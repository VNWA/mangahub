import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  OneToMany,
} from 'typeorm';
import { CrawlJob } from './crawl-job.entity';

export enum CrawlMangaStatus {
  PENDING = 'pending',
  CRAWLING = 'crawling',
  DONE = 'done',
  ERROR = 'error',
}

@Entity('crawl_mangas')
export class CrawlManga {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'varchar', length: 255 })
  sourceName: string; // Domain name (e.g., 'manga18.me')

  @Column({ type: 'varchar', length: 1000 })
  crawlUrl: string;

  @Column({ type: 'varchar', length: 500, nullable: true })
  title: string | null;

  @Column({ type: 'varchar', length: 500, nullable: true })
  slug: string | null;

  @Column({ type: 'varchar', length: 500, nullable: true })
  avatar: string | null;

  @Column({ type: 'int', nullable: true })
  vnwaMangaId: number | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  latestChapter: string | null;

  @Column({
    type: 'enum',
    enum: CrawlMangaStatus,
    default: CrawlMangaStatus.PENDING,
  })
  status: CrawlMangaStatus;

  @Column({ type: 'timestamp', nullable: true })
  lastCrawledAt: Date | null;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @OneToMany(() => CrawlJob, (crawlJob) => crawlJob.crawlManga)
  crawlJobs: CrawlJob[];
}
