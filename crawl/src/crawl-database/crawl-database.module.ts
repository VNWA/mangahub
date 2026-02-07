import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { CrawlManga } from './entities/crawl-manga.entity';
import { CrawlJob } from './entities/crawl-job.entity';

@Module({
  imports: [
    TypeOrmModule.forRootAsync({
      name: 'crawl',
      imports: [ConfigModule],
      useFactory: (configService: ConfigService) => {
        const dbConfig = configService.get('database.crawl');
        return {
          ...dbConfig,
          type: 'postgres',
        };
      },
      inject: [ConfigService],
    }),
    TypeOrmModule.forFeature(
      [CrawlManga, CrawlJob],
      'crawl',
    ),
  ],
  exports: [TypeOrmModule],
})
export class CrawlDatabaseModule {}
