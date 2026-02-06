import {
  Controller,
  Get,
  Post,
  Put,
  Delete,
  Body,
  Param,
  ParseIntPipe,
  HttpCode,
  HttpStatus,
} from '@nestjs/common';
import { CrawlMangaService } from '../services/crawl-manga.service';
import { CreateCrawlMangaDto } from '../dto/create-crawl-manga.dto';
import { UpdateCrawlMangaDto } from '../dto/update-crawl-manga.dto';
import { CrawlEngineService } from '../services/crawl-engine.service';

@Controller('api/crawl-mangas')
export class CrawlMangaController {
  constructor(
    private readonly crawlMangaService: CrawlMangaService,
    private readonly crawlEngineService: CrawlEngineService,
  ) {}

  @Get()
  async findAll() {
    return this.crawlMangaService.findAll();
  }

  @Get(':id')
  async findOne(@Param('id', ParseIntPipe) id: number) {
    return this.crawlMangaService.findOne(id);
  }

  @Post()
  @HttpCode(HttpStatus.CREATED)
  async create(@Body() createDto: CreateCrawlMangaDto) {
    return this.crawlMangaService.create(createDto);
  }

  @Put(':id')
  async update(
    @Param('id', ParseIntPipe) id: number,
    @Body() updateDto: UpdateCrawlMangaDto,
  ) {
    return this.crawlMangaService.update(id, updateDto);
  }

  @Delete(':id')
  @HttpCode(HttpStatus.NO_CONTENT)
  async remove(@Param('id', ParseIntPipe) id: number) {
    return this.crawlMangaService.remove(id);
  }

  @Post(':id/run')
  @HttpCode(HttpStatus.ACCEPTED)
  async runCrawl(@Param('id', ParseIntPipe) id: number) {
    await this.crawlEngineService.runMangaCrawl(id);
    return { message: 'Crawl job started', crawlMangaId: id };
  }

  @Post('run-all')
  @HttpCode(HttpStatus.ACCEPTED)
  async runAll() {
    await this.crawlEngineService.runAll();
    return { message: 'All crawl jobs started' };
  }
}
