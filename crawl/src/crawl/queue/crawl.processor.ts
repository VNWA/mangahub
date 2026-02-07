import { Processor, WorkerHost } from '@nestjs/bullmq';
import { Job } from 'bullmq';
import { CrawlEngineService } from '../services/crawl-engine.service';

export interface CrawlJobData {
  crawlMangaId: number;
  type: 'full' | 'update';
}

// Get concurrency from env (BullMQ decorator doesn't support dependency injection)
// Có thể điều chỉnh qua CRAWL_CONCURRENCY trong .env
const CRAWL_CONCURRENCY = parseInt(process.env.CRAWL_CONCURRENCY || '3', 10);

@Processor('crawl', {
  concurrency: CRAWL_CONCURRENCY, // Chạy song song N jobs (configurable via env)
})
export class CrawlProcessor extends WorkerHost {
  constructor(private readonly crawlEngineService: CrawlEngineService) {
    super();
  }

  async process(job: Job<CrawlJobData>): Promise<void> {
    const { crawlMangaId, type } = job.data;
    
    if (type === 'full') {
      await this.crawlEngineService.crawlDetail(crawlMangaId);
    } else if (type === 'update') {
      await this.crawlEngineService.crawlChapters(crawlMangaId);
    } else {
      await this.crawlEngineService.runMangaCrawl(crawlMangaId);
    }
  }
}
