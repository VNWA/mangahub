import { Injectable } from '@nestjs/common';
import { CrawlService } from '../../types/crawl.types';
import { Manga18MeService } from './manga18-me.service';
import { MangadistrictComService } from './mangadistrict-com.service';
import { OmegascansOrgService } from './omegascans-org.service';

@Injectable()
export class CrawlServiceRegistry {
  private services: Map<string, CrawlService> = new Map();

  constructor(
    private readonly manga18MeService: Manga18MeService,
    private readonly mangadistrictComService: MangadistrictComService,
    private readonly omegascansOrgService: OmegascansOrgService,
  ) {
    this.registerServices();
  }

  private registerServices(): void {
    // Register manga18.me service
    this.services.set('manga18.me', {
      crawlDetailOnly: (url: string, callbacks?: any) =>
        this.manga18MeService.crawlDetailOnly(url, callbacks),
      crawlChapterImages: (chapterUrl: string) =>
        this.manga18MeService.crawlChapterImages(chapterUrl),
    });

    // Register mangadistrict.com service
    this.services.set('mangadistrict.com', {
      crawlDetailOnly: (url: string, callbacks?: any) =>
        this.mangadistrictComService.crawlDetailOnly(url, callbacks),
      crawlChapterImages: (chapterUrl: string) =>
        this.mangadistrictComService.crawlChapterImages(chapterUrl),
    });

    // Register omegascans.org service
    this.services.set('omegascans.org', {
      crawlDetailOnly: (url: string, callbacks?: any) =>
        this.omegascansOrgService.crawlDetailOnly(url, callbacks),
      crawlChapterImages: (chapterUrl: string) =>
        this.omegascansOrgService.crawlChapterImages(chapterUrl),
    });
  }

  /**
   * Get crawl service by domain
   */
  getService(domain: string): CrawlService | null {
    return this.services.get(domain) || null;
  }

  /**
   * Get service by URL (extracts domain from URL)
   */
  getServiceByUrl(url: string): CrawlService | null {
    try {
      const domain = new URL(url).hostname.replace('www.', '');
      return this.getService(domain);
    } catch {
      return null;
    }
  }

  /**
   * List all registered services
   */
  listServices(): string[] {
    return Array.from(this.services.keys());
  }
}
