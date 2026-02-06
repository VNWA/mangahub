<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, onMounted, computed } from 'vue';
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
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Select } from '@/components/ui/select';

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
    vnwaMangaId: number | null;
    latestChapter: string | null;
    status: 'pending' | 'crawling' | 'done' | 'error';
    lastCrawledAt: string | null;
    createdAt: string;
    updatedAt: string;
    source: CrawlSource;
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
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const selectedCrawlManga = ref<CrawlManga | null>(null);

// Form data
const formData = ref({
    crawlUrl: '',
    sourceId: 0,
    title: '',
    slug: '',
});

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
const fetchCrawlMangas = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get(`${CRAWL_API_BASE}/api/crawl-mangas`);
        crawlMangas.value = response.data;
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

const handleCreate = async () => {
    if (!formData.value.crawlUrl || !formData.value.sourceId) {
        toast.error('Vui lòng điền đầy đủ thông tin');
        return;
    }

    try {
        isLoading.value = true;
        await axios.post(`${CRAWL_API_BASE}/api/crawl-mangas`, formData.value);
        toast.success('Tạo crawl manga thành công');
        isCreateDialogOpen.value = false;
        resetForm();
        await fetchCrawlMangas();
    } catch (error: any) {
        toast.error('Không thể tạo crawl manga: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoading.value = false;
    }
};

const handleUpdate = async () => {
    if (!selectedCrawlManga.value) return;

    try {
        isLoading.value = true;
        await axios.put(`${CRAWL_API_BASE}/api/crawl-mangas/${selectedCrawlManga.value.id}`, formData.value);
        toast.success('Cập nhật crawl manga thành công');
        isEditDialogOpen.value = false;
        resetForm();
        await fetchCrawlMangas();
    } catch (error: any) {
        toast.error('Không thể cập nhật crawl manga: ' + (error.response?.data?.message || error.message));
    } finally {
        isLoading.value = false;
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
        toast.success('Đã bắt đầu crawl');
        await fetchCrawlMangas();
        await fetchCrawlJobs();
    } catch (error: any) {
        toast.error('Không thể chạy crawl: ' + (error.response?.data?.message || error.message));
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

const openEditDialog = (manga: CrawlManga) => {
    selectedCrawlManga.value = manga;
    formData.value = {
        crawlUrl: manga.crawlUrl,
        sourceId: manga.source.id,
        title: manga.title || '',
        slug: manga.slug || '',
    };
    isEditDialogOpen.value = true;
};

const openCreateDialog = () => {
    resetForm();
    isCreateDialogOpen.value = true;
};

const resetForm = () => {
    formData.value = {
        crawlUrl: '',
        sourceId: 0,
        title: '',
        slug: '',
    };
    selectedCrawlManga.value = null;
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

// Lifecycle
onMounted(async () => {
    await fetchCrawlMangas();
    await fetchCrawlSources();
    await fetchCrawlJobs();
});
</script>

<template>
    <Head title="Quản lý Crawl" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Quản lý Crawl</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ crawlMangas.length }} mangas
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Quản lý và điều khiển crawl mangas từ NestJS service</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="handleRunAll" :disabled="isLoading">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Crawl Tất Cả
                    </Button>
                    <Button @click="openCreateDialog" :disabled="isLoading">
                        <Plus class="mr-2 h-4 w-4" />
                        Thêm Crawl Manga
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Tìm kiếm crawl manga..."
                                class="pl-9 pr-9"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
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
                    <Card
                        v-for="manga in filteredCrawlMangas"
                        :key="manga.id"
                        class="group relative transition-all hover:shadow-lg"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="rounded-lg bg-primary/10 p-2">
                                            <Network class="h-5 w-5 text-primary" />
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
                                                <component :is="getStatusIcon(manga.status)" class="mr-1 h-3 w-3" />
                                                {{ getStatusLabel(manga.status) }}
                                            </Badge>
                                        </div>

                                        <div class="text-xs text-muted-foreground space-y-1">
                                            <div class="flex items-center gap-1 truncate">
                                                <ExternalLink class="h-3 w-3 flex-shrink-0" />
                                                <span class="truncate">{{ manga.crawlUrl }}</span>
                                            </div>
                                            <div v-if="manga.vnwaMangaId" class="flex items-center gap-1">
                                                <span>VNWA ID: {{ manga.vnwaMangaId }}</span>
                                            </div>
                                            <div v-if="manga.latestChapter" class="flex items-center gap-1">
                                                <span>Chapter mới nhất: {{ manga.latestChapter }}</span>
                                            </div>
                                            <div v-if="manga.lastCrawledAt" class="flex items-center gap-1">
                                                <span>Crawl lần cuối: {{ new Date(manga.lastCrawledAt).toLocaleString('vi-VN') }}</span>
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
                                        <DropdownMenuItem @click="handleRunCrawl(manga.id)">
                                            <Play class="mr-2 h-4 w-4" />
                                            Chạy Crawl
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="openEditDialog(manga)">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Chỉnh sửa
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive"
                                            @click="handleDelete(manga.id)"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Xóa
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </CardContent>
                    </Card>
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
                    <Button class="mt-4" @click="openCreateDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        Thêm Crawl Manga
                    </Button>
                </CardContent>
            </Card>

            <!-- Create Dialog -->
            <Dialog v-model:open="isCreateDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Thêm Crawl Manga</DialogTitle>
                        <DialogDescription>
                            Thêm một crawl manga mới vào hệ thống
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="crawlUrl">Crawl URL *</Label>
                            <Input
                                id="crawlUrl"
                                v-model="formData.crawlUrl"
                                placeholder="https://example.com/manga/123"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="sourceId">Source *</Label>
                            <Select
                                id="sourceId"
                                v-model="formData.sourceId"
                                :options="[
                                    { value: 0, label: 'Chọn source' },
                                    ...crawlSources.map((source) => ({
                                        value: source.id,
                                        label: source.name,
                                    })),
                                ]"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="title">Title (tùy chọn)</Label>
                            <Input
                                id="title"
                                v-model="formData.title"
                                placeholder="Tên manga"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="slug">Slug (tùy chọn)</Label>
                            <Input
                                id="slug"
                                v-model="formData.slug"
                                placeholder="slug-manga"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isCreateDialogOpen = false">Hủy</Button>
                        <Button @click="handleCreate" :disabled="isLoading">
                            <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                            Tạo
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Edit Dialog -->
            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Chỉnh sửa Crawl Manga</DialogTitle>
                        <DialogDescription>
                            Cập nhật thông tin crawl manga
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="edit-crawlUrl">Crawl URL *</Label>
                            <Input
                                id="edit-crawlUrl"
                                v-model="formData.crawlUrl"
                                placeholder="https://example.com/manga/123"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-sourceId">Source *</Label>
                            <Select
                                id="edit-sourceId"
                                v-model="formData.sourceId"
                                :options="[
                                    { value: 0, label: 'Chọn source' },
                                    ...crawlSources.map((source) => ({
                                        value: source.id,
                                        label: source.name,
                                    })),
                                ]"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-title">Title</Label>
                            <Input
                                id="edit-title"
                                v-model="formData.title"
                                placeholder="Tên manga"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-slug">Slug</Label>
                            <Input
                                id="edit-slug"
                                v-model="formData.slug"
                                placeholder="slug-manga"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isEditDialogOpen = false">Hủy</Button>
                        <Button @click="handleUpdate" :disabled="isLoading">
                            <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                            Cập nhật
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
