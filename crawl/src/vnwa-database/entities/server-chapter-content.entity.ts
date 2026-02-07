import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { MangaChapter } from './manga-chapter.entity';

@Entity('server_chapter_contents')
export class ServerChapterContent {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ name: 'manga_server_id', type: 'int' })
  mangaServerId: number;

  @Column({ name: 'manga_chapter_id', type: 'int' })
  mangaChapterId: number;

  @ManyToOne(() => MangaChapter, (chapter) => chapter.contents)
  @JoinColumn({ name: 'manga_chapter_id' })
  mangaChapter: MangaChapter;

  @Column({ type: 'json' })
  urls: string[];

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;
}
