import { Injectable } from '@nestjs/common';
import { DataSource, Repository } from 'typeorm';
import { MangaAuthor } from '../entities/manga-author.entity';
import { InjectDataSource } from '@nestjs/typeorm';

@Injectable()
export class MangaAuthorRepository extends Repository<MangaAuthor> {
  constructor(
    @InjectDataSource('vnwa')
    private readonly dataSource: DataSource,
  ) {
    super(MangaAuthor, dataSource.createEntityManager());
  }

  async findOrCreateByName(name: string): Promise<MangaAuthor> {
    if (!name || name.trim() === '') {
      throw new Error('Author name cannot be empty');
    }

    // Generate slug
    const slug = name
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '');

    // Try to find by slug first
    let author = await this.findOne({
      where: { slug },
    });

    // If not found, try to find by name (case insensitive)
    if (!author) {
      author = await this.createQueryBuilder('author')
        .where('LOWER(TRIM(author.name)) = LOWER(:name)', {
          name: name.trim(),
        })
        .getOne();
    }

    if (!author) {
      // Create new author
      author = this.create({
        name: name.trim(),
        slug: slug,
        description: null,
        avatar: null,
      });
      author = await this.save(author);
    }

    return author;
  }
}
