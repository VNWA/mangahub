import { Logger as NestLogger } from '@nestjs/common';

export class CrawlLogger {
  static info(message: string) {
    NestLogger.log(message, 'Crawl');
  }

  static success(message: string) {
    NestLogger.log(message, 'Crawl');
  }

  static warn(message: string) {
    NestLogger.warn(message, 'Crawl');
  }

  static error(message: string) {
    NestLogger.error(message, 'Crawl');
  }
}
