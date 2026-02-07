<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head, Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, onMounted, computed } from 'vue';
import mangas from '@/routes/mangas';
import axios from '@/axios';
import {
    Network,
    Plus,
    Search,
    X,
    Edit,
    Trash2,
    Play,
    RefreshCw,
    MoreVertical,
    ExternalLink,
    AlertCircle,
    CheckCircle2,
    Clock,
    Loader2,
    View,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getStorageUrl } from '@/utils/storage';
import crawl from '@/routes/crawl';

const toast = useToast();

// API base URL - cấu hình trong .env hoặc hardcode
const CRAWL_API_BASE = import.meta.env.VITE_CRAWL_API_URL || 'http://localhost:3000';

interface CrawlSource {
    id: number;
    name: string;
    baseUrl: string;
    isActive: boolean;
}

interface CrawlManga {
    id: number;
    crawlUrl: string;
    title: string | null;
    slug: string | null;
    avatar: string;
    vnwaMangaId: number | null;
    latestChapter: string | null;
    status: 'pending' | 'crawling' | 'done' | 'error';
    lastCrawledAt: string | null;
    createdAt: string;
    updatedAt: string;
    source: CrawlSource;
}

interface PaginationMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface CrawlMangaResponse {
    data: CrawlManga[];
    meta: PaginationMeta;
    links: PaginationLink[];
}

interface CrawlJob {
    id: number;
    type: 'full' | 'update';
    status: 'pending' | 'running' | 'success' | 'failed';
    startedAt: string | null;
    finishedAt: string | null;
    errorMessage: string | null;
    createdAt: string;
}

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Crawl Management', href: '#' },
];

// State
const crawlMangas = ref<CrawlManga[]>([]);
const crawlSources = ref<CrawlSource[]>([]);
const crawlJobs = ref<CrawlJob[]>([]);
const isLoading = ref(false);
const searchQuery = ref('');
const selectedCrawlManga = ref<CrawlManga | null>(null);
const pagination = ref<PaginationMeta | null>(null);
const paginationLinks = ref<PaginationLink[]>([]);
const currentPage = ref(1);
const minioBaseUrl = import.meta.env.VITE_MINIO_URL || 'http://localhost:9000';

// Computed
const filteredCrawlMangas = computed(() => {
    if (!searchQuery.value) return crawlMangas.value;
    const query = searchQuery.value.toLowerCase();
    return crawlMangas.value.filter(
        (manga) =>
            manga.title?.toLowerCase().includes(query) ||
            manga.crawlUrl.toLowerCase().includes(query) ||
            manga.slug?.toLowerCase().includes(query)
    );
});

// Methods
const fetchCrawlMangas = async (page: number = 1) => {
    try {
        isLoading.value = true;
        const response = await axios.get<CrawlMangaResponse>(`${CRAWL_API_BASE}/api/crawl-mangas`, {
            params: { page, limit: 15 },
        });
        crawlMangas.value = response.data.data;
        pagination.value = response.data.meta;
        paginationLinks.value = response.data.links;
        currentPage.value = page;
    } catch (error: any) {
        toast.error('Không thể tải danh sách crawl mangas: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoading.value = false;
    }
};



const fetchCrawlSources = async () => {
    try {
        // Nếu API có endpoint để lấy sources
        // const response = await axios.get(`${CRAWL_API_BASE}/api/crawl-sources`);
        // crawlSources.value = response.data;

        // Tạm thời hardcode hoặc lấy từ crawl mangas
        const sources = new Map<number, CrawlSource>();
        crawlMangas.value.forEach((manga) => {
            if (manga.source && !sources.has(manga.source.id)) {
                sources.set(manga.source.id, manga.source);
            }
        });
        crawlSources.value = Array.from(sources.values());
    } catch (error: any) {
        console.error('Error fetching sources:', error);
    }
};

const fetchCrawlJobs = async () => {
    try {
        const response = await axios.get(`${CRAWL_API_BASE}/api/crawl-jobs?limit=10`);
        crawlJobs.value = response.data;
    } catch (error: any) {
        console.error('Error fetching jobs:', error);
    }
};



const handleDelete = async (id: number) => {
    if (!confirm('Bạn có chắc chắn muốn xóa crawl manga này?')) {
        return;
    }

    try {
        isLoading.value = true;
        await axios.delete(`${CRAWL_API_BASE}/api/crawl-mangas/${id}`);
        toast.success('Xóa crawl manga thành công');
        await fetchCrawlMangas();
    } catch (error: any) {
        toast.error('Không thể xóa crawl manga: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoading.value = false;
    }
};

const handleRunCrawl = async (id: number) => {
    try {
        isLoading.value = true;
        await axios.post(`${CRAWL_API_BASE}/api/crawl-mangas/${id}/run`);
        toast.success('Đã bắt đầu crawl detail và chapters');
        await fetchCrawlMangas();
        await fetchCrawlJobs();
    } catch (error: any) {
        toast.error('Không thể chạy crawl: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoading.value = false;
    }
};

const handleCrawlChapters = async (id: number) => {
    try {
        isLoading.value = true;
        await axios.post(`${CRAWL_API_BASE}/api/crawl-mangas/${id}/crawl-chapters`);
        toast.success('Đã bắt đầu crawl chapters');
        await fetchCrawlMangas();
        await fetchCrawlJobs();
    } catch (error: any) {
        toast.error('Không thể crawl chapters: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoading.value = false;
    }
};

const handleRunAll = async () => {
    if (!confirm('Bạn có chắc chắn muốn chạy crawl cho tất cả?')) {
        return;
    }

    try {
        isLoading.value = true;
        await axios.post(`${CRAWL_API_BASE}/api/crawl-mangas/run-all`);
        toast.success('Đã bắt đầu crawl tất cả');
        await fetchCrawlMangas();
        await fetchCrawlJobs();
    } catch (error: any) {
        toast.error('Không thể chạy crawl: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoading.value = false;
    }
};





const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    const variants: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        pending: 'outline',
        crawling: 'default',
        done: 'secondary',
        error: 'destructive',
    };
    return variants[status] || 'outline';
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        pending: 'Chờ xử lý',
        crawling: 'Đang crawl',
        done: 'Hoàn thành',
        error: 'Lỗi',
    };
    return labels[status] || status;
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'pending':
            return Clock;
        case 'crawling':
            return Loader2;
        case 'done':
            return CheckCircle2;
        case 'error':
            return AlertCircle;
        default:
            return Clock;
    }
};

const handleRefresh = async () => {
    try {
        isLoading.value = true;
        await Promise.all([
            fetchCrawlMangas(currentPage.value),
            fetchCrawlSources(),
            fetchCrawlJobs(),
        ]);
        toast.success('Đã tải lại dữ liệu');
    } catch (error: any) {
        toast.error('Không thể tải lại dữ liệu: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoading.value = false;
    }
};

// Lifecycle
onMounted(async () => {
    await fetchCrawlMangas(1);
    await fetchCrawlSources();
    await fetchCrawlJobs();
});
</script>

<template>


    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Quản lý Crawl" />

        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Quản lý Crawl</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ pagination?.total || crawlMangas.length }} mangas
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Quản lý và điều khiển crawl mangas từ NestJS service</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="handleRefresh" :disabled="isLoading">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isLoading }" />
                        Load lại
                    </Button>
                    <Button variant="outline" @click="handleRunAll" :disabled="isLoading">
                        <Play class="mr-2 h-4 w-4" />
                        Crawl Tất Cả
                    </Button>
                    <Link :href="crawl.create().url">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Thêm Crawl Manga
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchQuery" type="text" placeholder="Tìm kiếm crawl manga..."
                                class="pl-9 pr-9" />
                            <button v-if="searchQuery" @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Separator />

            <!-- Crawl Mangas List -->
            <div v-if="isLoading && crawlMangas.length === 0" class="flex items-center justify-center py-16">
                <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="filteredCrawlMangas.length > 0" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="manga in filteredCrawlMangas" :key="manga.id" :class="[
                        'group relative transition-all hover:shadow-lg',
                        manga.status === 'crawling' ? 'ring-2 ring-primary ring-offset-2' : '',
                        manga.status === 'pending' ? 'opacity-75' : ''
                    ]">
                        <CardContent class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div
                                            class="relative h-16 w-12 shrink-0 overflow-hidden rounded border bg-muted">
                                            <img :src="getStorageUrl(manga.avatar || '')" :alt="manga.title || 'Manga'"
                                                class="h-full w-full object-cover" />
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-semibold truncate">
                                                {{ manga.title || 'Chưa có tiêu đề' }}
                                            </h3>
                                            <p class="text-xs text-muted-foreground truncate">
                                                {{ manga.source.name }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-2 mb-3">
                                        <div class="flex items-center gap-2">
                                            <Badge :variant="getStatusBadgeVariant(manga.status)" class="text-xs">
                                                <component :is="getStatusIcon(manga.status)"
                                                    :class="['mr-1 h-3 w-3', manga.status === 'crawling' ? 'animate-spin' : '']" />
                                                {{ getStatusLabel(manga.status) }}
                                            </Badge>
                                        </div>

                                        <div class="text-xs text-muted-foreground space-y-1">
                                            <div class="flex items-center gap-1 truncate">
                                                <ExternalLink class="h-3 w-3 flex-shrink-0" />
                                                <span class="truncate">{{ manga.crawlUrl }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <Badge v-if="manga.vnwaMangaId" variant="outline" class="text-xs">
                                                    VNWA ID: {{ manga.vnwaMangaId }}
                                                </Badge>
                                                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                                                    Chưa sync VNWA
                                                </Badge>
                                            </div>
                                            <div v-if="manga.latestChapter" class="flex items-center gap-1">
                                                <span>Chapter mới nhất: {{ manga.latestChapter }}</span>
                                            </div>
                                            <div v-if="manga.lastCrawledAt" class="flex items-center gap-1">
                                                <span>Crawl lần cuối: {{ new
                                                    Date(manga.lastCrawledAt).toLocaleString('vi-VN') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0 flex-shrink-0">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem v-if="manga.status !== 'crawling'"
                                            @click="handleRunCrawl(manga.id)">
                                            <Play class="mr-2 h-4 w-4" />
                                            Crawl Detail
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="manga.status === 'done' && manga.vnwaMangaId"
                                            @click="handleCrawlChapters(manga.id)">
                                            <RefreshCw class="mr-2 h-4 w-4" />
                                            Crawl Chapters
                                        </DropdownMenuItem>

                                        <DropdownMenuItem v-if="manga.status !== 'crawling' && manga.vnwaMangaId"
                                            as-child>
                                            <Link :href="mangas.show(manga.vnwaMangaId).url">
                                                <View class="mr-2 h-4 w-4" />
                                                Xem chi tiết
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="manga.status !== 'crawling'" class="text-destructive"
                                            @click="handleDelete(manga.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Xóa
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Pagination -->
                <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                    <template v-for="(link, index) in paginationLinks" :key="index">
                        <Button v-if="link.url" variant="outline" size="sm" :disabled="link.active"
                            @click="fetchCrawlMangas(parseInt(link.url!.split('page=')[1]))"
                            :class="{ 'bg-primary text-primary-foreground': link.active }">
                            <span v-html="link.label" />
                        </Button>
                        <span v-else class="px-2 text-muted-foreground">
                            <span v-html="link.label" />
                        </span>
                    </template>
                </div>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-16">
                    <div class="rounded-full bg-muted p-6">
                        <Network class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có crawl manga nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Bắt đầu bằng cách thêm crawl manga đầu tiên
                        </p>
                    </div>
                    <Link :href="crawl.create().url">
                        <Button class="mt-4">
                            <Plus class="mr-2 h-4 w-4" />
                            Thêm Crawl Manga
                        </Button>
                    </Link>
                </CardContent>
            </Card>




        </div>
    </AppLayout>
</template>
