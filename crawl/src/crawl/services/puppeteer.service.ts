import { Injectable } from '@nestjs/common';
import { BrowserPoolService } from '../utils/browser-pool';

@Injectable()
export class PuppeteerService {
  constructor(private readonly browserPool: BrowserPoolService) {}

  async init(): Promise<void> {
    // Browser pool tự động khởi tạo khi cần
    // Không cần làm gì thêm
  }

  async close(): Promise<void> {
    await this.browserPool.closeAll();
  }

  getStats() {
    return this.browserPool.getStats();
  }
}
