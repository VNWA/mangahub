import { Injectable } from '@nestjs/common';
import { InjectDataSource } from '@nestjs/typeorm';
import { DataSource } from 'typeorm';
import { MangaRepository } from '../../vnwa-database/repositories/manga.repository';
import { MangaChapterRepository } from '../../vnwa-database/repositories/manga-chapter.repository';
import { ServerChapterContentRepository } from '../../vnwa-database/repositories/server-chapter-content.repository';
import { ConfigService } from '@nestjs/config';

@Injectable()
export class VnwaSyncService {
  private readonly defaultUserId: number;

  constructor(
    @InjectDataSource('vnwa')
    private readonly vnwaDataSource: DataSource,
    private readonly mangaRepository: MangaRepository,
    private readonly mangaChapterRepository: MangaChapterRepository,
    private readonly serverChapterContentRepository: ServerChapterContentRepository,
    private readonly configService: ConfigService,
  ) {
    this.defaultUserId = this.configService.get<number>('app.vnwaDefaultUserId', 1);
  }

  async syncManga(mangaData: any): Promise<number> {
    // This will be implemented when crawl logic is added
    // For now, it's a placeholder that uses transactions
    const queryRunner = this.vnwaDataSource.createQueryRunner();
    await queryRunner.connect();
    await queryRunner.startTransaction();

    try {
      // Placeholder implementation
      // const manga = await this.mangaRepository.findOrCreateBySlug(...)
      // await queryRunner.commitTransaction();
      // return manga.id;
      throw new Error('syncManga not implemented');
    } catch (error) {
      await queryRunner.rollbackTransaction();
      throw error;
    } finally {
      await queryRunner.release();
    }
  }

  async syncChapter(
    mangaId: number,
    chapterData: any,
    images: string[],
  ): Promise<number> {
    const queryRunner = this.vnwaDataSource.createQueryRunner();
    await queryRunner.connect();
    await queryRunner.startTransaction();

    try {
      // Placeholder implementation
      // const chapter = await this.mangaChapterRepository.findOrCreateBySlug(...)
      // await this.serverChapterContentRepository.upsertContent(...)
      // await queryRunner.commitTransaction();
      // return chapter.id;
      throw new Error('syncChapter not implemented');
    } catch (error) {
      await queryRunner.rollbackTransaction();
      throw error;
    } finally {
      await queryRunner.release();
    }
  }
}
