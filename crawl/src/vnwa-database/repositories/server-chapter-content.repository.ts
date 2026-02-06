import { Injectable } from '@nestjs/common';
import { DataSource, Repository } from 'typeorm';
import { ServerChapterContent } from '../entities/server-chapter-content.entity';
import { InjectDataSource } from '@nestjs/typeorm';

@Injectable()
export class ServerChapterContentRepository extends Repository<ServerChapterContent> {
  constructor(
    @InjectDataSource('vnwa')
    private readonly dataSource: DataSource,
  ) {
    super(ServerChapterContent, dataSource.createEntityManager());
  }

  async upsertContent(
    mangaChapterId: number,
    mangaServerId: number,
    urls: string[],
  ): Promise<ServerChapterContent> {
    let content = await this.findOne({
      where: { mangaChapterId, mangaServerId },
    });

    if (!content) {
      content = this.create({
        mangaChapterId,
        mangaServerId,
        urls,
      });
      content = await this.save(content);
    } else {
      content.urls = urls;
      content = await this.save(content);
    }

    return content;
  }
}
