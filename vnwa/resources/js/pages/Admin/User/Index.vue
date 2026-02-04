<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, computed } from 'vue';
import { Search, X, Eye, Edit, Trash2, Users, MoreVertical, Coins, User, Plus } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';
import users from '@/routes/users';

const toast = useToast();

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    is_guest: boolean;
    coin: number;
    created_at: string;
    favorites_count?: number;
    reading_history_count?: number;
    coin_transactions_count?: number;
}

interface Props {
    users: {
        data: User[];
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
        is_guest?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const isGuestFilter = ref(props.filters.is_guest || '');

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Users',
        href: users.index().url,
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
        users.index().url,
        {
            search: searchQuery.value || undefined,
            is_guest: isGuestFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleDelete = (userId: number) => {
    if (!confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác.')) {
        return;
    }

    router.delete(users.destroy(userId).url, {
        onSuccess: () => {
            toast.success('Người dùng đã được xóa thành công.');
        },
        onError: (errors) => {
            toast.error(errors.message || 'Có lỗi xảy ra khi xóa người dùng.');
        },
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    isGuestFilter.value = '';
    router.get(users.index().url, {}, { preserveState: true, replace: true });
};

</script>

<template>

    <Head title="Quản lý Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Quản lý Users</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ props.users.total }} users
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Quản lý tất cả người dùng trong hệ thống</p>
                </div>
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
                            <Select v-model="isGuestFilter" :options="[
                                { value: '', label: 'Tất cả' },
                                { value: 'true', label: 'Guest' },
                                { value: 'false', label: 'Registered' },
                            ]" @update:modelValue="handleSearch" />
                            <Button variant="outline" @click="handleSearch">
                                <Search class="mr-2 h-4 w-4" />
                                Tìm
                            </Button>
                            <Button v-if="searchQuery || isGuestFilter" variant="outline" @click="clearFilters">
                                <X class="mr-2 h-4 w-4" />
                                Xóa
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Table -->
            <div v-if="props.users.data.length > 0">
                <Card>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-16">
                                            <span class="sr-only">Avatar</span>
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">
                                            Tên
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">
                                            Email
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                                            Loại
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                                            <div class="flex items-center gap-1">
                                                <Coins class="h-4 w-4" />
                                                Coin
                                            </div>
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Favorites
                                        </th>
                                        <th
                                            class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-32">
                                            Reading History
                                        </th>
                                        <th
                                            class="h-12 px-4 text-right align-middle font-medium text-muted-foreground w-32">
                                            Thao tác
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in props.users.data" :key="user.id"
                                        class="border-b transition-colors hover:bg-muted/50">
                                        <td class="p-4 align-middle">
                                            <div
                                                class="relative h-10 w-10 shrink-0 overflow-hidden rounded-full border bg-muted">
                                                <img v-if="user.avatar" :src="user.avatar" :alt="user.name"
                                                    class="h-full w-full object-cover" />
                                                <div v-else class="flex h-full items-center justify-center">
                                                    <User class="h-5 w-5 text-muted-foreground" />
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="font-medium">{{ user.name }}</div>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <Badge :variant="user.is_guest ? 'outline' : 'default'" class="text-xs">
                                                {{ user.is_guest ? 'Guest' : 'Registered' }}
                                            </Badge>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="flex items-center gap-1 text-sm font-semibold">
                                                <Coins class="h-3 w-3 text-yellow-500" />
                                                <span>{{ formatNumber(user.coin) }}</span>
                                            </div>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm">{{ user.favorites_count || 0 }}</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm">{{ user.reading_history_count || 0 }}</span>
                                        </td>
                                        <td class="p-4 align-middle text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <Button variant="outline" size="sm" as-child>
                                                    <Link :href="users.show(user.id).url">
                                                        <Eye class="h-4 w-4" />
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
                                                            <Link :href="users.show(user.id).url">
                                                                <Eye class="mr-2 h-4 w-4" />
                                                                Xem chi tiết
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem as-child>
                                                            <Link :href="users.edit(user.id).url">
                                                                <Edit class="mr-2 h-4 w-4" />
                                                                Chỉnh sửa
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem as-child>
                                                            <Link :href="users.addCoinPage(user.id).url">
                                                                <Plus class="mr-2 h-4 w-4" />
                                                                Nạp Coin
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem class="text-destructive"
                                                            @click="handleDelete(user.id)">
                                                            <Trash2 class="mr-2 h-4 w-4" />
                                                            Xóa
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="props.users.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                    <template v-for="(link, index) in props.users.links" :key="index">
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
                        <Users class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có user nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Không tìm thấy user nào phù hợp với bộ lọc
                        </p>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
