import { Injectable, NotImplementedException } from '@nestjs/common';

@Injectable()
export class HtmlParserService {
  async parseManga(html: string, url: string): Promise<any> {
    throw new NotImplementedException('parseManga is not implemented');
  }

  async parseChapters(html: string, url: string): Promise<any[]> {
    throw new NotImplementedException('parseChapters is not implemented');
  }

  async parseImages(html: string, url: string): Promise<string[]> {
    throw new NotImplementedException('parseImages is not implemented');
  }
}
