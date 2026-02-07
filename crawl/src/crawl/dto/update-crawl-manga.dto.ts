import { IsString, IsUrl, IsInt, IsOptional, IsEnum } from 'class-validator';
import { CrawlMangaStatus } from '../../crawl-database/entities/crawl-manga.entity';

export class UpdateCrawlMangaDto {
  @IsString()
  @IsUrl()
  @IsOptional()
  crawlUrl?: string;

  @IsString()
  @IsOptional()
  title?: string;

  @IsString()
  @IsOptional()
  slug?: string;

  @IsInt()
  @IsOptional()
  vnwaMangaId?: number;

  @IsString()
  @IsOptional()
  latestChapter?: string;

  @IsEnum(CrawlMangaStatus)
  @IsOptional()
  status?: CrawlMangaStatus;
}
