import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CrawlJob, CrawlJobStatus } from '../../crawl-database/entities/crawl-job.entity';

interface FindAllOptions {
  crawlMangaId?: number;
  status?: string;
  limit?: number;
}

@Injectable()
export class CrawlJobService {
  constructor(
    @InjectRepository(CrawlJob, 'crawl')
    private readonly crawlJobRepository: Repository<CrawlJob>,
  ) {}

  async findAll(options: FindAllOptions = {}): Promise<CrawlJob[]> {
    const queryBuilder = this.crawlJobRepository
      .createQueryBuilder('job')
      .leftJoinAndSelect('job.crawlManga', 'crawlManga')
      .orderBy('job.createdAt', 'DESC');

    if (options.crawlMangaId) {
      queryBuilder.where('job.crawlMangaId = :crawlMangaId', {
        crawlMangaId: options.crawlMangaId,
      });
    }

    if (options.status) {
      queryBuilder.andWhere('job.status = :status', {
        status: options.status,
      });
    }

    if (options.limit) {
      queryBuilder.limit(options.limit);
    }

    return queryBuilder.getMany();
  }

  async findOne(id: number): Promise<CrawlJob> {
    const job = await this.crawlJobRepository.findOne({
      where: { id },
      relations: ['crawlManga'],
    });

    if (!job) {
      throw new NotFoundException(`CrawlJob with ID ${id} not found`);
    }

    return job;
  }
}
