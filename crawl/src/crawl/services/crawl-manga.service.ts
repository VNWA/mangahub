import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CrawlManga } from '../../crawl-database/entities/crawl-manga.entity';
import { CreateCrawlMangaDto } from '../dto/create-crawl-manga.dto';
import { UpdateCrawlMangaDto } from '../dto/update-crawl-manga.dto';

@Injectable()
export class CrawlMangaService {
  constructor(
    @InjectRepository(CrawlManga, 'crawl')
    private readonly crawlMangaRepository: Repository<CrawlManga>,
  ) {}

  async findAll(): Promise<CrawlManga[]> {
    return this.crawlMangaRepository.find({
      relations: ['source'],
      order: { createdAt: 'DESC' },
    });
  }

  async findOne(id: number): Promise<CrawlManga> {
    const crawlManga = await this.crawlMangaRepository.findOne({
      where: { id },
      relations: ['source'],
    });

    if (!crawlManga) {
      throw new NotFoundException(`CrawlManga with ID ${id} not found`);
    }

    return crawlManga;
  }

  async create(createDto: CreateCrawlMangaDto): Promise<CrawlManga> {
    const crawlManga = this.crawlMangaRepository.create(createDto);
    return this.crawlMangaRepository.save(crawlManga);
  }

  async update(
    id: number,
    updateDto: UpdateCrawlMangaDto,
  ): Promise<CrawlManga> {
    const crawlManga = await this.findOne(id);
    Object.assign(crawlManga, updateDto);
    return this.crawlMangaRepository.save(crawlManga);
  }

  async remove(id: number): Promise<void> {
    const crawlManga = await this.findOne(id);
    await this.crawlMangaRepository.remove(crawlManga);
  }
}
