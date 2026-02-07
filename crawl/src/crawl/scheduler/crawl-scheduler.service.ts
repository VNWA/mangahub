import { Injectable } from '@nestjs/common';
import { Cron, CronExpression } from '@nestjs/schedule';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository, IsNull, Not } from 'typeorm';
import { CrawlManga, CrawlMangaStatus } from '../../crawl-database/entities/crawl-manga.entity';
import { CrawlQueueService } from '../queue/crawl-queue.service';
import { CrawlJobData } from '../queue/crawl.processor';

@Injectable()
export class CrawlSchedulerService {
  constructor(
    @InjectRepository(CrawlManga, 'crawl')
    private readonly crawlMangaRepository: Repository<CrawlManga>,
    private readonly crawlQueueService: CrawlQueueService,
  ) { }

  @Cron(CronExpression.EVERY_HOUR)
  async schedulePendingCrawls() {
    // Find all pending crawl mangas (skip those that are currently crawling)
    const pendingMangas = await this.crawlMangaRepository.find({
      where: { status: CrawlMangaStatus.PENDING },
      take: 10, // Process 10 at a time
    });

    // Filter out mangas that are currently being processed
    const availableMangas = pendingMangas.filter(
      (manga) => manga.status !== CrawlMangaStatus.CRAWLING,
    );

    // Add jobs to queue
    const jobs: CrawlJobData[] = availableMangas.map((manga) => ({
      crawlMangaId: manga.id,
      type: (manga.vnwaMangaId ? 'update' : 'full') as 'full' | 'update',
    }));

    if (jobs.length > 0) {
      await this.crawlQueueService.addBulkCrawlJobs(jobs);
    }
  }

  @Cron(CronExpression.EVERY_6_HOURS)
  async scheduleUpdateCrawls() {
    // Find all done crawl mangas that need updates (skip those that are currently crawling)
    const doneMangas = await this.crawlMangaRepository.find({
      where: { status: CrawlMangaStatus.DONE },
      take: 20, // Process 20 at a time
    });

    // Filter out mangas that are currently being processed
    const availableMangas = doneMangas.filter(
      (manga) => manga.status !== CrawlMangaStatus.CRAWLING,
    );

    // Add update jobs to queue (only check new chapters, not full crawl)
    const jobs: CrawlJobData[] = availableMangas.map((manga) => ({
      crawlMangaId: manga.id,
      type: 'update' as const,
    }));

    if (jobs.length > 0) {
      await this.crawlQueueService.addBulkCrawlJobs(jobs);
    }
  }


  @Cron('0 */3 * * *') // Chạy mỗi 3 giờ (0:00, 3:00, 6:00, 9:00, 12:00, 15:00, 18:00, 21:00)
  async scheduleChapterCheck() {
    // Find all done crawl mangas that have vnwaMangaId (already synced)
    // Skip those that are currently crawling
    const doneMangas = await this.crawlMangaRepository.find({
      where: {
        status: CrawlMangaStatus.DONE,
        vnwaMangaId: Not(IsNull()), // Only mangas that are already synced (vnwaMangaId IS NOT NULL)
      },
      take: 50, // Tăng số lượng để xử lý nhiều hơn mỗi lần chạy
    });

    // Filter out mangas that are currently being processed
    const availableMangas = doneMangas.filter(
      (manga) => manga.status !== CrawlMangaStatus.CRAWLING,
    );

    // Add update jobs to queue (only check new chapters, không crawl lại toàn bộ)
    const jobs: CrawlJobData[] = availableMangas.map((manga) => ({
      crawlMangaId: manga.id,
      type: 'update' as const, // Chỉ crawl chapters mới, không crawl lại detail
    }));

    if (jobs.length > 0) {
      await this.crawlQueueService.addBulkCrawlJobs(jobs);
    }
  }
}
