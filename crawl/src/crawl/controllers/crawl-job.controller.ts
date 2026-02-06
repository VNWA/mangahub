import {
  Controller,
  Get,
  Param,
  ParseIntPipe,
  Query,
} from '@nestjs/common';
import { CrawlJobService } from '../services/crawl-job.service';

@Controller('api/crawl-jobs')
export class CrawlJobController {
  constructor(private readonly crawlJobService: CrawlJobService) {}

  @Get()
  async findAll(
    @Query('crawlMangaId') crawlMangaId?: string,
    @Query('status') status?: string,
    @Query('limit') limit?: string,
  ) {
    return this.crawlJobService.findAll({
      crawlMangaId: crawlMangaId ? parseInt(crawlMangaId, 10) : undefined,
      status,
      limit: limit ? parseInt(limit, 10) : undefined,
    });
  }

  @Get(':id')
  async findOne(@Param('id', ParseIntPipe) id: number) {
    return this.crawlJobService.findOne(id);
  }
}
