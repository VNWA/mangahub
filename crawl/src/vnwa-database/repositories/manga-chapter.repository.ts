import { Injectable } from '@nestjs/common';
import { DataSource, Repository } from 'typeorm';
import { MangaChapter } from '../entities/manga-chapter.entity';
import { InjectDataSource } from '@nestjs/typeorm';

@Injectable()
export class MangaChapterRepository extends Repository<MangaChapter> {
  constructor(
    @InjectDataSource('vnwa')
    private readonly dataSource: DataSource,
  ) {
    super(MangaChapter, dataSource.createEntityManager());
  }

  async findOrCreateBySlug(
    mangaId: number,
    slug: string,
    userId: number,
    data: Partial<MangaChapter>,
  ): Promise<MangaChapter> {
    let chapter = await this.findOne({
      where: { mangaId, slug },
    });

    if (!chapter) {
      chapter = this.create({
        ...data,
        mangaId,
        slug,
        userId,
      });
      chapter = await this.save(chapter);
    } else {
      // Update existing chapter
      Object.assign(chapter, { ...data, userId });
      chapter = await this.save(chapter);
    }

    return chapter;
  }

  async getLatestChapter(mangaId: number): Promise<MangaChapter | null> {
    return this.findOne({
      where: { mangaId },
      order: { order: 'DESC', createdAt: 'DESC' },
    });
  }
}
