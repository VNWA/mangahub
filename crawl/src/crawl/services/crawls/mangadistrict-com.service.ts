import { Injectable } from '@nestjs/common';
import { Page } from 'puppeteer';
import * as cheerio from 'cheerio';
import { MangaSummary, MangaChapter } from '../../types/manga.types';
import { CrawlDetailOnlyCallbacks } from '../../types/crawl.types';
import { CrawlLogger } from '../../utils/logger';
import {
  setupAntiDetectionPage,
  safeGotoWithAntiDetection,
  randomDelay,
} from '../../utils/anti-detection';
import { BrowserPoolService } from '../../utils/browser-pool';

@Injectable()
export class MangadistrictComService {
  constructor(private readonly browserPool: BrowserPoolService) { }

  async crawlDetailOnly(
    url: string,
    callbacks: CrawlDetailOnlyCallbacks = {},
  ): Promise<MangaSummary> {
    try {
      return await this.browserPool.withPage(async (page: Page) => {
        // Setup anti-detection measures
        await setupAntiDetectionPage(page);

        // Setup request interception with better filtering
        await page.setRequestInterception(true);
        page.on('request', (req) => {
          const resourceType = req.resourceType();
          const url = req.url();

          // Block unnecessary resources but allow important ones
          if (['image', 'stylesheet', 'font', 'media'].includes(resourceType)) {
            req.abort();
          } else if (
            url.includes('analytics') ||
            url.includes('tracking') ||
            url.includes('advertisement')
          ) {
            req.abort();
          } else {
            req.continue();
          }
        });

        CrawlLogger.info(`Starting to crawl manga detail (no images): ${url}`);

        // Random delay before first request
        await randomDelay(1000, 2000);

        // Use safe goto with anti-detection
        await safeGotoWithAntiDetection(page, url, 3);

        // Additional random delay to mimic human reading
        await randomDelay(1500, 3000);

        const $ = cheerio.load(await page.content());

        // Extract title from h1
        const name =
          $('.summary_content .rate-title').text().trim() ||
          $('h1').text().trim();

        let other_name = '';
        $('div.summary_content div.post-content .post-content_item').each(
          (i, el) => {
            const title = $(el).find('.summary-heading h5').text().trim();

            if (title === 'Alternative') {
              other_name = $(el).find('.summary-content').text().trim();
            }
          },
        );

        const avatar = $('.summary_image img').attr('src') || '';
        const name_author = $('.summary-content .author-content a').text().trim() || '';
        const name_categories = $('.summary-content .genres-content a').map((_, el) => $(el).text().trim()).get() || [];
        const description =
          $('.description-summary .summary__content').html()?.trim() || '';

        CrawlLogger.info(`Manga mangadistrict_com name: ${name}`);
        CrawlLogger.info(`Manga mangadistrict_com other name: ${other_name}`);
        CrawlLogger.info(`Manga mangadistrict_com avatar: ${avatar}`);
        CrawlLogger.info(`Manga mangadistrict_com description: ${description}`);
        callbacks.onMangaStart?.(name);

        // Extract chapters - fix selector (use dots for classes)
        const chapters: MangaChapter[] = [];
        const baseUrl = new URL(url).origin;

        $('.main.version-chap li.wp-manga-chapter').each((_, el) => {
          const $link = $(el).find('a').first();
          let chapterUrl = $link.attr('href') || '';
          const chapterText = $link.text().trim();

          if (chapterUrl && chapterText) {
            chapters.push({
              title: chapterText.trim(),
              url: chapterUrl,
              view: 0,
              images: [],
            });
          }
        });

        const orderedChapters = chapters.reverse();
        CrawlLogger.info(
          `Found ${orderedChapters.length} chapters (detail only, no images)`,
        );
        callbacks.onChaptersFound?.(orderedChapters.length);

        const detail: MangaSummary = {
          name,
          avatar,
          description,
          author_name: name_author || undefined,
          categories: name_categories,
          chapters: orderedChapters,
        };

        callbacks.onSuccess?.();
        return detail;
      });
    } catch (err: any) {
      const errorMsg = err.message || 'Unknown error';
      CrawlLogger.error(`Error crawling detail: ${errorMsg}`);
      callbacks.onError?.(errorMsg);
      throw err;
    }
  }

  async crawlChapterImages(chapterUrl: string): Promise<string[]> {
    try {
      return await this.browserPool.withPage(async (page: Page) => {
        // Setup anti-detection measures
        await setupAntiDetectionPage(page);

        // Allow images to load for chapter pages, but block unnecessary resources
        await page.setRequestInterception(true);
        page.on('request', (req) => {
          const resourceType = req.resourceType();
          const url = req.url();

          // Block stylesheets and fonts, but allow images
          if (['stylesheet', 'font'].includes(resourceType)) {
            req.abort();
          } else if (
            url.includes('analytics') ||
            url.includes('tracking') ||
            url.includes('advertisement')
          ) {
            req.abort();
          } else {
            req.continue();
          }
        });

        CrawlLogger.info(`Starting to crawl chapter images: ${chapterUrl}`);

        // Random delay before request
        await randomDelay(1000, 2000);

        // Use safe goto with anti-detection
        await safeGotoWithAntiDetection(page, chapterUrl, 3);

        // Wait for images to load with random delay
        await randomDelay(2000, 4000);

        const $ = cheerio.load(await page.content());

        const images: string[] = [];

        // Try multiple selectors for chapter images (priority order)
        // Hiperdex structure: div.reading-content > div.page-break.no-gaps > img.wp-manga-chapter-img
        $('div.reading-content img.wp-manga-chapter-img').each((_, el) => {
          const $img = $(el);
          let src =
            $img.attr('data-src') ||
            $img.attr('data-lazy-src') ||
            $img.attr('data-original') ||
            $img.attr('src') ||
            '';

          if (!src || src === '' || src === '#') {
            return;
          }

          if (!src.startsWith('http')) {
            const baseUrl = new URL(chapterUrl).origin;
            src = src.startsWith('/') ? baseUrl + src : `${baseUrl}/${src}`;
          }

          images.push(src);
        });

        CrawlLogger.info(`Found ${images.length} images for chapter: ${chapterUrl}`);
        return images;
      });
    } catch (err: any) {
      const errorMsg = err.message || 'Unknown error';
      CrawlLogger.error(`Error crawling chapter images: ${errorMsg}`);
      throw err;
    }
  }
}
