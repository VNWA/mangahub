import { Injectable, NotFoundException, BadRequestException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import {
  CrawlManga,
  CrawlMangaStatus,
} from '../../crawl-database/entities/crawl-manga.entity';
import { CreateCrawlMangaDto } from '../dto/create-crawl-manga.dto';
import { UpdateCrawlMangaDto } from '../dto/update-crawl-manga.dto';
import { CrawlServiceRegistry } from './crawls/crawl-service-registry';
import { CrawlQueueService } from '../queue/crawl-queue.service';
import { CrawlLogger } from '../utils/logger';
import { MinIOService } from './minio.service';

@Injectable()
export class CrawlMangaService {
  constructor(
    @InjectRepository(CrawlManga, 'crawl')
    private readonly crawlMangaRepository: Repository<CrawlManga>,
    private readonly crawlServiceRegistry: CrawlServiceRegistry,
    private readonly crawlQueueService: CrawlQueueService,
    private readonly minioService: MinIOService,
  ) { }

  async findAll(): Promise<CrawlManga[]> {
    return this.crawlMangaRepository.find({
      order: { createdAt: 'DESC' },
    });
  }

  async findAllPaginated(page: number = 1, limit: number = 15) {
    const skip = (page - 1) * limit;

    const [data, total] = await this.crawlMangaRepository.findAndCount({
      order: { createdAt: 'DESC' },
      skip,
      take: limit,
    });

    const totalPages = Math.ceil(total / limit);

    // Map data to include source info (avatar is already path, not full URL)
    const mappedData = data.map(manga => ({
      ...manga,
      avatar: manga.avatar, // Path only, not full URL
      source: {
        name: manga.sourceName,
        baseUrl: `https://${manga.sourceName}`,
        isActive: true,
      },
    }));

    return {
      data: mappedData,
      meta: {
        current_page: page,
        per_page: limit,
        total,
        last_page: totalPages,
        from: skip + 1,
        to: Math.min(skip + limit, total),
      },
      links: this.generatePaginationLinks(page, totalPages),
    };
  }

  private generatePaginationLinks(currentPage: number, lastPage: number) {
    const links: Array<{ url: string | null; label: string; active: boolean }> = [];

    // Previous link
    links.push({
      url: currentPage > 1 ? `?page=${currentPage - 1}` : null,
      label: '&laquo; Previous',
      active: false,
    });

    // Page links
    for (let i = 1; i <= lastPage; i++) {
      if (
        i === 1 ||
        i === lastPage ||
        (i >= currentPage - 2 && i <= currentPage + 2)
      ) {
        links.push({
          url: `?page=${i}`,
          label: i.toString(),
          active: i === currentPage,
        });
      } else if (i === currentPage - 3 || i === currentPage + 3) {
        links.push({
          url: null,
          label: '...',
          active: false,
        });
      }
    }

    // Next link
    links.push({
      url: currentPage < lastPage ? `?page=${currentPage + 1}` : null,
      label: 'Next &raquo;',
      active: false,
    });

    return links;
  }

  async findOne(id: number): Promise<any> {
    const crawlManga = await this.crawlMangaRepository.findOne({
      where: { id },
    });

    if (!crawlManga) {
      throw new NotFoundException(`CrawlManga with ID ${id} not found`);
    }

    // Return with avatar as path (not full URL)
    return {
      ...crawlManga,
      avatar: crawlManga.avatar, // Path only, not full URL
    };
  }

  /**
   * Extract domain from URL
   */
  private extractDomain(url: string): string {
    try {
      const urlObj = new URL(url);
      return urlObj.hostname.replace('www.', '').toLowerCase();
    } catch {
      throw new BadRequestException('Invalid URL format');
    }
  }

  /**
   * Validate if domain is supported (sources are managed in code, not database)
   */
  private validateSource(url: string): string {
    const domain = this.extractDomain(url);

    // Check if service is supported
    const service = this.crawlServiceRegistry.getService(domain);
    if (!service) {
      throw new BadRequestException(
        `Domain "${domain}" is not supported. Supported domains: ${this.crawlServiceRegistry.listServices().join(', ')}`,
      );
    }

    return domain;
  }

  async create(createDto: CreateCrawlMangaDto): Promise<CrawlManga> {
    // Check if URL already exists
    const existing = await this.crawlMangaRepository.findOne({
      where: { crawlUrl: createDto.crawlUrl },
    });

    if (existing) {
      throw new BadRequestException('Crawl URL already exists');
    }

    // Validate source (sources are managed in code, not database)
    const sourceName = this.validateSource(createDto.crawlUrl);

    // Create crawl manga in pending status
    const crawlManga = this.crawlMangaRepository.create({
      crawlUrl: createDto.crawlUrl,
      sourceName: sourceName, // Store domain name (e.g., 'manga18.me')
      title: null,
      slug: null,
      avatar: null,
      vnwaMangaId: null,
      latestChapter: null,
      status: CrawlMangaStatus.PENDING,
      lastCrawledAt: null,
    });

    const savedManga = await this.crawlMangaRepository.save(crawlManga);

    // Queue background job to crawl detail & chapters
    this.crawlQueueService
      .addCrawlJob({
        crawlMangaId: savedManga.id,
        type: 'full',
      })
      .then(() => {
        CrawlLogger.info(`Queued crawl job for manga ${savedManga.id}`);
      })
      .catch((error) => {
        CrawlLogger.error(
          `Failed to queue crawl job for manga ${savedManga.id}: ${error.message}`,
        );
      });

    CrawlLogger.success(`Crawl manga created successfully: ${savedManga.id}`);

    return savedManga;
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
