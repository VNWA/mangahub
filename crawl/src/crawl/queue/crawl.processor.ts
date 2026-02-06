import { Processor, WorkerHost } from '@nestjs/bullmq';
import { Job } from 'bullmq';
import { CrawlEngineService } from '../services/crawl-engine.service';

export interface CrawlJobData {
  crawlMangaId: number;
  type: 'full' | 'update';
}

@Processor('crawl')
export class CrawlProcessor extends WorkerHost {
  constructor(private readonly crawlEngineService: CrawlEngineService) {
    super();
  }

  async process(job: Job<CrawlJobData>): Promise<void> {
    const { crawlMangaId, type } = job.data;
    
    // This will be implemented when crawl logic is added
    // For now, it's a skeleton
    await this.crawlEngineService.runMangaCrawl(crawlMangaId);
  }
}
