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
    User,
    Plus,
    Search,
    X,
    Edit,
    Trash2,
    Eye,
    MoreVertical,
    BookOpen,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import authors from '@/routes/authors';

const toast = useToast();

interface Author {
    id: number;
    name: string;
    slug: string;
    avatar?: string;
    description?: string;
    mangas_count?: number;
}

interface Props {
    authors: {
        data: Author[];
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
    { title: 'Authors', href: '#' },
];

const handleSearch = () => {
    router.get(
        authors.index().url,
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleDelete = (authorId: number) => {
    if (!confirm('Bạn có chắc chắn muốn xóa author này?')) {
        return;
    }

    router.delete(authors.destroy(authorId).url, {
        onSuccess: () => {
            toast.success('Author đã được xóa thành công.');
        },
        onError: () => {
            toast.error('Có lỗi xảy ra khi xóa author.');
        },
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    router.get(authors.index().url, {}, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Quản lý Authors" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Quản lý Authors</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ props.authors.total }} authors
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Quản lý tất cả các tác giả manga</p>
                </div>
                <Button as-child>
                    <Link :href="authors.create().url">
                        <Plus class="mr-2 h-4 w-4" />
                        Thêm Author
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
                                placeholder="Tìm kiếm author..."
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

            <!-- Authors List -->
            <div v-if="props.authors.data.length > 0" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="author in props.authors.data"
                        :key="author.id"
                        class="group relative transition-all hover:shadow-lg"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-start gap-4">
                                <!-- Avatar -->
                                <div
                                    v-if="author.avatar"
                                    class="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-full border-2 bg-muted"
                                >
                                    <img
                                        :src="author.avatar"
                                        :alt="author.name"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div
                                    v-else
                                    class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full border-2 bg-muted"
                                >
                                    <User class="h-8 w-8 text-muted-foreground" />
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold truncate">{{ author.name }}</h3>
                                    <p
                                        v-if="author.description"
                                        class="text-sm text-muted-foreground line-clamp-2 mt-1"
                                    >
                                        {{ author.description }}
                                    </p>
                                    <div class="mt-3 flex items-center gap-2">
                                        <Badge variant="outline" class="text-xs">
                                            <BookOpen class="mr-1 h-3 w-3" />
                                            {{ author.mangas_count || 0 }} manga
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0 flex-shrink-0">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link :href="authors.show(author.id).url">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Xem chi tiết
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem as-child>
                                            <Link :href="authors.edit(author.id).url">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Chỉnh sửa
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive"
                                            @click="handleDelete(author.id)"
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
                <div v-if="props.authors.last_page > 1" class="flex items-center justify-center gap-2">
                    <template v-for="(link, index) in props.authors.links" :key="index">
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
                        <User class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có author nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Bắt đầu bằng cách thêm author đầu tiên
                        </p>
                    </div>
                    <Button class="mt-4" as-child>
                        <Link :href="authors.create().url">
                            <Plus class="mr-2 h-4 w-4" />
                            Thêm Author
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
