import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import * as MinIO from 'minio';
import { CrawlLogger } from '../utils/logger';

@Injectable()
export class MinIOService {
  private readonly minioClient: MinIO.Client;
  private readonly bucket: string;
  private readonly baseUrl: string;

  constructor(private readonly configService: ConfigService) {
    const minioConfig = this.configService.get('minio');
    
    this.minioClient = new MinIO.Client({
      endPoint: new URL(minioConfig.endpoint).hostname,
      port: parseInt(new URL(minioConfig.endpoint).port || '9000', 10),
      useSSL: minioConfig.endpoint.startsWith('https'),
      accessKey: minioConfig.accessKeyId,
      secretKey: minioConfig.secretAccessKey,
    });

    this.bucket = minioConfig.bucket;
    this.baseUrl = minioConfig.url;

    this.ensureBucketExists();
  }

  private async ensureBucketExists(): Promise<void> {
    try {
      const exists = await this.minioClient.bucketExists(this.bucket);
      if (!exists) {
        await this.minioClient.makeBucket(this.bucket, 'us-east-1');
        CrawlLogger.info(`Created MinIO bucket: ${this.bucket}`);
      }
    } catch (error: any) {
      CrawlLogger.error(`Failed to ensure bucket exists: ${error.message}`);
    }
  }

  /**
   * Upload file to MinIO and return path (not full URL)
   */
  async uploadFile(
    buffer: Buffer,
    path: string,
    contentType: string = 'application/octet-stream',
  ): Promise<string> {
    try {
      await this.minioClient.putObject(
        this.bucket,
        path,
        buffer,
        buffer.length,
        {
          'Content-Type': contentType,
        },
      );

      CrawlLogger.info(`Uploaded file to MinIO: ${path}`);
      return path; // Return path only, not full URL
    } catch (error: any) {
      CrawlLogger.error(`Failed to upload file to MinIO: ${error.message}`);
      throw error;
    }
  }

  /**
   * Download file from URL and upload to MinIO
   */
  async downloadAndUpload(
    url: string,
    path: string,
    contentType?: string,
  ): Promise<string> {
    try {
      const response = await fetch(url, {
        headers: {
          'User-Agent': 'Mozilla/5.0',
          'Accept': 'image/*',
        },
      });

      if (!response.ok) {
        throw new Error(`Failed to download: ${response.statusText}`);
      }

      const arrayBuffer = await response.arrayBuffer();
      const buffer = Buffer.from(arrayBuffer);

      const detectedContentType =
        contentType || response.headers.get('content-type') || 'application/octet-stream';

      return await this.uploadFile(buffer, path, detectedContentType);
    } catch (error: any) {
      CrawlLogger.error(`Failed to download and upload: ${error.message}`);
      throw error;
    }
  }

  /**
   * Generate unique filename with date prefix
   * Format: prefix/YYYY-MM-DD_timestamp_random.extension
   */
  generateUniqueFilename(prefix: string, extension: string): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const dateStr = `${year}-${month}-${day}`;
    
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(2, 10);
    
    return `${prefix}/${dateStr}_${timestamp}_${random}.${extension}`;
  }
}
