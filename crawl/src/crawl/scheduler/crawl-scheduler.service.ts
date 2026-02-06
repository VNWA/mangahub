import { Injectable } from '@nestjs/common';
import { Cron, CronExpression } from '@nestjs/schedule';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CrawlManga, CrawlMangaStatus } from '../../crawl-database/entities/crawl-manga.entity';
import { CrawlQueueService } from '../queue/crawl-queue.service';
import { CrawlJobData } from '../queue/crawl.processor';

@Injectable()
export class CrawlSchedulerService {
  constructor(
    @InjectRepository(CrawlManga, 'crawl')
    private readonly crawlMangaRepository: Repository<CrawlManga>,
    private readonly crawlQueueService: CrawlQueueService,
  ) {}

  @Cron(CronExpression.EVERY_HOUR)
  async schedulePendingCrawls() {
    // Find all pending crawl mangas
    const pendingMangas = await this.crawlMangaRepository.find({
      where: { status: CrawlMangaStatus.PENDING },
      take: 10, // Process 10 at a time
    });

    // Add jobs to queue
    const jobs: CrawlJobData[] = pendingMangas.map((manga) => ({
      crawlMangaId: manga.id,
      type: (manga.vnwaMangaId ? 'update' : 'full') as 'full' | 'update',
    }));

    if (jobs.length > 0) {
      await this.crawlQueueService.addBulkCrawlJobs(jobs);
    }
  }

  @Cron(CronExpression.EVERY_6_HOURS)
  async scheduleUpdateCrawls() {
    // Find all done crawl mangas that need updates
    const doneMangas = await this.crawlMangaRepository.find({
      where: { status: CrawlMangaStatus.DONE },
      take: 20, // Process 20 at a time
    });

    // Add update jobs to queue
    const jobs: CrawlJobData[] = doneMangas.map((manga) => ({
      crawlMangaId: manga.id,
      type: 'update' as const,
    }));

    if (jobs.length > 0) {
      await this.crawlQueueService.addBulkCrawlJobs(jobs);
    }
  }
}
