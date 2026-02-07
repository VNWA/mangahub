export interface CrawlDetailOnlyCallbacks {
  onMangaStart?: (name: string) => void;
  onChaptersFound?: (total: number) => void;
  onSuccess?: () => void;
  onError?: (error: string) => void;
}

export interface CrawlService {
  crawlDetailOnly: (
    url: string,
    callbacks?: CrawlDetailOnlyCallbacks,
  ) => Promise<any>;
  crawlChapterImages: (chapterUrl: string) => Promise<string[]>;
}
