import { Injectable } from '@nestjs/common';
import { InjectDataSource } from '@nestjs/typeorm';
import { DataSource } from 'typeorm';
import { MangaRepository } from '../../vnwa-database/repositories/manga.repository';
import { MangaChapterRepository } from '../../vnwa-database/repositories/manga-chapter.repository';
import { ServerChapterContentRepository } from '../../vnwa-database/repositories/server-chapter-content.repository';
import { MangaAuthorRepository } from '../../vnwa-database/repositories/manga-author.repository';
import { MangaCategoryRepository } from '../../vnwa-database/repositories/manga-category.repository';
import { Manga } from '../../vnwa-database/entities/manga.entity';
import { ConfigService } from '@nestjs/config';
import { MinIOService } from './minio.service';
import { ImageProcessingService } from './image-processing.service';
import { CrawlLogger } from '../utils/logger';

@Injectable()
export class VnwaSyncService {
  private readonly defaultUserId: number;

  constructor(
    @InjectDataSource('vnwa')
    private readonly vnwaDataSource: DataSource,
    private readonly mangaRepository: MangaRepository,
    private readonly mangaChapterRepository: MangaChapterRepository,
    private readonly serverChapterContentRepository: ServerChapterContentRepository,
    private readonly mangaAuthorRepository: MangaAuthorRepository,
    private readonly mangaCategoryRepository: MangaCategoryRepository,
    private readonly configService: ConfigService,
    private readonly minioService: MinIOService,
    private readonly imageProcessingService: ImageProcessingService,
  ) {
    this.defaultUserId = this.configService.get<number>('app.vnwaDefaultUserId', 1);
  }

  /**
   * Generate slug from name
   */
  private generateSlug(name: string): string {
    return name
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '') // Remove special characters
      .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
      .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
  }

  /**
   * Find manga by name or slug
   */
  async findMangaByNameOrSlug(name: string, slug?: string): Promise<Manga | null> {
    const searchSlug = slug || this.generateSlug(name);
    
    // Try to find by slug first (exact match)
    let manga = await this.mangaRepository.findOne({
      where: { slug: searchSlug },
    });

    // If not found, try to find by name using query builder for case-insensitive search
    if (!manga) {
      manga = await this.mangaRepository
        .createQueryBuilder('manga')
        .where('LOWER(TRIM(manga.name)) = LOWER(:name)', {
          name: name.trim(),
        })
        .getOne();
    }

    return manga;
  }

  async getMangaById(mangaId: number): Promise<Manga | null> {
    return this.mangaRepository.findOne({
      where: { id: mangaId },
    });
  }

  async syncManga(mangaData: {
    name: string;
    description?: string;
    avatar?: string;
    status?: string;
    author_name?: string;
    categories?: string[];
  }): Promise<number> {
    const queryRunner = this.vnwaDataSource.createQueryRunner();
    await queryRunner.connect();
    await queryRunner.startTransaction();

    try {
      const slug = this.generateSlug(mangaData.name);

      // Process and upload avatar if provided
      let avatarPath: string | null = null;
      if (mangaData.avatar) {
        try {
          // Process avatar: resize to 300x400 and convert to WebP
          const processedAvatar = await this.imageProcessingService.processAvatarFromUrl(
            mangaData.avatar,
            300,
            400,
          );

          // Upload to MinIO - path: /avatar-manga/
          const avatarFileName = this.minioService.generateUniqueFilename('avatar-manga', 'webp');
          avatarPath = await this.minioService.uploadFile(
            processedAvatar,
            avatarFileName,
            'image/webp',
          );
        } catch (error: any) {
          CrawlLogger.warn(`Failed to process avatar: ${error.message}`);
          // Continue without avatar
        }
      }

      // Handle author (only for new manga, skip if empty)
      let authorId: number | null = null;
      if (mangaData.author_name && mangaData.author_name.trim() !== '') {
        try {
          const author = await this.mangaAuthorRepository.findOrCreateByName(
            mangaData.author_name.trim(),
          );
          authorId = author.id;
        } catch (error: any) {
          CrawlLogger.warn(`Failed to sync author: ${error.message}`);
        }
      }

      // Check if manga already exists
      let manga = await this.findMangaByNameOrSlug(mangaData.name, slug);

      if (manga) {
        // Update existing manga
        Object.assign(manga, {
          name: mangaData.name,
          description: mangaData.description,
          avatar: avatarPath,
          userId: this.defaultUserId,
          mangaAuthorId: authorId || manga.mangaAuthorId, // Only update if new author found
        });
        manga = await this.mangaRepository.save(manga);
      } else {
        // Create new manga
        manga = this.mangaRepository.create({
          name: mangaData.name,
          slug: slug,
          description: mangaData.description || null,
          avatar: avatarPath,
          status: (mangaData.status as any) || 'ongoing',
          userId: this.defaultUserId,
          mangaAuthorId: authorId,
        });

        manga = await this.mangaRepository.save(manga);
      }

      // Handle categories (many-to-many relationship, skip if empty)
      if (
        mangaData.categories &&
        mangaData.categories.length > 0 &&
        mangaData.categories.some((cat) => cat && cat.trim() !== '')
      ) {
        try {
          const categoryIds: number[] = [];
          for (const categoryName of mangaData.categories) {
            if (categoryName && categoryName.trim() !== '') {
              const category = await this.mangaCategoryRepository.findOrCreateByName(
                categoryName.trim(),
              );
              categoryIds.push(category.id);
            }
          }

          // Sync categories to manga_category_manga pivot table
          if (categoryIds.length > 0) {
            // Delete existing relationships
            await this.vnwaDataSource.query(
              `DELETE FROM manga_category_manga WHERE manga_id = $1`,
              [manga.id],
            );

            // Insert new relationships
            for (const categoryId of categoryIds) {
              await this.vnwaDataSource.query(
                `INSERT INTO manga_category_manga (manga_id, manga_category_id) 
                 VALUES ($1, $2) 
                 ON CONFLICT DO NOTHING`,
                [manga.id, categoryId],
              );
            }
          }
        } catch (error: any) {
          CrawlLogger.warn(`Failed to sync categories: ${error.message}`);
        }
      }

      await queryRunner.commitTransaction();
      return manga.id;
    } catch (error) {
      await queryRunner.rollbackTransaction();
      throw error;
    } finally {
      await queryRunner.release();
    }
  }

  /**
   * Get or create default manga server
   */
  private async getOrCreateDefaultServer(): Promise<number> {
    // Try to find default server
    const result = await this.vnwaDataSource.query(
      `SELECT id FROM manga_servers WHERE name = $1 LIMIT 1`,
      ['Default'],
    );

    if (result && result.length > 0) {
      return result[0].id;
    }

    // Create default server if not exists
    const insertResult = await this.vnwaDataSource.query(
      `INSERT INTO manga_servers (name, description, created_at, updated_at) 
       VALUES ($1, $2, NOW(), NOW()) 
       RETURNING id`,
      ['Default', 'Default server for crawled chapters'],
    );

    return insertResult[0].id;
  }

  /**
   * Get all chapters for a manga
   */
  async getChaptersByMangaId(mangaId: number): Promise<Array<{ id: number; slug: string; order: number }>> {
    const chapters = await this.mangaChapterRepository.find({
      where: { mangaId },
      select: ['id', 'slug', 'order'],
      order: { order: 'ASC' },
    });

    return chapters;
  }

  /**
   * Get next order for chapter in manga
   */
  private async getNextChapterOrder(mangaId: number): Promise<number> {
    const result = await this.vnwaDataSource.query(
      `SELECT COALESCE(MAX("order"), -1) + 1 as next_order 
       FROM manga_chapters 
       WHERE manga_id = $1`,
      [mangaId],
    );

    return result[0]?.next_order || 0;
  }

  async syncChapter(
    mangaId: number,
    chapterData: {
      name: string;
      slug?: string;
      order?: number;
      description?: string;
    },
    images: string[],
  ): Promise<number> {
    const queryRunner = this.vnwaDataSource.createQueryRunner();
    await queryRunner.connect();
    await queryRunner.startTransaction();

    try {
      // Generate slug if not provided
      const slug = chapterData.slug || this.generateSlug(chapterData.name);

      // Find existing chapter by slug and manga_id
      let chapter = await this.mangaChapterRepository.findOne({
        where: { mangaId, slug },
      });

      // Determine order: use provided order, or keep existing, or get next
      let order = chapterData.order;
      if (order === undefined || order === null) {
        if (chapter) {
          // Keep existing order if chapter exists and no order provided
          order = chapter.order;
        } else {
          // Get next order for new chapter
          order = await this.getNextChapterOrder(mangaId);
        }
      }

      if (chapter) {
        // Update existing chapter (always update order if provided to ensure consistency)
        Object.assign(chapter, {
          name: chapterData.name,
          slug: slug,
          order: order, // Always use determined order
          description: chapterData.description !== undefined 
            ? chapterData.description 
            : chapter.description, // Only update if provided
          userId: this.defaultUserId,
        });
        chapter = await this.mangaChapterRepository.save(chapter);
      } else {
        // Create new chapter
        chapter = this.mangaChapterRepository.create({
          mangaId: mangaId,
          slug: slug,
          name: chapterData.name,
          order: order,
          description: chapterData.description || null,
          userId: this.defaultUserId,
          coinCost: 0,
          isLocked: false,
        });
        chapter = await this.mangaChapterRepository.save(chapter);
      }

      // Use server ID = 1 as requested
      const serverId = 1;

      // Upload images to MinIO and get paths (no format/resize, just download and upload)
      const imagePaths: string[] = [];
      if (images && images.length > 0) {
        for (let i = 0; i < images.length; i++) {
          const imageUrl = images[i];
          try {
            // Download image directly
            const imageBuffer = await this.imageProcessingService.downloadImage(imageUrl);
            
            // Get original extension from URL or default to jpg
            const urlPath = new URL(imageUrl).pathname;
            const extension = urlPath.split('.').pop()?.toLowerCase() || 'jpg';
            
            // Detect content type
            const contentType = extension === 'jpg' || extension === 'jpeg' 
              ? 'image/jpeg' 
              : extension === 'png' 
              ? 'image/png' 
              : extension === 'gif'
              ? 'image/gif'
              : extension === 'webp'
              ? 'image/webp'
              : 'image/jpeg';
            
            // Upload to MinIO - path: chapter-images/
            const imagePath = this.minioService.generateUniqueFilename(
              'chapter-images',
              extension,
            );
            const uploadedPath = await this.minioService.uploadFile(
              imageBuffer,
              imagePath,
              contentType,
            );
            
            imagePaths.push(uploadedPath);
            CrawlLogger.info(`Uploaded image ${i + 1}/${images.length} for chapter ${chapter.id}`);
          } catch (error: any) {
            CrawlLogger.error(`Failed to upload image ${i + 1}: ${error.message}`);
            // Continue with next image
          }
        }

        // Upsert chapter content with MinIO paths
        if (imagePaths.length > 0) {
          await this.serverChapterContentRepository.upsertContent(
            chapter.id,
            serverId,
            imagePaths, // Store paths, not URLs
          );
        }
      }

      await queryRunner.commitTransaction();
      return chapter.id;
    } catch (error) {
      await queryRunner.rollbackTransaction();
      throw error;
    } finally {
      await queryRunner.release();
    }
  }
}
