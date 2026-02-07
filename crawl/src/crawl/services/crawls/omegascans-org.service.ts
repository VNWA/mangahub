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
export class OmegascansOrgService {
  constructor(private readonly browserPool: BrowserPoolService) { }

  private resolveNextImageUrl(src: string): string {
    if (!src) return '';

    // nếu không phải _next/image thì trả luôn
    if (!src.includes('/_next/image')) {
      return src.startsWith('http')
        ? src
        : new URL(src, 'https://omegascans.org').href;
    }

    const urlObj = new URL(src, 'https://omegascans.org');
    const realUrl = urlObj.searchParams.get('url');

    return realUrl ? decodeURIComponent(realUrl) : '';
  }

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
        const name = $('h1').first().text().trim();
        // Use attribute selector to avoid CSS pseudo-class issues
        const other_name = $(
          'div[class*="flex"][class*="flex-col"][class*="gap-2"] span[class*="text-muted-foreground"][class*="text-base"]',
        )
          .first()
          .text()
          .trim() || '';
        let author_name = '';

        $('.space-y-2.rounded.p-5.bg-muted\\/10.w-full')
          .find('.flex.justify-between')
          .each((_, el) => {
            const label = $(el).find('span').first().text().trim();

            if (label === 'Author') {
              author_name = $(el).find('span').last().text().trim();
            }
          });
        let avatar =
          $('div[class*="p-1"][class*="bg-background"][class*="rounded"] img')
            .first()
            .attr('src') || '';

        avatar = this.resolveNextImageUrl(avatar);
        CrawlLogger.info(`Manga name: ${name}`);
        CrawlLogger.info(`Manga other name: ${other_name}`);
        CrawlLogger.info(`Manga avatar: ${avatar}`);
        callbacks.onMangaStart?.(name);

        // Extract description - use attribute selector to avoid CSS issues
        const description =
          $(
            'div[class*="col-span-12"][class*="h-full"] div[class*="text-muted-foreground"] div',
          )
            .first()
            .html() || '';

        // Helper function to extract chapters from current page
        const extractChaptersFromPage = (
          $: ReturnType<typeof cheerio.load>,
        ): MangaChapter[] => {
          const pageChapters: MangaChapter[] = [];
          $(
            'div[class*="space-y-4"][class*="py-5"] a[class*="text-foreground"]',
          ).each((_, el) => {
            const chapterUrl = $(el).attr('href') || '';
            // Try multiple ways to get chapter text
            let chapterText = $(el).find('span').first().text().trim();
            if (!chapterText) {
              chapterText = $(el).text().trim();
            }

            if (chapterUrl && chapterText) {
              const fullUrl = chapterUrl.startsWith('http')
                ? chapterUrl
                : `https://omegascans.org${chapterUrl.startsWith('/') ? '' : '/'}${chapterUrl}`;
              pageChapters.push({
                title: chapterText.trim(),
                url: fullUrl,
                view: 0,
                images: [],
              });
            }
          });
          return pageChapters;
        };

        const chapters: MangaChapter[] = [];
        let currentPage = 1;

        // Extract chapters from first page
        const firstPageChapters = extractChaptersFromPage($);
        chapters.push(...firstPageChapters);
        CrawlLogger.info(
          `Found ${firstPageChapters.length} chapters on page ${currentPage}`,
        );

        // Loop: check for Next button, click if exists, crawl chapters, repeat
        while (true) {
          // Check if Next button exists
          const hasNext = await page.evaluate(() => {
            const nav = document.querySelector(
              'nav[role="navigation"][aria-label="pagination"]',
            );
            if (nav) {
              // Look for Next button - could be in different places
              // Try to find link/button with text "Next" or "next" or arrow icon
              const nextLinks = nav.querySelectorAll('a');
              for (let i = 0; i < nextLinks.length; i++) {
                const link = nextLinks[i];
                const text = link.textContent?.toLowerCase().trim() || '';
                const hasNextText = text.includes('next') || text.includes('>');
                const isDisabled =
                  link.hasAttribute('disabled') ||
                  link.classList.contains('disabled') ||
                  link.getAttribute('aria-disabled') === 'true' ||
                  link.getAttribute('data-has-next') === 'false';

                // Check if it's a Next button (has text or arrow icon)
                if (hasNextText && !isDisabled) {
                  return true; // Found Next button
                }
              }

              // Alternative: check ul thứ 5 for clickable links
              const uls = nav.querySelectorAll('ul');
              if (uls.length > 4) {
                const linkUl = uls[4];
                const links = linkUl.querySelectorAll('a');
                for (let i = 0; i < links.length; i++) {
                  const link = links[i];
                  const isDisabled =
                    link.hasAttribute('disabled') ||
                    link.classList.contains('disabled') ||
                    link.getAttribute('aria-disabled') === 'true';
                  const isCurrentPage =
                    link.getAttribute('aria-current') === 'page';

                  if (!isDisabled && !isCurrentPage) {
                    return true; // Found clickable link
                  }
                }
              }
            }
            return false; // No Next button found
          });

          if (!hasNext) {
            CrawlLogger.info('No Next button found, stopping pagination');
            break;
          }

          // Click Next button
          CrawlLogger.info(
            `Clicking Next button to go to page ${currentPage + 1}...`,
          );
          await randomDelay(500, 1000);

          try {
            const clicked = await page.evaluate(() => {
              const nav = document.querySelector(
                'nav[role="navigation"][aria-label="pagination"]',
              );
              if (nav) {
                // Try to find and click Next button
                const nextLinks = nav.querySelectorAll('a');
                for (let i = 0; i < nextLinks.length; i++) {
                  const link = nextLinks[i];
                  const text = link.textContent?.toLowerCase().trim() || '';
                  const hasNextText = text.includes('next') || text.includes('>');
                  const isDisabled =
                    link.hasAttribute('disabled') ||
                    link.classList.contains('disabled') ||
                    link.getAttribute('aria-disabled') === 'true' ||
                    link.getAttribute('data-has-next') === 'false';

                  if (hasNextText && !isDisabled) {
                    (link as HTMLElement).click();
                    return true;
                  }
                }

                // Alternative: click first available link in ul thứ 5
                const uls = nav.querySelectorAll('ul');
                if (uls.length > 4) {
                  const linkUl = uls[4];
                  const links = linkUl.querySelectorAll('a');
                  for (let i = 0; i < links.length; i++) {
                    const link = links[i];
                    const isDisabled =
                      link.hasAttribute('disabled') ||
                      link.classList.contains('disabled') ||
                      link.getAttribute('aria-disabled') === 'true';
                    const isCurrentPage =
                      link.getAttribute('aria-current') === 'page';

                    if (!isDisabled && !isCurrentPage) {
                      (link as HTMLElement).click();
                      return true;
                    }
                  }
                }
              }
              return false;
            });

            if (!clicked) {
              CrawlLogger.info(
                'Could not click Next button, stopping pagination',
              );
              break;
            }

            // Wait for navigation and content to load
            await Promise.race([
              page.waitForNavigation({
                waitUntil: 'networkidle0',
                timeout: 30000,
              }),
              new Promise((resolve) => setTimeout(resolve, 3000)), // Fallback timeout
            ]).catch(() => {
              // Navigation might be instant or already complete
            });

            // Additional delay to ensure content is loaded
            await randomDelay(1500, 3000);

            // Re-parse the page content
            const $new = cheerio.load(await page.content());
            const pageChapters = extractChaptersFromPage($new);

            if (pageChapters.length === 0) {
              CrawlLogger.info(
                'No chapters found on this page, stopping pagination',
              );
              break;
            }

            currentPage++;
            chapters.push(...pageChapters);
            CrawlLogger.info(
              `Found ${pageChapters.length} chapters on page ${currentPage}`,
            );
          } catch (err: any) {
            CrawlLogger.error(`Error clicking Next button: ${err.message}`);
            break;
          }
        }

        const orderedChapters = chapters.reverse();
        CrawlLogger.info(
          `Found total ${orderedChapters.length} chapters across all pages (detail only, no images)`,
        );
        callbacks.onChaptersFound?.(orderedChapters.length);

        const detail: MangaSummary = {
          name,
          avatar,
          description,
          categories: [],
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

  /**
   * Crawl chapter images from chapter URL
   */
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

        $('div#content')
          .children('div.container')
          .find('div.flex.flex-col.justify-center.items-center img')
          .each((_, el) => {
            const $img = $(el);
            let src =
              $img.attr('src') ||
              $img.attr('data-src') ||
              $img.attr('data-lazy-src') ||
              $img.attr('data-original') ||
              '';

            if (src) {
              images.push(src);
            }
          });

        CrawlLogger.info(
          `Có ${images.length} ảnh cho chapter: ${chapterUrl}`,
        );
        return images;
      });
    } catch (err: any) {
      const errorMsg = err.message || 'Unknown error';
      CrawlLogger.error(`Error crawling chapter images: ${errorMsg}`);
      throw err;
    }
  }
}
