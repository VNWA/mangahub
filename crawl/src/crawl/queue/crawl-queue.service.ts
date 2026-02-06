import { Injectable } from '@nestjs/common';
import { InjectQueue } from '@nestjs/bullmq';
import { Queue } from 'bullmq';
import { CrawlJobData } from './crawl.processor';

@Injectable()
export class CrawlQueueService {
  constructor(
    @InjectQueue('crawl')
    private readonly crawlQueue: Queue<CrawlJobData>,
  ) {}

  async addCrawlJob(data: CrawlJobData): Promise<void> {
    await this.crawlQueue.add('crawl-manga', data, {
      attempts: 3,
      backoff: {
        type: 'exponential',
        delay: 5000,
      },
    });
  }

  async addBulkCrawlJobs(jobs: CrawlJobData[]): Promise<void> {
    await this.crawlQueue.addBulk(
      jobs.map((data) => ({
        name: 'crawl-manga',
        data,
        opts: {
          attempts: 3,
          backoff: {
            type: 'exponential',
            delay: 5000,
          },
        },
      })),
    );
  }
}
