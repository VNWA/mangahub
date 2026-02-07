import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CrawlManga, CrawlMangaStatus } from '../../crawl-database/entities/crawl-manga.entity';
import { CrawlJob, CrawlJobStatus, CrawlJobType } from '../../crawl-database/entities/crawl-job.entity';
import { VnwaSyncService } from './vnwa-sync.service';
import { HtmlParserService } from './html-parser.service';
import { CrawlLogger } from '../utils/logger';

@Injectable()
export class CrawlEngineService {
  constructor(
    @InjectRepository(CrawlManga, 'crawl')
    private readonly crawlMangaRepository: Repository<CrawlManga>,
    @InjectRepository(CrawlJob, 'crawl')
    private readonly crawlJobRepository: Repository<CrawlJob>,
    private readonly vnwaSyncService: VnwaSyncService,
    private readonly htmlParserService: HtmlParserService,
  ) {}

  /**
   * Run full crawl: detail + chapters
   */
  async runMangaCrawl(crawlMangaId: number): Promise<void> {
    // First crawl detail
    await this.crawlDetail(crawlMangaId);
    
    // Then crawl chapters
    await this.crawlChapters(crawlMangaId);
  }

  async runAll(): Promise<void> {
    const pendingMangas = await this.crawlMangaRepository.find({
      where: { status: CrawlMangaStatus.PENDING },
      take: 10,
    });

    for (const manga of pendingMangas) {
      try {
        await this.runMangaCrawl(manga.id);
      } catch (error: any) {
        CrawlLogger.error(`Failed to crawl manga ${manga.id}: ${error.message}`);
        // Continue with next manga
      }
    }
  }

  /**
   * Crawl detail only (manga info, no chapters)
   */
  async crawlDetail(crawlMangaId: number): Promise<void> {
    const crawlManga = await this.crawlMangaRepository.findOne({
      where: { id: crawlMangaId },
    });

    if (!crawlManga) {
      throw new Error(`CrawlManga with ID ${crawlMangaId} not found`);
    }

    // Update status to crawling
    crawlManga.status = CrawlMangaStatus.CRAWLING;
    await this.crawlMangaRepository.save(crawlManga);

    try {
      CrawlLogger.info(`Starting to crawl detail for: ${crawlManga.crawlUrl}`);

      // Parse manga detail
      const mangaDetail = await this.htmlParserService.parseManga(crawlManga.crawlUrl);

      // Generate slug from name
      const slug = mangaDetail.name
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');

      // Check if manga already exists in VNWA database
      let vnwaMangaId: number | null = null;
      let avatarPath: string | null = null;
      try {
        vnwaMangaId = await this.vnwaSyncService.syncManga({
          name: mangaDetail.name,
          description: mangaDetail.description,
          avatar: mangaDetail.avatar,
          status: mangaDetail.status,
          author_name: mangaDetail.author_name,
          categories: mangaDetail.categories || [],
        });
        
        // Get avatar path from VNWA manga
        const vnwaManga = await this.vnwaSyncService.getMangaById(vnwaMangaId);
        avatarPath = vnwaManga?.avatar || null;
        
        CrawlLogger.info(
          `Manga synced to VNWA database with ID: ${vnwaMangaId}`,
        );
      } catch (error: any) {
        CrawlLogger.error(`Failed to sync manga to VNWA: ${error.message}`);
        throw error;
      }

      // Update crawl manga with parsed data
      crawlManga.title = mangaDetail.name;
      crawlManga.slug = slug;
      crawlManga.avatar = avatarPath;
      crawlManga.vnwaMangaId = vnwaMangaId;
      crawlManga.latestChapter = mangaDetail.chapters[0]?.title || null;
      crawlManga.status = CrawlMangaStatus.DONE;
      crawlManga.lastCrawledAt = new Date();
      await this.crawlMangaRepository.save(crawlManga);

      CrawlLogger.success(`Detail crawl completed for manga: ${crawlManga.crawlUrl}`);
    } catch (error: any) {
      CrawlLogger.error(`Detail crawl failed for manga ${crawlManga.crawlUrl}: ${error.message}`);
      crawlManga.status = CrawlMangaStatus.ERROR;
      await this.crawlMangaRepository.save(crawlManga);
      throw error;
    }
  }

  /**
   * Crawl chapters (images) for a manga that already has detail
   */
  async crawlChapters(crawlMangaId: number): Promise<void> {
    const crawlManga = await this.crawlMangaRepository.findOne({
      where: { id: crawlMangaId },
    });

    if (!crawlManga) {
      throw new Error(`CrawlManga with ID ${crawlMangaId} not found`);
    }

    if (!crawlManga.vnwaMangaId) {
      throw new Error(`Manga ${crawlMangaId} has not been synced to VNWA yet. Please crawl detail first.`);
    }

    // Create crawl job
    const crawlJob = this.crawlJobRepository.create({
      crawlMangaId: crawlManga.id,
      type: CrawlJobType.UPDATE,
      status: CrawlJobStatus.RUNNING,
      startedAt: new Date(),
    });
    await this.crawlJobRepository.save(crawlJob);

    try {
      CrawlLogger.info(`Starting to crawl chapters for: ${crawlManga.crawlUrl}`);

      // Parse manga detail to get chapters
      const mangaDetail = await this.htmlParserService.parseManga(crawlManga.crawlUrl);

      // Chapters from crawl are usually newest first, reverse to get oldest first
      // This ensures we insert from chapter 1, 2, 3, ... (order 0, 1, 2, ...)
      const chaptersToSync = [...mangaDetail.chapters].reverse();

      // Get existing chapters to check and calculate order
      const existingChapters = await this.vnwaSyncService.getChaptersByMangaId(crawlManga.vnwaMangaId!);
      const existingSlugs = new Set(existingChapters.map(c => c.slug));
      const maxOrder = existingChapters.length > 0 
        ? Math.max(...existingChapters.map(c => c.order)) 
        : -1;

      // Sync chapters sequentially to maintain order and avoid overwhelming the database
      let currentOrder = maxOrder + 1;
      for (let i = 0; i < chaptersToSync.length; i++) {
        const chapter = chaptersToSync[i];
        try {
          const chapterSlug = chapter.title.toLowerCase().replace(/\s+/g, '-');
          
          // Check if chapter already exists
          if (existingSlugs.has(chapterSlug)) {
            CrawlLogger.info(`Chapter already exists, skipping: ${chapter.title}`);
            continue;
          }

          CrawlLogger.info(`Crawling chapter ${i + 1}/${chaptersToSync.length}: ${chapter.title}`);

          // Parse chapter images
          const images = await this.htmlParserService.parseImages(chapter.url);

          if (!images || images.length === 0) {
            CrawlLogger.warn(`No images found for chapter: ${chapter.title}`);
            continue;
          }

          // Sync chapter to VNWA with calculated order
          await this.vnwaSyncService.syncChapter(crawlManga.vnwaMangaId!, {
            name: chapter.title,
            slug: chapterSlug,
            order: currentOrder, // Use calculated order
          }, images);

          // Update tracking
          existingSlugs.add(chapterSlug);
          currentOrder++;

          CrawlLogger.info(`Synced chapter ${i + 1}/${chaptersToSync.length}: ${chapter.title} (${images.length} images, order: ${currentOrder - 1})`);
        } catch (error: any) {
          CrawlLogger.error(`Failed to sync chapter ${chapter.title}: ${error.message}`);
          // Continue with next chapter - don't fail entire crawl
        }
      }

      // Update latest chapter
      if (mangaDetail.chapters.length > 0) {
        crawlManga.latestChapter = mangaDetail.chapters[0].title;
      }
      crawlManga.lastCrawledAt = new Date();
      await this.crawlMangaRepository.save(crawlManga);

      // Update crawl job
      crawlJob.status = CrawlJobStatus.SUCCESS;
      crawlJob.finishedAt = new Date();
      await this.crawlJobRepository.save(crawlJob);

      CrawlLogger.success(`Chapters crawl completed for manga: ${crawlManga.crawlUrl}`);
    } catch (error: any) {
      CrawlLogger.error(`Chapters crawl failed for manga ${crawlManga.crawlUrl}: ${error.message}`);

      // Update crawl job
      crawlJob.status = CrawlJobStatus.FAILED;
      crawlJob.finishedAt = new Date();
      crawlJob.errorMessage = error.message;
      await this.crawlJobRepository.save(crawlJob);

      throw error;
    }
  }

  async syncToVnwa(crawlMangaId: number, mangaDetail?: any): Promise<void> {
    const crawlManga = await this.crawlMangaRepository.findOne({
      where: { id: crawlMangaId },
    });

    if (!crawlManga) {
      throw new Error(`CrawlManga with ID ${crawlMangaId} not found`);
    }

    // If mangaDetail not provided, parse it
    if (!mangaDetail) {
      mangaDetail = await this.htmlParserService.parseManga(crawlManga.crawlUrl);
    }

    // Sync manga to VNWA
    const vnwaMangaId = await this.vnwaSyncService.syncManga({
      name: mangaDetail.name,
      description: mangaDetail.description,
      avatar: mangaDetail.avatar,
      status: 'ongoing',
    });

    // Update crawl manga with VNWA ID
    if (vnwaMangaId) {
      crawlManga.vnwaMangaId = vnwaMangaId;
      await this.crawlMangaRepository.save(crawlManga);
    }

    // Sync chapters
    for (const chapter of mangaDetail.chapters) {
      try {
        // Parse chapter images
        const images = await this.htmlParserService.parseImages(chapter.url);

        // Sync chapter to VNWA
        await this.vnwaSyncService.syncChapter(vnwaMangaId, {
          name: chapter.title,
          slug: chapter.title.toLowerCase().replace(/\s+/g, '-'),
          order: mangaDetail.chapters.indexOf(chapter),
        }, images);
      } catch (error: any) {
        CrawlLogger.error(`Failed to sync chapter ${chapter.title}: ${error.message}`);
        // Continue with next chapter
      }
    }
  }
}
