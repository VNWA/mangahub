import { Injectable } from '@nestjs/common';
import { DataSource, Repository } from 'typeorm';
import { MangaCategory } from '../entities/manga-category.entity';
import { InjectDataSource } from '@nestjs/typeorm';

@Injectable()
export class MangaCategoryRepository extends Repository<MangaCategory> {
  constructor(
    @InjectDataSource('vnwa')
    private readonly dataSource: DataSource,
  ) {
    super(MangaCategory, dataSource.createEntityManager());
  }

  async findOrCreateByName(name: string): Promise<MangaCategory> {
    if (!name || name.trim() === '') {
      throw new Error('Category name cannot be empty');
    }

    // Generate slug
    const slug = name
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '');

    // Try to find by slug first
    let category = await this.findOne({
      where: { slug },
    });

    // If not found, try to find by name (case insensitive)
    if (!category) {
      category = await this.createQueryBuilder('category')
        .where('LOWER(TRIM(category.name)) = LOWER(:name)', {
          name: name.trim(),
        })
        .getOne();
    }

    if (!category) {
      // Create new category
      category = this.create({
        name: name.trim(),
        slug: slug,
        description: null,
        avatar: null,
        icon: null,
      });
      category = await this.save(category);
    }

    return category;
  }
}
