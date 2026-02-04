<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { computed, ref } from 'vue';
import {
    ArrowLeft,
    CheckCircle,
    XCircle,
    Coins,
    User,
    Calendar,
    CreditCard,
    FileImage,
    MessageSquare,
    AlertCircle,
    Eye,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { getStorageUrl } from '@/utils/storage';

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
        avatar?: string;
        coin: number;
    };
    processor?: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    request: CoinRequest;
}

const props = defineProps<Props>();

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
        href: '/admin/coin-requests',
    },
    {
        title: `#${props.request.id}`,
        href: '#',
    },
]);

const approveForm = useForm({
    admin_note: '',
});

const rejectForm = useForm({
    admin_note: '',
});

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);

const formatNumber = (num: number): string => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};

const getPaymentMethodLabel = (method: string): string => {
    const labels: Record<string, string> = {
        bank_transfer: 'Chuyển khoản ngân hàng',
        momo: 'MoMo',
        zalopay: 'ZaloPay',
        vnpay: 'VNPay',
        other: 'Khác',
    };
    return labels[method] || method;
};

const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: string; icon: any; label: string; color: string }> = {
        pending: {
            variant: 'outline',
            icon: AlertCircle,
            label: 'Chờ duyệt',
            color: 'text-yellow-600',
        },
        approved: {
            variant: 'default',
            icon: CheckCircle,
            label: 'Đã duyệt',
            color: 'text-green-600',
        },
        rejected: {
            variant: 'destructive',
            icon: XCircle,
            label: 'Đã từ chối',
            color: 'text-red-600',
        },
    };
    return variants[status] || { variant: 'secondary', icon: AlertCircle, label: status, color: '' };
};

const handleApprove = () => {
    approveForm.post(`/admin/coin-requests/${props.request.id}/approve`, {
        onSuccess: () => {
            toast.success('Đã duyệt yêu cầu và cấp coin cho người dùng.');
            showApproveDialog.value = false;
            approveForm.reset();
        },
        onError: (errors) => {
            toast.error(errors.message || 'Có lỗi xảy ra khi duyệt yêu cầu.');
        },
    });
};

const handleReject = () => {
    if (!rejectForm.admin_note.trim()) {
        toast.error('Vui lòng nhập lý do từ chối.');
        return;
    }

    rejectForm.post(`/admin/coin-requests/${props.request.id}/reject`, {
        onSuccess: () => {
            toast.success('Đã từ chối yêu cầu.');
            showRejectDialog.value = false;
            rejectForm.reset();
        },
        onError: (errors) => {
            toast.error(errors.message || 'Có lỗi xảy ra khi từ chối yêu cầu.');
        },
    });
};

const statusBadge = computed(() => getStatusBadge(props.request.status));
</script>

<template>

    <Head :title="`Yêu cầu nạp coin #${request.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/admin/coin-requests">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Yêu cầu nạp coin #{{ request.id }}</h1>
                        <Badge :variant="statusBadge.variant as any" class="text-sm">
                            <component :is="statusBadge.icon" class="mr-1 h-3 w-3" />
                            {{ statusBadge.label }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">
                        {{ new Date(request.created_at).toLocaleString('vi-VN') }}
                    </p>
                </div>
                <div v-if="request.status === 'pending'" class="flex gap-2">
                    <Dialog v-model:open="showApproveDialog">
                        <DialogTrigger as-child>
                            <Button variant="default">
                                <CheckCircle class="mr-2 h-4 w-4" />
                                Duyệt
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Duyệt yêu cầu nạp coin</DialogTitle>
                                <DialogDescription>
                                    Bạn có chắc chắn muốn duyệt yêu cầu này? {{ formatNumber(request.amount) }} coin sẽ
                                    được cấp cho người dùng
                                    <strong>{{ request.user?.name }}</strong>.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4 py-4">
                                <div class="space-y-2">
                                    <Label for="approve-note">Ghi chú (tùy chọn)</Label>
                                    <Input id="approve-note" v-model="approveForm.admin_note" type="text"
                                        placeholder="Ghi chú cho người dùng" />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="showApproveDialog = false">
                                    Hủy
                                </Button>
                                <Button @click="handleApprove" :disabled="approveForm.processing">
                                    <CheckCircle class="mr-2 h-4 w-4" />
                                    Duyệt và cấp coin
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog v-model:open="showRejectDialog">
                        <DialogTrigger as-child>
                            <Button variant="destructive">
                                <XCircle class="mr-2 h-4 w-4" />
                                Từ chối
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Từ chối yêu cầu nạp coin</DialogTitle>
                                <DialogDescription>
                                    Vui lòng nhập lý do từ chối yêu cầu này.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4 py-4">
                                <div class="space-y-2">
                                    <Label for="reject-note">Lý do từ chối *</Label>
                                    <textarea id="reject-note" v-model="rejectForm.admin_note"
                                        class="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                        placeholder="Nhập lý do từ chối..." required />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="showRejectDialog = false">
                                    Hủy
                                </Button>
                                <Button variant="destructive" @click="handleReject"
                                    :disabled="rejectForm.processing || !rejectForm.admin_note.trim()">
                                    <XCircle class="mr-2 h-4 w-4" />
                                    Từ chối
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Request Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Thông tin yêu cầu</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Số lượng coin</span>
                                <div class="flex items-center gap-1 text-lg font-bold">
                                    <Coins class="h-5 w-5 text-yellow-500" />
                                    <span>{{ formatNumber(request.amount) }}</span>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Phương thức thanh toán</span>
                                <Badge variant="outline">
                                    <CreditCard class="mr-1 h-3 w-3" />
                                    {{ getPaymentMethodLabel(request.payment_method) }}
                                </Badge>
                            </div>
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Trạng thái</span>
                                <Badge :variant="statusBadge.variant as any">
                                    <component :is="statusBadge.icon" class="mr-1 h-3 w-3" />
                                    {{ statusBadge.label }}
                                </Badge>
                            </div>
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Thời gian tạo</span>
                                <span class="text-sm">
                                    {{ new Date(request.created_at).toLocaleString('vi-VN') }}
                                </span>
                            </div>
                            <div v-if="request.processed_at" class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Thời gian xử lý</span>
                                <span class="text-sm">
                                    {{ new Date(request.processed_at).toLocaleString('vi-VN') }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- User Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Thông tin người dùng</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="request.user" class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-full border bg-muted">
                                    <img v-if="request.user.avatar" :src="request.user.avatar" :alt="request.user.name"
                                        class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full items-center justify-center">
                                        <User class="h-8 w-8 text-muted-foreground" />
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold">{{ request.user.name }}</h3>
                                    <p class="text-sm text-muted-foreground">{{ request.user.email }}</p>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Coin hiện tại</span>
                                <div class="flex items-center gap-1 text-sm font-semibold">
                                    <Coins class="h-4 w-4 text-yellow-500" />
                                    <span>{{ formatNumber(request.user.coin) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Coin sau khi duyệt</span>
                                <div class="flex items-center gap-1 text-sm font-semibold text-green-600">
                                    <Coins class="h-4 w-4" />
                                    <span>{{ formatNumber(request.user.coin + request.amount) }}</span>
                                </div>
                            </div>
                            <Button variant="outline" size="sm" as-child class="w-full">
                                <Link :href="`/admin/users/${request.user.id}`">
                                    <Eye class="mr-2 h-4 w-4" />
                                    Xem chi tiết user
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment Proof -->
                <Card v-if="request.payment_proof" class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Chứng từ thanh toán</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="relative rounded-lg border bg-muted/50 p-4">
                            <img :src="getStorageUrl(request.payment_proof) || request.payment_proof"
                                alt="Payment proof" class="max-h-96 w-full rounded-lg object-contain" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card v-if="request.note" class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Ghi chú từ người dùng</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-lg border bg-muted/50 p-4">
                            <p class="whitespace-pre-wrap">{{ request.note }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Admin Note -->
                <Card v-if="request.admin_note" class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Ghi chú từ admin</CardTitle>
                        <CardDescription v-if="request.processor">
                            Xử lý bởi: {{ request.processor.name }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-lg border bg-muted/50 p-4">
                            <p class="whitespace-pre-wrap">{{ request.admin_note }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
