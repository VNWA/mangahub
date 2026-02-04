<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { computed, ref } from 'vue';
import axios from '@/axios';
import {
    ArrowLeft,
    Edit,
    User,
    Mail,
    Calendar,
    Coins,
    Heart,
    BookOpen,
    Lock,
    Plus,
    Minus,
    Shield,
    MessageSquare,
    TrendingUp,
    TrendingDown,
    Eye,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import users from '@/routes/users';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

const toast = useToast();

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        avatar?: string;
        is_guest: boolean;
        coin: number;
        created_at: string;
        email_verified_at?: string;
        favorites_count?: number;
        reading_history_count?: number;
        coin_transactions_count?: number;
        chapter_unlocks_count?: number;
    };
    comments: {
        data: Array<{
            id: number;
            content: string;
            likes_count: number;
            dislikes_count: number;
            replies_count: number;
            is_pinned: boolean;
            created_at: string;
            page?: {
                pageable_type: string;
                pageable?: {
                    id: number;
                    name?: string;
                    slug?: string;
                };
            };
        }>;
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
    transactions: {
        data: Array<{
            id: number;
            type: string;
            amount: number;
            description?: string;
            balance_after: number;
            created_at: string;
            admin?: {
                id: number;
                name: string;
                email: string;
            };
        }>;
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
        total_favorites: number;
        total_reading_history: number;
        total_coin_transactions: number;
        total_chapter_unlocks: number;
        total_coins: number;
        total_comments: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Users',
        href: users.index().url,
    },
    {
        title: props.user.name,
        href: '#',
    },
]);

const addCoinForm = ref({
    amount: 0,
    description: '',
});

const removeCoinForm = ref({
    amount: 0,
    description: '',
});

const assignRoleForm = ref({
    role: 'user',
});

const addingCoin = ref(false);
const removingCoin = ref(false);
const assigningRole = ref(false);

const activeTab = ref<'overview' | 'comments' | 'coins'>('overview');

const handleAddCoin = async () => {
    if (addCoinForm.value.amount <= 0) {
        toast.error('Số lượng coin phải lớn hơn 0.');
        return;
    }

    addingCoin.value = true;
    try {
        const response = await axios.post(users.addCoin(props.user.id).url, addCoinForm.value);
        toast.success(response.data.message || 'Đã thêm coin thành công.');
        addCoinForm.value = { amount: 0, description: '' };
        router.reload({ only: ['user', 'transactions', 'stats'] });
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra.');
    } finally {
        addingCoin.value = false;
    }
};

const handleRemoveCoin = async () => {
    if (removeCoinForm.value.amount <= 0) {
        toast.error('Số lượng coin phải lớn hơn 0.');
        return;
    }

    removingCoin.value = true;
    try {
        const response = await axios.post(users.removeCoin(props.user.id).url, removeCoinForm.value);
        toast.success(response.data.message || 'Đã trừ coin thành công.');
        removeCoinForm.value = { amount: 0, description: '' };
        router.reload({ only: ['user', 'transactions', 'stats'] });
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra.');
    } finally {
        removingCoin.value = false;
    }
};

const handleAssignRole = async () => {
    assigningRole.value = true;
    try {
        const response = await axios.post(users.assignRole(props.user.id).url, assignRoleForm.value);
        toast.success(response.data.message || 'Đã cập nhật vai trò thành công.');
        router.reload({ only: ['user'] });
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra.');
    } finally {
        assigningRole.value = false;
    }
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

const getPageableTypeLabel = (type: string): string => {
    const labels: Record<string, string> = {
        'App\\Models\\Manga': 'Manga',
        'App\\Models\\MangaChapter': 'Chapter',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="users.index().url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold tracking-tight">{{ user.name }}</h1>
                    <p class="text-muted-foreground">{{ user.email }}</p>
                </div>
                <Button as-child>
                    <Link :href="users.edit(user.id).url">
                        <Edit class="mr-2 h-4 w-4" />
                        Chỉnh sửa
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- User Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Thông tin người dùng</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full border bg-muted">
                                <img
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full items-center justify-center"
                                >
                                    <User class="h-10 w-10 text-muted-foreground" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold">{{ user.name }}</h3>
                                    <Badge :variant="user.is_guest ? 'outline' : 'default'">
                                        {{ user.is_guest ? 'Guest' : 'Registered' }}
                                    </Badge>
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                                    <Mail class="h-4 w-4" />
                                    {{ user.email }}
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <span class="text-muted-foreground">Ngày tạo:</span>
                                <span>{{ new Date(user.created_at).toLocaleDateString('vi-VN') }}</span>
                            </div>
                            <div v-if="user.email_verified_at" class="flex items-center gap-2 text-sm">
                                <Mail class="h-4 w-4 text-muted-foreground" />
                                <span class="text-muted-foreground">Email đã xác thực:</span>
                                <span>{{ new Date(user.email_verified_at).toLocaleDateString('vi-VN') }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Stats -->
                <Card>
                    <CardHeader>
                        <CardTitle>Thống kê</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Coins class="h-4 w-4" />
                                    Coin
                                </div>
                                <div class="text-2xl font-bold">{{ formatNumber(stats.total_coins) }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Heart class="h-4 w-4" />
                                    Favorites
                                </div>
                                <div class="text-2xl font-bold">{{ stats.total_favorites }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <BookOpen class="h-4 w-4" />
                                    Reading History
                                </div>
                                <div class="text-2xl font-bold">{{ stats.total_reading_history }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Lock class="h-4 w-4" />
                                    Chapter Unlocks
                                </div>
                                <div class="text-2xl font-bold">{{ stats.total_chapter_unlocks }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Coin Management -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Coins class="h-5 w-5" />
                            Quản lý Coin
                        </CardTitle>
                        <CardDescription>Thêm hoặc trừ coin cho người dùng. Mọi thao tác sẽ được ghi lại trong lịch sử.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="rounded-lg border bg-muted/30 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm text-muted-foreground">Số coin hiện tại</div>
                                    <div class="text-3xl font-bold">{{ formatNumber(user.coin) }}</div>
                                </div>
                                <div class="rounded-full bg-primary/10 p-4">
                                    <Coins class="h-8 w-8 text-primary" />
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="outline" class="flex-1">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Thêm Coin
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Thêm Coin</DialogTitle>
                                        <DialogDescription>
                                            Thêm coin cho người dùng {{ user.name }}
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div class="space-y-4 py-4">
                                        <div class="space-y-2">
                                            <Label for="add-amount">Số lượng</Label>
                                            <Input
                                                id="add-amount"
                                                v-model.number="addCoinForm.amount"
                                                type="number"
                                                min="1"
                                                placeholder="Nhập số lượng coin"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="add-description">Mô tả (tùy chọn)</Label>
                                            <Input
                                                id="add-description"
                                                v-model="addCoinForm.description"
                                                type="text"
                                                placeholder="Mô tả giao dịch"
                                            />
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            @click="handleAddCoin"
                                            :disabled="addingCoin || addCoinForm.amount <= 0"
                                        >
                                            <Spinner v-if="addingCoin" class="mr-2 h-4 w-4" />
                                            {{ addingCoin ? 'Đang xử lý...' : 'Thêm Coin' }}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>

                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="outline" class="flex-1">
                                        <Minus class="mr-2 h-4 w-4" />
                                        Trừ Coin
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Trừ Coin</DialogTitle>
                                        <DialogDescription>
                                            Trừ coin của người dùng {{ user.name }}
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div class="space-y-4 py-4">
                                        <div class="space-y-2">
                                            <Label for="remove-amount">Số lượng</Label>
                                            <Input
                                                id="remove-amount"
                                                v-model.number="removeCoinForm.amount"
                                                type="number"
                                                min="1"
                                                placeholder="Nhập số lượng coin"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="remove-description">Mô tả (tùy chọn)</Label>
                                            <Input
                                                id="remove-description"
                                                v-model="removeCoinForm.description"
                                                type="text"
                                                placeholder="Mô tả giao dịch"
                                            />
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            @click="handleRemoveCoin"
                                            :disabled="removingCoin || removeCoinForm.amount <= 0"
                                        >
                                            <Spinner v-if="removingCoin" class="mr-2 h-4 w-4" />
                                            {{ removingCoin ? 'Đang xử lý...' : 'Trừ Coin' }}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        </div>

                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="outline" class="w-full">
                                    <Shield class="mr-2 h-4 w-4" />
                                    Phân quyền
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Phân quyền</DialogTitle>
                                    <DialogDescription>
                                        Thay đổi vai trò của người dùng {{ user.name }}
                                    </DialogDescription>
                                </DialogHeader>
                                <div class="space-y-4 py-4">
                                    <div class="space-y-2">
                                        <Label for="role">Vai trò</Label>
                                        <Select
                                            id="role"
                                            v-model="assignRoleForm.role"
                                            :options="[
                                                { value: 'user', label: 'User' },
                                                { value: 'admin', label: 'Admin' },
                                            ]"
                                        />
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button
                                        @click="handleAssignRole"
                                        :disabled="assigningRole"
                                    >
                                        <Spinner v-if="assigningRole" class="mr-2 h-4 w-4" />
                                        {{ assigningRole ? 'Đang xử lý...' : 'Cập nhật' }}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </CardContent>
                </Card>

            </div>

            <!-- Tabs -->
            <Card class="md:col-span-2">
                <CardHeader>
                    <div class="flex items-center gap-2 border-b">
                        <button
                            @click="activeTab = 'overview'"
                            :class="[
                                'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                                activeTab === 'overview'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            Tổng quan
                        </button>
                        <button
                            @click="activeTab = 'comments'"
                            :class="[
                                'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px flex items-center gap-2',
                                activeTab === 'comments'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            <MessageSquare class="h-4 w-4" />
                            Comments
                            <Badge variant="secondary" class="text-xs">
                                {{ stats.total_comments }}
                            </Badge>
                        </button>
                        <button
                            @click="activeTab = 'coins'"
                            :class="[
                                'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px flex items-center gap-2',
                                activeTab === 'coins'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            <Coins class="h-4 w-4" />
                            Coin Transactions
                            <Badge variant="secondary" class="text-xs">
                                {{ stats.total_coin_transactions }}
                            </Badge>
                        </button>
                    </div>
                </CardHeader>
                <CardContent class="pt-6">
                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <div class="text-sm text-muted-foreground">Tổng số comments</div>
                                <div class="text-2xl font-bold">{{ stats.total_comments }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-sm text-muted-foreground">Tổng số giao dịch coin</div>
                                <div class="text-2xl font-bold">{{ stats.total_coin_transactions }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Comments Tab -->
                    <div v-if="activeTab === 'comments'" class="space-y-4">
                        <div v-if="comments.data.length > 0" class="space-y-3">
                            <div
                                v-for="comment in comments.data"
                                :key="comment.id"
                                class="rounded-lg border p-4"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 space-y-2">
                                        <div class="flex items-center gap-2">
                                            <Badge v-if="comment.is_pinned" variant="default" class="text-xs">
                                                Pinned
                                            </Badge>
                                            <Badge v-if="comment.page?.pageable_type" variant="outline" class="text-xs">
                                                {{ getPageableTypeLabel(comment.page.pageable_type) }}
                                            </Badge>
                                        </div>
                                        <p class="text-sm line-clamp-2">{{ comment.content }}</p>
                                        <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                            <div class="flex items-center gap-1">
                                                <TrendingUp class="h-3 w-3" />
                                                {{ comment.likes_count }}
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <TrendingDown class="h-3 w-3" />
                                                {{ comment.dislikes_count }}
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <MessageSquare class="h-3 w-3" />
                                                {{ comment.replies_count }}
                                            </div>
                                            <span>{{ new Date(comment.created_at).toLocaleString('vi-VN') }}</span>
                                        </div>
                                        <div v-if="comment.page?.pageable" class="text-xs text-muted-foreground">
                                            Liên kết: {{ comment.page.pageable.name || comment.page.pageable.slug }}
                                        </div>
                                    </div>
                                    <Button variant="outline" size="sm" as-child>
                                        <Link :href="`/admin/comments/${comment.id}`">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="comments.last_page > 1" class="flex items-center justify-center gap-2 pt-4">
                                <template v-for="(link, index) in comments.links" :key="index">
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
                        <div v-else class="text-center py-12 text-sm text-muted-foreground">
                            <MessageSquare class="mx-auto h-12 w-12 mb-4 opacity-50" />
                            <p>Chưa có comment nào</p>
                        </div>
                    </div>

                    <!-- Coins Tab -->
                    <div v-if="activeTab === 'coins'" class="space-y-4">
                        <div v-if="transactions.data.length > 0" class="space-y-3">
                            <div
                                v-for="transaction in transactions.data"
                                :key="transaction.id"
                                class="rounded-lg border p-4 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 space-y-2">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <Badge
                                                :variant="transaction.type === 'deposit' ? 'default' : 'secondary'"
                                                class="text-xs"
                                            >
                                                {{ transaction.type === 'deposit' ? 'Nạp' : 'Tiêu' }}
                                            </Badge>
                                            <span class="text-sm font-medium">
                                                {{ transaction.description || 'Giao dịch' }}
                                            </span>
                                        </div>
                                        <div class="flex flex-col gap-1 text-xs text-muted-foreground">
                                            <div class="flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />
                                                {{ new Date(transaction.created_at).toLocaleString('vi-VN') }}
                                            </div>
                                            <div v-if="transaction.admin" class="flex items-center gap-1">
                                                <User class="h-3 w-3" />
                                                Nạp bởi: <span class="font-medium text-foreground">{{ transaction.admin.name }}</span>
                                                <span class="text-muted-foreground">({{ transaction.admin.email }})</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right space-y-1">
                                        <div
                                            class="text-lg font-bold"
                                            :class="transaction.type === 'deposit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                        >
                                            {{ transaction.type === 'deposit' ? '+' : '-' }}
                                            {{ formatNumber(transaction.amount) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            Số dư: <span class="font-medium text-foreground">{{ formatNumber(transaction.balance_after) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="transactions.last_page > 1" class="flex items-center justify-center gap-2 pt-4">
                                <template v-for="(link, index) in transactions.links" :key="index">
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
                        <div v-else class="text-center py-12 text-sm text-muted-foreground">
                            <Coins class="mx-auto h-12 w-12 mb-4 opacity-50" />
                            <p>Chưa có giao dịch nào</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
