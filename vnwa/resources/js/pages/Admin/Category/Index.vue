<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref } from 'vue';
import {
    FolderTree,
    Plus,
    Search,
    X,
    Edit,
    Trash2,
    Eye,
    MoreVertical,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import categories from '@/routes/categories';

const toast = useToast();

interface Category {
    id: number;
    name: string;
    slug: string;
    icon?: string;
    description?: string;
    mangas_count?: number;
}

interface Props {
    categories: {
        data: Category[];
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
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Categories', href: categories.index().url },
];

const handleSearch = () => {
    router.get(
        categories.index().url,
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleDelete = (categoryId: number) => {
    if (!confirm('Bạn có chắc chắn muốn xóa category này?')) {
        return;
    }

    router.delete(categories.destroy(categoryId).url, {
        onSuccess: () => {
            toast.success('Category đã được xóa thành công.');
        },
        onError: () => {
            toast.error('Có lỗi xảy ra khi xóa category.');
        },
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    router.get(categories.index().url, {}, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Quản lý Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Quản lý Categories</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ props.categories.total }} categories
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Quản lý tất cả các thể loại manga</p>
                </div>
                <Button as-child>
                    <Link :href="categories.create().url">
                        <Plus class="mr-2 h-4 w-4" />
                        Thêm Category
                    </Link>
                </Button>
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
                                placeholder="Tìm kiếm category..."
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
                        <Button variant="outline" @click="handleSearch">
                            <Search class="mr-2 h-4 w-4" />
                            Tìm
                        </Button>
                        <Button
                            v-if="searchQuery"
                            variant="outline"
                            @click="clearFilters"
                        >
                            <X class="mr-2 h-4 w-4" />
                            Xóa
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Separator />

            <!-- Categories List -->
            <div v-if="props.categories.data.length > 0" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="category in props.categories.data"
                        :key="category.id"
                        class="group relative transition-all hover:shadow-lg"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span v-if="category.icon" class="text-2xl">{{ category.icon }}</span>
                                        <h3 class="text-lg font-semibold">{{ category.name }}</h3>
                                    </div>
                                    <p v-if="category.description" class="text-sm text-muted-foreground line-clamp-2 mb-3">
                                        {{ category.description }}
                                    </p>
                                    <Badge variant="outline" class="text-xs">
                                        {{ category.mangas_count || 0 }} manga
                                    </Badge>
                                </div>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link :href="categories.show(category.id).url">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Xem chi tiết
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem as-child>
                                            <Link :href="categories.edit(category.id).url">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Chỉnh sửa
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive"
                                            @click="handleDelete(category.id)"
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

                <!-- Pagination -->
                <div v-if="props.categories.last_page > 1" class="flex items-center justify-center gap-2">
                    <template v-for="(link, index) in props.categories.links" :key="index">
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
                        <FolderTree class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có category nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Bắt đầu bằng cách thêm category đầu tiên
                        </p>
                    </div>
                    <Button class="mt-4" as-child>
                        <Link :href="categories.create().url">
                            <Plus class="mr-2 h-4 w-4" />
                            Thêm Category
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
