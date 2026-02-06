import { Module } from '@nestjs/common';
import { BullModule } from '@nestjs/bullmq';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { ScheduleModule } from '@nestjs/schedule';
import { CrawlDatabaseModule } from '../crawl-database/crawl-database.module';
import { VnwaDatabaseModule } from '../vnwa-database/vnwa-database.module';
import { CrawlMangaController } from './controllers/crawl-manga.controller';
import { CrawlJobController } from './controllers/crawl-job.controller';
import { CrawlMangaService } from './services/crawl-manga.service';
import { CrawlJobService } from './services/crawl-job.service';
import { CrawlEngineService } from './services/crawl-engine.service';
import { PuppeteerService } from './services/puppeteer.service';
import { HtmlParserService } from './services/html-parser.service';
import { VnwaSyncService } from './services/vnwa-sync.service';
import { CrawlQueueService } from './queue/crawl-queue.service';
import { CrawlProcessor } from './queue/crawl.processor';
import { CrawlSchedulerService } from './scheduler/crawl-scheduler.service';

@Module({
  imports: [
    CrawlDatabaseModule,
    VnwaDatabaseModule,
    ScheduleModule.forRoot(),
    BullModule.forRootAsync({
      imports: [ConfigModule],
      useFactory: (configService: ConfigService) => {
        const redisConfig = configService.get('redis');
        return {
          connection: {
            host: redisConfig.host,
            port: redisConfig.port,
            password: redisConfig.password,
          },
        };
      },
      inject: [ConfigService],
    }),
    BullModule.registerQueue({
      name: 'crawl',
    }),
  ],
  controllers: [CrawlMangaController, CrawlJobController],
  providers: [
    CrawlMangaService,
    CrawlJobService,
    CrawlEngineService,
    PuppeteerService,
    HtmlParserService,
    VnwaSyncService,
    CrawlQueueService,
    CrawlProcessor,
    CrawlSchedulerService,
  ],
  exports: [CrawlQueueService, CrawlEngineService],
})
export class CrawlModule {}
