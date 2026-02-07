import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { Manga } from './entities/manga.entity';
import { MangaChapter } from './entities/manga-chapter.entity';
import { ServerChapterContent } from './entities/server-chapter-content.entity';
import { MangaAuthor } from './entities/manga-author.entity';
import { MangaCategory } from './entities/manga-category.entity';
import { MangaRepository } from './repositories/manga.repository';
import { MangaChapterRepository } from './repositories/manga-chapter.repository';
import { ServerChapterContentRepository } from './repositories/server-chapter-content.repository';
import { MangaAuthorRepository } from './repositories/manga-author.repository';
import { MangaCategoryRepository } from './repositories/manga-category.repository';

@Module({
  imports: [
    TypeOrmModule.forRootAsync({
      name: 'vnwa',
      imports: [ConfigModule],
      useFactory: (configService: ConfigService) => {
        const dbConfig = configService.get('database.vnwa');
        return {
          ...dbConfig,
          type: 'postgres',
        };
      },
      inject: [ConfigService],
    }),
    TypeOrmModule.forFeature(
      [Manga, MangaChapter, ServerChapterContent, MangaAuthor, MangaCategory],
      'vnwa',
    ),
  ],
  providers: [
    MangaRepository,
    MangaChapterRepository,
    ServerChapterContentRepository,
    MangaAuthorRepository,
    MangaCategoryRepository,
  ],
  exports: [
    TypeOrmModule,
    MangaRepository,
    MangaChapterRepository,
    ServerChapterContentRepository,
    MangaAuthorRepository,
    MangaCategoryRepository,
  ],
})
export class VnwaDatabaseModule {}
