import { Injectable, OnModuleDestroy } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import puppeteer, { Browser, Page } from 'puppeteer';
import { CrawlLogger } from './logger';

/**
 * Browser Pool Manager
 * Reuse browser instances để tránh launch/close browser mỗi request
 */
@Injectable()
export class BrowserPoolService implements OnModuleDestroy {
  private browsers: Browser[] = [];
  private maxBrowsers: number;
  private browserInUse: Set<Browser> = new Set();
  private readonly browserOptions: any;

  constructor(private readonly configService: ConfigService) {
    this.maxBrowsers = this.configService.get<number>('app.maxBrowsers', 3);
    this.browserOptions = {
      headless: true,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-blink-features=AutomationControlled',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--disable-gpu',
      ],
    };
  }

  /**
   * Lấy browser từ pool hoặc tạo mới nếu chưa đủ
   */
  async acquire(): Promise<Browser> {
    // Tìm browser available
    for (const browser of this.browsers) {
      if (!this.browserInUse.has(browser) && browser.isConnected()) {
        this.browserInUse.add(browser);
        return browser;
      }
    }

    // Nếu chưa đủ max browsers, tạo mới
    if (this.browsers.length < this.maxBrowsers) {
      const browser = await puppeteer.launch(this.browserOptions);
      this.browsers.push(browser);
      this.browserInUse.add(browser);
      CrawlLogger.info(
        `[BrowserPool] Created new browser (total: ${this.browsers.length})`,
      );
      return browser;
    }

    // Nếu đã đủ, đợi browser available (simple wait)
    // Trong production có thể dùng queue thay vì wait
    await new Promise((resolve) => setTimeout(resolve, 100));
    return this.acquire();
  }

  /**
   * Trả browser về pool
   */
  release(browser: Browser): void {
    this.browserInUse.delete(browser);
  }

  /**
   * Tạo page mới từ browser
   */
  async createPage(browser: Browser): Promise<Page> {
    const page = await browser.newPage();
    return page;
  }

  /**
   * Helper: Sử dụng browser từ pool
   */
  async withBrowser<T>(callback: (browser: Browser) => Promise<T>): Promise<T> {
    const browser = await this.acquire();
    try {
      return await callback(browser);
    } finally {
      this.release(browser);
    }
  }

  /**
   * Helper: Sử dụng page từ pool
   */
  async withPage<T>(callback: (page: Page) => Promise<T>): Promise<T> {
    return this.withBrowser(async (browser) => {
      const page = await this.createPage(browser);
      try {
        return await callback(page);
      } finally {
        await page.close();
      }
    });
  }

  /**
   * Cleanup: đóng tất cả browsers
   */
  async closeAll(): Promise<void> {
    for (const browser of this.browsers) {
      if (browser.isConnected()) {
        await browser.close();
      }
    }
    this.browsers = [];
    this.browserInUse.clear();
    CrawlLogger.info('[BrowserPool] All browsers closed');
  }

  /**
   * Cleanup browser bị disconnect
   */
  async cleanupDisconnected(): Promise<void> {
    const connectedBrowsers: Browser[] = [];
    for (const browser of this.browsers) {
      if (browser.isConnected()) {
        connectedBrowsers.push(browser);
      } else {
        this.browserInUse.delete(browser);
      }
    }
    this.browsers = connectedBrowsers;
  }

  /**
   * Get pool stats (public access)
   */
  getStats(): { active: number; total: number; maxBrowsers: number } {
    return {
      active: this.browserInUse.size,
      total: this.browsers.length,
      maxBrowsers: this.maxBrowsers,
    };
  }

  async onModuleDestroy() {
    await this.closeAll();
  }
}
