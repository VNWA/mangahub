import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
  OneToMany,
} from 'typeorm';
import { Manga } from './manga.entity';
import { ServerChapterContent } from './server-chapter-content.entity';

@Entity('manga_chapters')
export class MangaChapter {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ name: 'manga_id', type: 'int' })
  mangaId: number;

  @ManyToOne(() => Manga, (manga) => manga.chapters)
  @JoinColumn({ name: 'manga_id' })
  manga: Manga;

  @Column({ name: 'user_id', type: 'int' })
  userId: number;

  @Column({ type: 'int', default: 0 })
  order: number;

  @Column({ type: 'varchar', length: 255 })
  name: string;

  @Column({ type: 'varchar', length: 255 })
  slug: string;

  @Column({ type: 'text', nullable: true })
  description: string | null;

  @Column({ name: 'coin_cost', type: 'int', default: 0 })
  coinCost: number;

  @Column({ name: 'is_locked', type: 'boolean', default: false })
  isLocked: boolean;

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;

  @OneToMany(
    () => ServerChapterContent,
    (content) => content.mangaChapter,
  )
  contents: ServerChapterContent[];
}
