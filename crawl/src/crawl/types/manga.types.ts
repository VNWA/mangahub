export interface MangaChapter {
  title: string;
  url: string;
  images?: string[];
  view: number;
}

export interface MangaSummary {
  name: string;
  avatar: string;
  description: string;
  status?: string; // 'ongoing' | 'completed' | 'hiatus' | 'cancelled'
  author_name?: string; // Single author name for linking to manga_author_id
  categories?: string[]; // For linking to manga_category_manga (genres)
  chapters: MangaChapter[];
}
