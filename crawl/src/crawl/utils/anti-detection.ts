import { Page } from 'puppeteer';

/**
 * Random user agents to avoid detection
 */
const USER_AGENTS = [
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:123.0) Gecko/20100101 Firefox/123.0',
];

/**
 * Get random user agent
 */
export function getRandomUserAgent(): string {
  return USER_AGENTS[Math.floor(Math.random() * USER_AGENTS.length)];
}

/**
 * Random delay between min and max milliseconds
 */
export function randomDelay(min: number = 1000, max: number = 3000): Promise<void> {
  const delay = Math.floor(Math.random() * (max - min + 1)) + min;
  return new Promise((resolve) => setTimeout(resolve, delay));
}

/**
 * Setup page with anti-detection measures
 */
export async function setupAntiDetectionPage(page: Page): Promise<void> {
  // Set random user agent
  await page.setUserAgent(getRandomUserAgent());

  // Set viewport to common desktop size
  await page.setViewport({
    width: 1920 + Math.floor(Math.random() * 100),
    height: 1080 + Math.floor(Math.random() * 100),
    deviceScaleFactor: 1,
  });

  // Set extra headers to mimic real browser
  await page.setExtraHTTPHeaders({
    Accept:
      'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language': 'en-US,en;q=0.9',
    'Accept-Encoding': 'gzip, deflate, br',
    DNT: '1',
    Connection: 'keep-alive',
    'Upgrade-Insecure-Requests': '1',
    'Sec-Fetch-Dest': 'document',
    'Sec-Fetch-Mode': 'navigate',
    'Sec-Fetch-Site': 'none',
    'Sec-Fetch-User': '?1',
    'Cache-Control': 'max-age=0',
  });

  // Override webdriver property
  await page.evaluateOnNewDocument(() => {
    Object.defineProperty(navigator, 'webdriver', {
      get: () => false,
    });
  });

  // Override plugins
  await page.evaluateOnNewDocument(() => {
    Object.defineProperty(navigator, 'plugins', {
      get: () => [1, 2, 3, 4, 5],
    });
  });

  // Override languages
  await page.evaluateOnNewDocument(() => {
    Object.defineProperty(navigator, 'languages', {
      get: () => ['en-US', 'en'],
    });
  });

  // Override permissions
  await page.evaluateOnNewDocument(() => {
    const originalQuery = (window as any).navigator.permissions?.query;
    (window as any).navigator.permissions = {
      ...(window as any).navigator.permissions,
      query: (parameters: any) => {
        if (parameters.name === 'notifications') {
          return Promise.resolve({
            state: (window as any).Notification?.permission || 'default',
          } as any);
        }
        return originalQuery
          ? originalQuery(parameters)
          : Promise.resolve({ state: 'granted' } as any);
      },
    };
  });

  // Add Chrome object
  await page.evaluateOnNewDocument(() => {
    (window as any).chrome = {
      runtime: {},
    };
  });
}

/**
 * Human-like mouse movement
 */
export async function humanLikeMouseMove(page: Page): Promise<void> {
  const viewport = page.viewport();
  if (!viewport) return;

  const steps = 5 + Math.floor(Math.random() * 5);
  for (let i = 0; i < steps; i++) {
    const x = Math.floor(Math.random() * viewport.width);
    const y = Math.floor(Math.random() * viewport.height);
    await page.mouse.move(x, y, { steps: 10 });
    await randomDelay(50, 150);
  }
}

/**
 * Human-like scroll
 */
export async function humanLikeScroll(page: Page): Promise<void> {
  const viewport = page.viewport();
  if (!viewport) return;

  const scrollSteps = 3 + Math.floor(Math.random() * 3);
  const scrollAmount = viewport.height / scrollSteps;

  for (let i = 0; i < scrollSteps; i++) {
    await page.evaluate((amount) => {
      window.scrollBy(0, amount);
    }, scrollAmount);
    await randomDelay(200, 500);
  }

  // Random scroll back up a bit
  await page.evaluate(() => {
    window.scrollBy(0, -Math.random() * 200);
  });
}

/**
 * Safe goto with anti-detection measures
 */
export async function safeGotoWithAntiDetection(
  page: Page,
  url: string,
  retries: number = 3,
  waitForSelector?: string,
): Promise<void> {
  for (let i = 0; i < retries; i++) {
    try {
      // Random delay before navigation
      await randomDelay(500, 1500);

      // Navigate with realistic options
      await page.goto(url, {
        waitUntil: 'networkidle2',
        timeout: 60000,
      });

      // Wait a bit for page to settle
      await randomDelay(1000, 2000);

      // Wait for specific selector if provided
      if (waitForSelector) {
        await page.waitForSelector(waitForSelector, { timeout: 10000 }).catch(() => {});
      }

      return;
    } catch (err: any) {
      if (i === retries - 1) throw err;

      // Exponential backoff
      const backoffDelay = Math.min(3000 * Math.pow(2, i), 10000);
      await new Promise((resolve) => setTimeout(resolve, backoffDelay));
    }
  }
}
