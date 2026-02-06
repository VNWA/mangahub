import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { Manga } from './entities/manga.entity';
import { MangaChapter } from './entities/manga-chapter.entity';
import { ServerChapterContent } from './entities/server-chapter-content.entity';
import { MangaRepository } from './repositories/manga.repository';
import { MangaChapterRepository } from './repositories/manga-chapter.repository';
import { ServerChapterContentRepository } from './repositories/server-chapter-content.repository';

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
      [Manga, MangaChapter, ServerChapterContent],
      'vnwa',
    ),
  ],
  providers: [
    MangaRepository,
    MangaChapterRepository,
    ServerChapterContentRepository,
  ],
  exports: [
    TypeOrmModule,
    MangaRepository,
    MangaChapterRepository,
    ServerChapterContentRepository,
  ],
})
export class VnwaDatabaseModule {}
