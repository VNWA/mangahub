import { Injectable, NotImplementedException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CrawlManga } from '../../crawl-database/entities/crawl-manga.entity';
import { CrawlJob } from '../../crawl-database/entities/crawl-job.entity';
import { VnwaSyncService } from './vnwa-sync.service';

@Injectable()
export class CrawlEngineService {
  constructor(
    @InjectRepository(CrawlManga, 'crawl')
    private readonly crawlMangaRepository: Repository<CrawlManga>,
    @InjectRepository(CrawlJob, 'crawl')
    private readonly crawlJobRepository: Repository<CrawlJob>,
    private readonly vnwaSyncService: VnwaSyncService,
  ) {}

  async runMangaCrawl(crawlMangaId: number): Promise<void> {
    throw new NotImplementedException('runMangaCrawl is not implemented');
  }

  async runAll(): Promise<void> {
    throw new NotImplementedException('runAll is not implemented');
  }

  async syncToVnwa(crawlMangaId: number): Promise<void> {
    throw new NotImplementedException('syncToVnwa is not implemented');
  }
}
