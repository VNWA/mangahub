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
export class Manga18MeService {
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
        const name = $('div.post-title').children('h1').first().text().trim();
        const other_name = $('.post-content_item.wleft')
          .filter((_, el) => {
            return $(el).find('.summary-heading h5').text().trim() === 'Alternative:';
          })
          .first()
          .find('.summary-content')
          .text()
          .trim() || '';

        const avatar = $('div.summary_image').children('img').first().attr('src') || '';
        const name_author = $('div.author-content a').first().text().trim() || '';
        const name_categories = $("div.genres-content a").map((_, el) => $(el).text().trim()).get() || [];
        const description =
          $('div.panel-story-description').children('div.ss-manga').html() || '';

        CrawlLogger.info(`Manga manga18_me name: ${name}`);
        CrawlLogger.info(`Manga manga18_me other name: ${other_name}`);
        CrawlLogger.info(`Manga manga18_me avatar: ${avatar}`);
        CrawlLogger.info(`Manga manga18_me description: ${description}`);
        callbacks.onMangaStart?.(name);

        // Extract chapters - fix selector (use dots for classes)
        const chapters: MangaChapter[] = [];
        const baseUrl = new URL(url).origin;

        $('ul.row-content-chapter')
          .children('li.a-h')
          .each((_, el) => {
            const $link = $(el).find('a.chapter-name').first();
            let chapterUrl = $link.attr('href') || '';
            const chapterText = $link.text().trim();

            // Convert relative URL to absolute
            if (chapterUrl && !chapterUrl.startsWith('http')) {
              chapterUrl = chapterUrl.startsWith('/')
                ? baseUrl + chapterUrl
                : `${baseUrl}/${chapterUrl}`;
            }

            if (chapterUrl && chapterText) {
              chapters.push({
                title: chapterText.trim(),
                url: chapterUrl,
                view: 0,
                images: [],
              });
            }
          });

        const orderedChapters = chapters;
        CrawlLogger.info(
          `Found ${orderedChapters.length} chapters (detail only, no images)`,
        );
        callbacks.onChaptersFound?.(orderedChapters.length);

        const detail: MangaSummary = {
          name,
          avatar,
          description,
          author_name: name_author || undefined,
          categories: name_categories || [],
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

        // Store images with their order for proper sorting
        interface ImageWithOrder {
          src: string;
          order: number;
        }
        const imagesWithOrder: ImageWithOrder[] = [];
        const seenUrls = new Set<string>();

        // Try multiple selectors for chapter images
        $('div.read-manga')
          .children('div.read-content')
          .children('img')
          .each((_, el) => {
            const $img = $(el);
            let src =
              $img.attr('data-src') ||
              $img.attr('data-lazy-src') ||
              $img.attr('data-original') ||
              $img.attr('src') ||
              '';

            // Trim whitespace
            src = src.trim();

            // Skip empty or invalid URLs
            if (!src || src === '' || src === '#') {
              return;
            }

            // Convert relative URLs to absolute
            if (!src.startsWith('http')) {
              const baseUrl = new URL(chapterUrl).origin;
              src = src.startsWith('/') ? baseUrl + src : `${baseUrl}/${src}`;
            }

            // Skip duplicates
            if (seenUrls.has(src)) {
              return;
            }

            // Filter out non-content images by URL pattern
            const lowerSrc = src.toLowerCase();
            if (
              lowerSrc.includes('logo') ||
              lowerSrc.includes('icon') ||
              lowerSrc.includes('avatar') ||
              lowerSrc.includes('banner') ||
              lowerSrc.includes('ad') ||
              lowerSrc.includes('sponsor') ||
              lowerSrc.includes('button') ||
              lowerSrc.includes('badge')
            ) {
              return;
            }

            // Filter out small images (likely ads/icons) - minimum 200px width/height
            const width = parseInt($img.attr('width') || '0');
            const height = parseInt($img.attr('height') || '0');

            // Only add if it's a reasonable size or no size specified
            if (width > 0 && height > 0 && (width < 200 || height < 200)) {
              return;
            }

            // Extract order from id attribute (image-0, image-1, etc.) or from filename
            let order = -1;
            const imgId = $img.attr('id') || '';
            const idMatch = imgId.match(/image-(\d+)/);
            if (idMatch) {
              order = parseInt(idMatch[1]);
            } else {
              // Fallback: extract from filename
              const filenameMatch = src.match(/(\d+)\.(jpg|jpeg|png|webp)$/i);
              if (filenameMatch) {
                order = parseInt(filenameMatch[1]);
              }
            }

            imagesWithOrder.push({ src, order });
            seenUrls.add(src);
          });

        // Fallback: get all images if no images found (only as last resort)
        if (imagesWithOrder.length === 0) {
          $('img').each((_, el) => {
            const $img = $(el);
            let src =
              $img.attr('data-src') ||
              $img.attr('data-lazy-src') ||
              $img.attr('data-original') ||
              $img.attr('src') ||
              '';

            // Trim whitespace
            src = src.trim();

            if (!src || src === '' || src === '#') {
              return;
            }

            if (!src.startsWith('http')) {
              const baseUrl = new URL(chapterUrl).origin;
              src = src.startsWith('/') ? baseUrl + src : `${baseUrl}/${src}`;
            }

            if (seenUrls.has(src)) {
              return;
            }

            const lowerSrc = src.toLowerCase();
            if (
              lowerSrc.includes('logo') ||
              lowerSrc.includes('icon') ||
              lowerSrc.includes('avatar') ||
              lowerSrc.includes('banner') ||
              lowerSrc.includes('ad') ||
              lowerSrc.includes('sponsor')
            ) {
              return;
            }

            const width = parseInt($img.attr('width') || '0');
            const height = parseInt($img.attr('height') || '0');

            if (width === 0 || height === 0 || (width > 200 && height > 200)) {
              imagesWithOrder.push({ src, order: -1 });
              seenUrls.add(src);
            }
          });
        }

        // Sort images by their order (id attribute or filename)
        imagesWithOrder.sort((a, b) => {
          // If both have valid order, sort by order
          if (a.order >= 0 && b.order >= 0) {
            return a.order - b.order;
          }
          // If only one has order, prioritize it
          if (a.order >= 0) return -1;
          if (b.order >= 0) return 1;
          // If neither has order, try to extract from URL
          const aMatch = a.src.match(/(\d+)\.(jpg|jpeg|png|webp)$/i);
          const bMatch = b.src.match(/(\d+)\.(jpg|jpeg|png|webp)$/i);
          if (aMatch && bMatch) {
            return parseInt(aMatch[1]) - parseInt(bMatch[1]);
          }
          return 0;
        });

        // Extract just the URLs in sorted order
        const images = imagesWithOrder.map((img) => img.src);

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
