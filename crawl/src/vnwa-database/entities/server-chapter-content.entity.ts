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

  @Column({ type: 'int' })
  mangaServerId: number;

  @Column({ type: 'int' })
  mangaChapterId: number;

  @ManyToOne(() => MangaChapter, (chapter) => chapter.contents)
  @JoinColumn({ name: 'mangaChapterId' })
  mangaChapter: MangaChapter;

  @Column({ type: 'json' })
  urls: string[];

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;
}
