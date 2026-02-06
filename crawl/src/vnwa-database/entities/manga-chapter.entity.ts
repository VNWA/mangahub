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

  @Column({ type: 'int' })
  mangaId: number;

  @ManyToOne(() => Manga, (manga) => manga.chapters)
  @JoinColumn({ name: 'mangaId' })
  manga: Manga;

  @Column({ type: 'int' })
  userId: number;

  @Column({ type: 'int', default: 0 })
  order: number;

  @Column({ type: 'varchar', length: 255 })
  name: string;

  @Column({ type: 'varchar', length: 255 })
  slug: string;

  @Column({ type: 'text', nullable: true })
  description: string | null;

  @Column({ type: 'int', default: 0 })
  coinCost: number;

  @Column({ type: 'boolean', default: false })
  isLocked: boolean;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @OneToMany(
    () => ServerChapterContent,
    (content) => content.mangaChapter,
  )
  contents: ServerChapterContent[];
}
