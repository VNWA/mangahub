import { Injectable } from '@nestjs/common';
import { CrawlServiceRegistry } from './crawls/crawl-service-registry';
import { MangaSummary } from '../types/manga.types';

@Injectable()
export class HtmlParserService {
  constructor(private readonly crawlServiceRegistry: CrawlServiceRegistry) {}

  async parseManga(url: string): Promise<MangaSummary> {
    const service = this.crawlServiceRegistry.getServiceByUrl(url);
    if (!service) {
      throw new Error(`No crawl service found for URL: ${url}`);
    }
    return service.crawlDetailOnly(url);
  }

  async parseChapters(url: string): Promise<any[]> {
    const manga = await this.parseManga(url);
    return manga.chapters;
  }

  async parseImages(chapterUrl: string): Promise<string[]> {
    const service = this.crawlServiceRegistry.getServiceByUrl(chapterUrl);
    if (!service) {
      throw new Error(`No crawl service found for URL: ${chapterUrl}`);
    }
    return service.crawlChapterImages(chapterUrl);
  }
}
