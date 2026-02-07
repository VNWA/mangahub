import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  OneToMany,
} from 'typeorm';
import { MangaChapter } from './manga-chapter.entity';

export enum MangaStatus {
  ONGOING = 'ongoing',
  COMPLETED = 'completed',
  HIATUS = 'hiatus',
  CANCELLED = 'cancelled',
}

@Entity('mangas')
export class Manga {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ name: 'manga_author_id', type: 'int', nullable: true })
  mangaAuthorId: number | null;

  @Column({ name: 'manga_badge_id', type: 'int', nullable: true })
  mangaBadgeId: number | null;

  @Column({ name: 'user_id', type: 'int', nullable: true })
  userId: number | null;

  @Column({ type: 'varchar', length: 255, nullable: true })
  avatar: string | null;

  @Column({ type: 'varchar', length: 255 })
  name: string;

  @Column({ type: 'varchar', length: 255, unique: true })
  slug: string;

  @Column({ type: 'text', nullable: true })
  description: string | null;

  @Column({
    type: 'enum',
    enum: MangaStatus,
    default: MangaStatus.ONGOING,
  })
  status: MangaStatus;

  @Column({ name: 'total_views', type: 'int', default: 0 })
  totalViews: number;

  @Column({ name: 'monthly_views', type: 'int', default: 0 })
  monthlyViews: number;

  @Column({ name: 'weekly_views', type: 'int', default: 0 })
  weeklyViews: number;

  @Column({ name: 'daily_views', type: 'int', default: 0 })
  dailyViews: number;

  @Column({ name: 'total_follow', type: 'int', default: 0 })
  totalFollow: number;

  @Column({ type: 'float', default: 0 })
  rating: number;

  @Column({ name: 'total_ratings', type: 'int', default: 0 })
  totalRatings: number;

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;

  @OneToMany(() => MangaChapter, (chapter) => chapter.manga)
  chapters: MangaChapter[];
}
