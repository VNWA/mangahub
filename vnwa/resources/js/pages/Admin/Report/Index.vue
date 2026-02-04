<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, computed } from 'vue';
import { Search, X, Eye, Trash2, AlertTriangle, BookOpen, FileText, TrendingUp } from 'lucide-vue-next';
import { dashboard } from '@/routes';

const toast = useToast();

interface Report {
    id: number;
    reason: string;
    description?: string;
    status: string;
    created_at: string;
    reviewed_at?: string;
    admin_note?: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    reviewer?: {
        id: number;
        name: string;
    };
    reportable_type: string;
    reportable?: {
        id: number;
        name?: string;
        title?: string;
        slug?: string;
    };
}

interface Props {
    reports: {
        data: Report[];
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
    stats: {
        total: number;
        pending: number;
        reviewed: number;
        resolved: number;
        rejected: number;
    };
    filters: {
        search?: string;
        status?: string;
        type?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const typeFilter = ref(props.filters.type || '');

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Reports',
        href: '#',
    },
]);

const getStatusVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        pending: 'destructive',
        reviewed: 'secondary',
        resolved: 'default',
        rejected: 'outline',
    };
    return variants[status] || 'secondary';
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'Chờ xử lý',
        reviewed: 'Đã xem',
        resolved: 'Đã xử lý',
        rejected: 'Từ chối',
    };
    return labels[status] || status;
};

const getReasonLabel = (reason: string) => {
    const labels: Record<string, string> = {
        spam: 'Spam',
        inappropriate: 'Nội dung không phù hợp',
        copyright: 'Vi phạm bản quyền',
        other: 'Khác',
    };
    return labels[reason] || reason;
};

const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        'App\\Models\\Manga': 'Manga',
        'App\\Models\\MangaChapter': 'Chapter',
    };
    return labels[type] || type;
};

const handleSearch = () => {
    router.get(
        '/admin/reports',
        {
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
            type: typeFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    typeFilter.value = '';
    router.get('/admin/reports', {}, { preserveState: true, replace: true });
};

const handleDelete = (reportId: number) => {
    if (!confirm('Bạn có chắc chắn muốn xóa report này?')) {
        return;
    }

    router.delete(`/admin/reports/${reportId}`, {
        onSuccess: () => {
            toast.success('Đã xóa report thành công');
        },
        onError: () => {
            toast.error('Có lỗi xảy ra khi xóa report');
        },
    });
};
</script>

<template>
    <Head title="Quản lý Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Quản lý Reports</h1>
                    <p class="text-muted-foreground">Quản lý các báo cáo từ người dùng</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-5">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Tổng số</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Chờ xử lý</CardTitle>
                        <TrendingUp class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.pending }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Đã xem</CardTitle>
                        <FileText class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ stats.reviewed }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Đã xử lý</CardTitle>
                        <BookOpen class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.resolved }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Từ chối</CardTitle>
                        <X class="h-4 w-4 text-gray-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-gray-600">{{ stats.rejected }}</div>
                    </CardContent>
                </Card>
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
                                placeholder="Tìm kiếm user..."
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
                            <Select
                                v-model="statusFilter"
                                :options="[
                                    { value: '', label: 'Tất cả trạng thái' },
                                    { value: 'pending', label: 'Chờ xử lý' },
                                    { value: 'reviewed', label: 'Đã xem' },
                                    { value: 'resolved', label: 'Đã xử lý' },
                                    { value: 'rejected', label: 'Từ chối' },
                                ]"
                                @update:modelValue="handleSearch"
                            />
                            <Select
                                v-model="typeFilter"
                                :options="[
                                    { value: '', label: 'Tất cả loại' },
                                    { value: 'App\\Models\\Manga', label: 'Manga' },
                                    { value: 'App\\Models\\MangaChapter', label: 'Chapter' },
                                ]"
                                @update:modelValue="handleSearch"
                            />
                            <Button variant="outline" @click="handleSearch">
                                <Search class="mr-2 h-4 w-4" />
                                Tìm
                            </Button>
                            <Button
                                v-if="searchQuery || statusFilter || typeFilter"
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

            <!-- Reports Table -->
            <div v-if="props.reports.data.length > 0">
                <Card>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">
                                            Người báo cáo
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">
                                            Đối tượng
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Loại
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Lý do
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Trạng thái
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-40">
                                            Thời gian
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Hành động
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="report in props.reports.data"
                                        :key="report.id"
                                        class="border-b transition-colors hover:bg-muted/50"
                                    >
                                        <td class="p-4 align-middle">
                                            <div v-if="report.user" class="text-sm">
                                                <div class="font-medium">{{ report.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ report.user.email }}</div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div v-if="report.reportable" class="text-sm">
                                                <div class="font-medium">
                                                    {{ report.reportable.name || report.reportable.title || 'N/A' }}
                                                </div>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ getTypeLabel(report.reportable_type) }}
                                                </div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <Badge variant="outline" class="text-xs">
                                                {{ getTypeLabel(report.reportable_type) }}
                                            </Badge>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm">{{ getReasonLabel(report.reason) }}</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <Badge :variant="getStatusVariant(report.status)" class="text-xs">
                                                {{ getStatusLabel(report.status) }}
                                            </Badge>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm text-muted-foreground">
                                                {{ new Date(report.created_at).toLocaleString('vi-VN') }}
                                            </span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="flex items-center gap-2">
                                                <Button variant="outline" size="sm" as-child>
                                                    <Link :href="`/admin/reports/${report.id}`">
                                                        <Eye class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    @click="handleDelete(report.id)"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="props.reports.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                    <template v-for="(link, index) in props.reports.links" :key="index">
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
                        <AlertTriangle class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có report nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Không tìm thấy report nào phù hợp với bộ lọc
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
