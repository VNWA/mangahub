import { Injectable } from '@nestjs/common';
import sharp from 'sharp';
import { CrawlLogger } from '../utils/logger';

@Injectable()
export class ImageProcessingService {
  /**
   * Resize and convert image to WebP format
   */
  async processAvatar(
    imageBuffer: Buffer,
    width: number = 300,
    height: number = 400,
  ): Promise<Buffer> {
    try {
      const processed = await sharp(imageBuffer)
        .resize(width, height, {
          fit: 'cover',
          position: 'center',
        })
        .webp({ quality: 85 })
        .toBuffer();

      CrawlLogger.info(`Processed avatar: ${width}x${height} WebP`);
      return processed;
    } catch (error: any) {
      CrawlLogger.error(`Failed to process avatar: ${error.message}`);
      throw error;
    }
  }

  /**
   * Convert image to WebP (for chapter images)
   */
  async convertToWebP(imageBuffer: Buffer, quality: number = 90): Promise<Buffer> {
    try {
      const processed = await sharp(imageBuffer)
        .webp({ quality })
        .toBuffer();

      return processed;
    } catch (error: any) {
      CrawlLogger.error(`Failed to convert to WebP: ${error.message}`);
      throw error;
    }
  }

  /**
   * Download image from URL
   */
  async downloadImage(url: string): Promise<Buffer> {
    try {
      const response = await fetch(url, {
        headers: {
          'User-Agent': 'Mozilla/5.0',
          'Accept': 'image/*',
          'Referer': new URL(url).origin,
        },
      });

      if (!response.ok) {
        throw new Error(`Failed to download image: ${response.statusText}`);
      }

      const arrayBuffer = await response.arrayBuffer();
      return Buffer.from(arrayBuffer);
    } catch (error: any) {
      CrawlLogger.error(`Failed to download image: ${error.message}`);
      throw error;
    }
  }

  /**
   * Process avatar: download, resize, convert to WebP
   */
  async processAvatarFromUrl(
    url: string,
    width: number = 300,
    height: number = 400,
  ): Promise<Buffer> {
    const imageBuffer = await this.downloadImage(url);
    return await this.processAvatar(imageBuffer, width, height);
  }
}
