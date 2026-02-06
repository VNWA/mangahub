import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { CrawlManga } from './crawl-manga.entity';

export enum CrawlJobType {
  FULL = 'full',
  UPDATE = 'update',
}

export enum CrawlJobStatus {
  PENDING = 'pending',
  RUNNING = 'running',
  SUCCESS = 'success',
  FAILED = 'failed',
}

@Entity('crawl_jobs')
export class CrawlJob {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'int' })
  crawlMangaId: number;

  @ManyToOne(() => CrawlManga, (crawlManga) => crawlManga.crawlJobs)
  @JoinColumn({ name: 'crawlMangaId' })
  crawlManga: CrawlManga;

  @Column({
    type: 'enum',
    enum: CrawlJobType,
    default: CrawlJobType.FULL,
  })
  type: CrawlJobType;

  @Column({
    type: 'enum',
    enum: CrawlJobStatus,
    default: CrawlJobStatus.PENDING,
  })
  status: CrawlJobStatus;

  @Column({ type: 'timestamp', nullable: true })
  startedAt: Date | null;

  @Column({ type: 'timestamp', nullable: true })
  finishedAt: Date | null;

  @Column({ type: 'text', nullable: true })
  errorMessage: string | null;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;
}
