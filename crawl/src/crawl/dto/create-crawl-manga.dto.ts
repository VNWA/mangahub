import { IsString, IsUrl, IsOptional } from 'class-validator';

export class CreateCrawlMangaDto {
  @IsString()
  @IsUrl()
  crawlUrl: string;

  @IsString()
  @IsOptional()
  title?: string;

  @IsString()
  @IsOptional()
  slug?: string;
}
