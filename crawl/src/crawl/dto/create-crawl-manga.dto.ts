import { IsString, IsUrl, IsInt, IsOptional } from 'class-validator';

export class CreateCrawlMangaDto {
  @IsString()
  @IsUrl()
  crawlUrl: string;

  @IsInt()
  sourceId: number;

  @IsString()
  @IsOptional()
  title?: string;

  @IsString()
  @IsOptional()
  slug?: string;
}
