<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, computed } from 'vue';
import {
    BookOpen,
    Plus,
    Search,
    X,
    Edit,
    Trash2,
    Eye,
    FileText,
    Filter,
    MoreVertical,
    Star,
    Heart,
    TrendingUp,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import mangas from '@/routes/mangas';
import authors from '@/routes/authors';
import { getStorageUrl } from '@/utils/storage';

const toast = useToast();

interface Manga {
    id: number;
    name: string;
    slug: string;
    avatar?: string;
    description?: string;
    status: string;
    total_views: number;
    total_follow: number;
    rating: number;
    total_ratings: number;
    author?: {
        id: number;
        name: string;
    };
    badge?: {
        id: number;
        name: string;
        light_text_color: string;
        light_bg_color: string;
    };
    categories?: Array<{
        id: number;
        name: string;
    }>;
}

interface Props {
    mangas: {
        data: Manga[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    authors: Array<{ id: number; name: string }>;
    badges: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
    {
        title: 'Mangas',
        href: '#',
    },
]);

const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    const variants: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        ongoing: 'default',
        completed: 'secondary',
        hiatus: 'outline',
        cancelled: 'destructive',
    };
    return variants[status] || 'outline';
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        ongoing: 'Đang ra',
        completed: 'Hoàn thành',
        hiatus: 'Tạm ngưng',
        cancelled: 'Hủy',
    };
    return labels[status] || status;
};

const formatNumber = (num: number): string => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};

const handleSearch = () => {
    router.get(
        mangas.index().url,
        {
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleDelete = (mangaId: number) => {
    if (!confirm('Bạn có chắc chắn muốn xóa manga này? Hành động này không thể hoàn tác.')) {
        return;
    }

    router.delete(mangas.destroy(mangaId).url, {
        onSuccess: () => {
            toast.success('Manga đã được xóa thành công.');
        },
        onError: (errors) => {
            toast.error(errors.message || 'Có lỗi xảy ra khi xóa manga.');
        },
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    router.get(mangas.index().url, {}, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Quản lý Mangas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Quản lý Mangas</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ props.mangas.total }} manga
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Quản lý tất cả các manga trong hệ thống</p>
                </div>
                <Button as-child>
                    <Link :href="mangas.create().url">
                        <Plus class="mr-2 h-4 w-4" />
                        Thêm Manga
                    </Link>
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Tìm kiếm manga..."
                                class="pl-9 pr-9"
                                @keyup.enter="handleSearch"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <select
                                v-model="statusFilter"
                                class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                @change="handleSearch"
                            >
                                <option value="">Tất cả trạng thái</option>
                                <option value="ongoing">Đang ra</option>
                                <option value="completed">Hoàn thành</option>
                                <option value="hiatus">Tạm ngưng</option>
                                <option value="cancelled">Hủy</option>
                            </select>
                            <Button variant="outline" @click="handleSearch">
                                <Search class="mr-2 h-4 w-4" />
                                Tìm
                            </Button>
                            <Button
                                v-if="searchQuery || statusFilter"
                                variant="outline"
                                @click="clearFilters"
                            >
                                <X class="mr-2 h-4 w-4" />
                                Xóa
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Separator />

            <!-- Manga Table -->
            <div v-if="props.mangas.data.length > 0">
                <Card>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <!-- Table Header -->
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <!-- Avatar Column - Hiển thị ảnh đại diện của manga -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-16">
                                            <span class="sr-only">Ảnh</span>
                                        </th>

                                        <!-- Name Column - Tên manga, có thể thêm link để xem chi tiết -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">
                                            Tên Manga
                                        </th>

                                        <!-- Author Column - Tác giả của manga -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[150px]">
                                            Tác giả
                                        </th>

                                        <!-- Badge Column - Badge hiển thị trên manga (Hot, New, etc.) -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                                            Badge
                                        </th>

                                        <!-- Categories Column - Danh sách categories của manga -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[150px]">
                                            Categories
                                        </th>

                                        <!-- Status Column - Trạng thái (Đang ra, Hoàn thành, etc.) -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Trạng thái
                                        </th>

                                        <!-- Views Column - Tổng lượt xem -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                                            <div class="flex items-center gap-1">
                                                <Eye class="h-4 w-4" />
                                                Views
                                            </div>
                                        </th>

                                        <!-- Follows Column - Số lượt theo dõi -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                                            <div class="flex items-center gap-1">
                                                <Heart class="h-4 w-4" />
                                                Follows
                                            </div>
                                        </th>

                                        <!-- Rating Column - Đánh giá trung bình -->
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                                            <div class="flex items-center gap-1">
                                                <Star class="h-4 w-4" />
                                                Rating
                                            </div>
                                        </th>

                                        <!-- Actions Column - Các thao tác (Xem, Sửa, Xóa, Chapters) -->
                                        <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground w-32">
                                            Thao tác
                                        </th>

                                        <!-- 
                                            ĐỂ THÊM CỘT MỚI:
                                            1. Thêm <th> mới trong <thead> với comment mô tả
                                            2. Thêm <td> tương ứng trong <tbody> với dữ liệu từ manga object
                                            3. Cập nhật interface Manga nếu cần thêm field mới
                                            
                                            Ví dụ thêm cột "Ngày tạo":
                                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                                Ngày tạo
                                            </th>
                                            
                                            Và trong tbody:
                                            <td class="p-4 align-middle">
                                                {{ new Date(manga.created_at).toLocaleDateString('vi-VN') }}
                                            </td>
                                        -->
                                    </tr>
                                </thead>

                                <!-- Table Body -->
                                <tbody>
                                    <tr
                                        v-for="manga in props.mangas.data"
                                        :key="manga.id"
                                        class="border-b transition-colors hover:bg-muted/50"
                                    >
                                        <!-- Avatar Cell -->
                                        <td class="p-4 align-middle">
                                            <div class="relative h-16 w-12 shrink-0 overflow-hidden rounded border bg-muted">
                                                <img
                                                    v-if="manga.avatar"
                                                    :src="getStorageUrl(manga.avatar) || ''"
                                                    :alt="manga.name"
                                                    class="h-full w-full object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="flex h-full items-center justify-center"
                                                >
                                                    <BookOpen class="h-6 w-6 text-muted-foreground" />
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Name Cell -->
                                        <td class="p-4 align-middle">
                                            <div class="space-y-1">
                                                <Link
                                                    :href="mangas.show(manga.id).url"
                                                    class="font-medium hover:underline"
                                                >
                                                    {{ manga.name }}
                                                </Link>
                                                <p v-if="manga.description" class="text-xs text-muted-foreground line-clamp-1">
                                                    {{ manga.description }}
                                                </p>
                                            </div>
                                        </td>

                                        <!-- Author Cell -->
                                        <td class="p-4 align-middle">
                                            <div v-if="manga.author">
                                                <Link
                                                    :href="authors.show(manga.author.id).url"
                                                    class="text-sm hover:underline"
                                                >
                                                    {{ manga.author.name }}
                                                </Link>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>

                                        <!-- Badge Cell -->
                                        <td class="p-4 align-middle">
                                            <Badge
                                                v-if="manga.badge"
                                                :style="{
                                                    color: manga.badge.light_text_color,
                                                    backgroundColor: manga.badge.light_bg_color,
                                                }"
                                                class="text-xs font-semibold"
                                            >
                                                {{ manga.badge.name }}
                                            </Badge>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>

                                        <!-- Categories Cell -->
                                        <td class="p-4 align-middle">
                                            <div v-if="manga.categories && manga.categories.length > 0" class="flex flex-wrap gap-1">
                                                <Badge
                                                    v-for="category in manga.categories.slice(0, 2)"
                                                    :key="category.id"
                                                    variant="outline"
                                                    class="text-xs"
                                                >
                                                    {{ category.name }}
                                                </Badge>
                                                <Badge
                                                    v-if="manga.categories.length > 2"
                                                    variant="outline"
                                                    class="text-xs"
                                                >
                                                    +{{ manga.categories.length - 2 }}
                                                </Badge>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>

                                        <!-- Status Cell -->
                                        <td class="p-4 align-middle">
                                            <Badge :variant="getStatusBadgeVariant(manga.status)" class="text-xs">
                                                {{ getStatusLabel(manga.status) }}
                                            </Badge>
                                        </td>

                                        <!-- Views Cell -->
                                        <td class="p-4 align-middle">
                                            <div class="flex items-center gap-1 text-sm">
                                                <Eye class="h-3 w-3 text-muted-foreground" />
                                                <span>{{ formatNumber(manga.total_views) }}</span>
                                            </div>
                                        </td>

                                        <!-- Follows Cell -->
                                        <td class="p-4 align-middle">
                                            <div class="flex items-center gap-1 text-sm">
                                                <Heart class="h-3 w-3 text-muted-foreground" />
                                                <span>{{ formatNumber(manga.total_follow) }}</span>
                                            </div>
                                        </td>

                                        <!-- Rating Cell -->
                                        <td class="p-4 align-middle">
                                            <div v-if="manga.rating > 0" class="flex items-center gap-1 text-sm font-semibold">
                                                <Star class="h-3 w-3 text-yellow-500 fill-yellow-500" />
                                                <span>{{ manga.rating.toFixed(1) }}</span>
                                                <span class="text-xs text-muted-foreground">
                                                    ({{ manga.total_ratings }})
                                                </span>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>

                                        <!-- Actions Cell -->
                                        <td class="p-4 align-middle text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <Button variant="outline" size="sm" as-child>
                                                    <Link :href="mangas.chapters.index(manga.id).url">
                                                        <FileText class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger as-child>
                                                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                            <MoreVertical class="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem as-child>
                                                            <Link :href="mangas.show(manga.id).url">
                                                                <Eye class="mr-2 h-4 w-4" />
                                                                Xem chi tiết
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem as-child>
                                                            <Link :href="mangas.edit(manga.id).url">
                                                                <Edit class="mr-2 h-4 w-4" />
                                                                Chỉnh sửa
                                                            </Link>
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
                                        </td>

                                        <!-- 
                                            ĐỂ THÊM CELL MỚI TƯƠNG ỨNG VỚI CỘT ĐÃ THÊM:
                                            Thêm <td> mới với class="p-4 align-middle" và hiển thị dữ liệu
                                            
                                            Ví dụ thêm cell "Ngày tạo":
                                            <td class="p-4 align-middle">
                                                <span class="text-sm">
                                                    {{ new Date(manga.created_at).toLocaleDateString('vi-VN') }}
                                                </span>
                                            </td>
                                        -->
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="props.mangas.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                    <template v-for="(link, index) in props.mangas.links" :key="index">
                        <Button
                            v-if="link.url"
                            variant="outline"
                            size="sm"
                            :disabled="link.active"
                            @click="router.get(link.url!)"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                        >
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
                        <BookOpen class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có manga nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Bắt đầu bằng cách thêm manga đầu tiên của bạn
                        </p>
                    </div>
                    <Button class="mt-4" as-child>
                        <Link :href="mangas.create().url">
                            <Plus class="mr-2 h-4 w-4" />
                            Thêm Manga
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
