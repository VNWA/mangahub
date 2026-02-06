import { Injectable, NotImplementedException } from '@nestjs/common';

@Injectable()
export class PuppeteerService {
  async init(): Promise<void> {
    throw new NotImplementedException('init is not implemented');
  }

  async close(): Promise<void> {
    throw new NotImplementedException('close is not implemented');
  }
}
