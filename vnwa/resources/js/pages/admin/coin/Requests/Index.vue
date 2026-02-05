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
import {
    Search,
    X,
    Coins,
    Clock,
    CheckCircle,
    XCircle,
    Eye,
    AlertCircle,
    TrendingUp,
    DollarSign,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';

const toast = useToast();

interface CoinRequest {
    id: number;
    amount: number;
    payment_method: string;
    payment_proof?: string;
    note?: string;
    status: string;
    admin_note?: string;
    created_at: string;
    processed_at?: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    processor?: {
        id: number;
        name: string;
    };
}

interface Props {
    requests: {
        data: CoinRequest[];
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
        pending: number;
        approved: number;
        rejected: number;
        total_amount_pending: number;
        total_amount_approved: number;
    };
    filters: {
        search?: string;
        status?: string;
        payment_method?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const paymentMethodFilter = ref(props.filters.payment_method || '');

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Coins',
        href: '/admin/coins',
    },
    {
        title: 'Yêu cầu nạp coin',
        href: '#',
    },
]);

const formatNumber = (num: number): string => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};

const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: string; icon: any; label: string }> = {
        pending: {
            variant: 'outline',
            icon: Clock,
            label: 'Chờ duyệt',
        },
        approved: {
            variant: 'default',
            icon: CheckCircle,
            label: 'Đã duyệt',
        },
        rejected: {
            variant: 'destructive',
            icon: XCircle,
            label: 'Đã từ chối',
        },
    };
    return variants[status] || { variant: 'secondary', icon: AlertCircle, label: status };
};

const getPaymentMethodLabel = (method: string): string => {
    const labels: Record<string, string> = {
        bank_transfer: 'Chuyển khoản',
        momo: 'MoMo',
        zalopay: 'ZaloPay',
        vnpay: 'VNPay',
        other: 'Khác',
    };
    return labels[method] || method;
};

const handleSearch = () => {
    router.get(
        '/admin/coin-requests',
        {
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
            payment_method: paymentMethodFilter.value || undefined,
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
    paymentMethodFilter.value = '';
    router.get('/admin/coin-requests', {}, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Yêu cầu nạp coin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Yêu cầu nạp coin</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ props.requests.total }} yêu cầu
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Quản lý các yêu cầu nạp coin từ người dùng</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-5">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Chờ duyệt</CardTitle>
                        <Clock class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatNumber(stats.total_amount_pending) }} coin
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Đã duyệt</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.approved }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatNumber(stats.total_amount_approved) }} coin
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Đã từ chối</CardTitle>
                        <XCircle class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.rejected }}</div>
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
                                    { value: 'pending', label: 'Chờ duyệt' },
                                    { value: 'approved', label: 'Đã duyệt' },
                                    { value: 'rejected', label: 'Đã từ chối' },
                                ]"
                                @update:modelValue="handleSearch"
                            />
                            <Select
                                v-model="paymentMethodFilter"
                                :options="[
                                    { value: '', label: 'Tất cả phương thức' },
                                    { value: 'bank_transfer', label: 'Chuyển khoản' },
                                    { value: 'momo', label: 'MoMo' },
                                    { value: 'zalopay', label: 'ZaloPay' },
                                    { value: 'vnpay', label: 'VNPay' },
                                    { value: 'other', label: 'Khác' },
                                ]"
                                @update:modelValue="handleSearch"
                            />
                            <Button variant="outline" @click="handleSearch">
                                <Search class="mr-2 h-4 w-4" />
                                Tìm
                            </Button>
                            <Button
                                v-if="searchQuery || statusFilter || paymentMethodFilter"
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

            <!-- Requests Table -->
            <div v-if="props.requests.data.length > 0">
                <Card>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                            ID
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">
                                            Người dùng
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Số lượng
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Phương thức
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Trạng thái
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-40">
                                            Thời gian
                                        </th>
                                        <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground w-32">
                                            Thao tác
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="request in props.requests.data"
                                        :key="request.id"
                                        class="border-b transition-colors hover:bg-muted/50"
                                    >
                                        <td class="p-4 align-middle">
                                            <span class="text-sm font-mono">#{{ request.id }}</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div v-if="request.user" class="text-sm">
                                                <div class="font-medium">{{ request.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ request.user.email }}</div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="flex items-center gap-1 text-sm font-semibold">
                                                <Coins class="h-4 w-4 text-yellow-500" />
                                                <span>{{ formatNumber(request.amount) }}</span>
                                            </div>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <Badge variant="outline" class="text-xs">
                                                {{ getPaymentMethodLabel(request.payment_method) }}
                                            </Badge>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <Badge
                                                :variant="getStatusBadge(request.status).variant as any"
                                                class="text-xs"
                                            >
                                                <component
                                                    :is="getStatusBadge(request.status).icon"
                                                    class="mr-1 h-3 w-3"
                                                />
                                                {{ getStatusBadge(request.status).label }}
                                            </Badge>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="text-sm">
                                                <div>{{ new Date(request.created_at).toLocaleDateString('vi-VN') }}</div>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ new Date(request.created_at).toLocaleTimeString('vi-VN') }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 align-middle text-right">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="`/admin/coin-requests/${request.id}`">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="props.requests.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                    <template v-for="(link, index) in props.requests.links" :key="index">
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
                        <Coins class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có yêu cầu nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Không tìm thấy yêu cầu nào phù hợp với bộ lọc
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
