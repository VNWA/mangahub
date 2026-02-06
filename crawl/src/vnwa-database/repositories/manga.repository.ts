import { Injectable } from '@nestjs/common';
import { DataSource, Repository } from 'typeorm';
import { Manga } from '../entities/manga.entity';
import { InjectDataSource } from '@nestjs/typeorm';

@Injectable()
export class MangaRepository extends Repository<Manga> {
  constructor(
    @InjectDataSource('vnwa')
    private readonly dataSource: DataSource,
  ) {
    super(Manga, dataSource.createEntityManager());
  }

  async findOrCreateBySlug(
    slug: string,
    userId: number,
    data: Partial<Manga>,
  ): Promise<Manga> {
    let manga = await this.findOne({ where: { slug } });

    if (!manga) {
      manga = this.create({
        ...data,
        slug,
        userId,
      });
      manga = await this.save(manga);
    } else {
      // Update existing manga
      Object.assign(manga, { ...data, userId });
      manga = await this.save(manga);
    }

    return manga;
  }

  async updateLatestChapter(mangaId: number, latestChapterSlug: string): Promise<void> {
    // This is a placeholder - actual implementation would update latest chapter reference
    // Since Laravel doesn't have a direct latest_chapter field, this might need to be
    // handled differently based on your Laravel schema
  }
}
