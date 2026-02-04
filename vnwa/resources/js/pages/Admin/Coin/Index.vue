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
import { Search, X, Coins, TrendingUp, TrendingDown, Users, DollarSign, AlertCircle } from 'lucide-vue-next';
import { dashboard } from '@/routes';

const toast = useToast();

interface Transaction {
    id: number;
    type: string;
    amount: number;
    description?: string;
    balance_after: number;
    created_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    transactions: {
        data: Transaction[];
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
        total_deposits: number;
        total_spends: number;
        total_users: number;
        total_coins_in_circulation: number;
        pending_requests?: number;
        pending_requests_amount?: number;
    };
    filters: {
        search?: string;
        type?: string;
        user_id?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Coins',
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

const handleSearch = () => {
    router.get(
        '/admin/coins',
        {
            search: searchQuery.value || undefined,
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
    typeFilter.value = '';
    router.get('/admin/coins', {}, { preserveState: true, replace: true });
};
</script>

<template>

    <Head title="Quản lý Coins" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Quản lý Coins</h1>
                    <p class="text-muted-foreground">Quản lý giao dịch coin trong hệ thống</p>
                </div>
                <Button as-child>
                    <Link href="/admin/coin-requests">
                        <AlertCircle class="mr-2 h-4 w-4" />
                        Yêu cầu nạp coin
                        <Badge v-if="stats.pending_requests && stats.pending_requests > 0" variant="destructive"
                            class="ml-2">
                            {{ stats.pending_requests }}
                        </Badge>
                    </Link>
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-5">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Tổng nạp</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            +{{ formatNumber(stats.total_deposits) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Tổng tiêu</CardTitle>
                        <TrendingDown class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            -{{ formatNumber(stats.total_spends) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Tổng coin lưu thông</CardTitle>
                        <Coins class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ formatNumber(stats.total_coins_in_circulation) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Người dùng có coin</CardTitle>
                        <Users class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Yêu cầu chờ duyệt</CardTitle>
                        <AlertCircle class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending_requests || 0 }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatNumber(stats.pending_requests_amount || 0) }} coin
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchQuery" type="text" placeholder="Tìm kiếm user..." class="pl-9 pr-9"
                                @keyup.enter="handleSearch" />
                            <button v-if="searchQuery" @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <Select v-model="typeFilter" :options="[
                                { value: '', label: 'Tất cả' },
                                { value: 'deposit', label: 'Nạp' },
                                { value: 'spend', label: 'Tiêu' },
                            ]" @update:modelValue="handleSearch" />
                            <Button variant="outline" @click="handleSearch">
                                <Search class="mr-2 h-4 w-4" />
                                Tìm
                            </Button>
                            <Button v-if="searchQuery || typeFilter" variant="outline" @click="clearFilters">
                                <X class="mr-2 h-4 w-4" />
                                Xóa
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Transactions Table -->
            <div v-if="props.transactions.data.length > 0">
                <Card>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">
                                            Người dùng
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">
                                            Mô tả
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Loại
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Số lượng
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Còn lại
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-40">
                                            Thời gian
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="transaction in props.transactions.data" :key="transaction.id"
                                        class="border-b transition-colors hover:bg-muted/50">
                                        <td class="p-4 align-middle">
                                            <div v-if="transaction.user" class="text-sm">
                                                <div class="font-medium">{{ transaction.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ transaction.user.email }}
                                                </div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm">{{ transaction.description || 'Giao dịch' }}</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <Badge :variant="transaction.type === 'deposit' ? 'default' : 'secondary'"
                                                class="text-xs">
                                                {{ transaction.type === 'deposit' ? 'Nạp' : 'Tiêu' }}
                                            </Badge>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="text-sm font-semibold"
                                                :class="transaction.type === 'deposit' ? 'text-green-600' : 'text-red-600'">
                                                {{ transaction.type === 'deposit' ? '+' : '-' }}
                                                {{ formatNumber(transaction.amount) }}
                                            </div>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm">{{ formatNumber(transaction.balance_after) }}</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm text-muted-foreground">
                                                {{ new Date(transaction.created_at).toLocaleString('vi-VN') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="props.transactions.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                    <template v-for="(link, index) in props.transactions.links" :key="index">
                        <Button v-if="link.url" variant="outline" size="sm" :disabled="link.active"
                            @click="router.get(link.url!)"
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
                        <Coins class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có giao dịch nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Không tìm thấy giao dịch nào phù hợp với bộ lọc
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
