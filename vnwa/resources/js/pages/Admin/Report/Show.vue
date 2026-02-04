<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { computed, ref } from 'vue';
import { ArrowLeft, Trash2, AlertTriangle, User, Calendar, CheckCircle, XCircle, Eye } from 'lucide-vue-next';
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
    report: Report;
}

const props = defineProps<Props>();

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Reports',
        href: '/admin/reports',
    },
    {
        title: 'Chi tiết',
        href: '#',
    },
]);

const statusForm = useForm({
    status: props.report.status,
    admin_note: props.report.admin_note || '',
});

const isDialogOpen = ref(false);

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

const handleUpdateStatus = () => {
    statusForm.put(`/admin/reports/${props.report.id}/status`, {
        onSuccess: () => {
            toast.success('Đã cập nhật trạng thái report');
            isDialogOpen.value = false;
        },
        onError: () => {
            toast.error('Có lỗi xảy ra khi cập nhật trạng thái');
        },
    });
};

const handleDelete = () => {
    if (!confirm('Bạn có chắc chắn muốn xóa report này?')) {
        return;
    }

    router.delete(`/admin/reports/${props.report.id}`, {
        onSuccess: () => {
            toast.success('Đã xóa report thành công');
            router.visit('/admin/reports');
        },
        onError: () => {
            toast.error('Có lỗi xảy ra khi xóa report');
        },
    });
};
</script>

<template>
    <Head title="Chi tiết Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/admin/reports">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold tracking-tight">Chi tiết Report</h1>
                </div>
                <div class="flex gap-2">
                    <Dialog v-model:open="isDialogOpen">
                        <DialogTrigger as-child>
                            <Button variant="outline">
                                <CheckCircle class="mr-2 h-4 w-4" />
                                Cập nhật trạng thái
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Cập nhật trạng thái</DialogTitle>
                                <DialogDescription>
                                    Thay đổi trạng thái của report này
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4 py-4">
                                <div class="space-y-2">
                                    <Label for="status">Trạng thái</Label>
                                    <Select
                                        id="status"
                                        v-model="statusForm.status"
                                        :options="[
                                            { value: 'pending', label: 'Chờ xử lý' },
                                            { value: 'reviewed', label: 'Đã xem' },
                                            { value: 'resolved', label: 'Đã xử lý' },
                                            { value: 'rejected', label: 'Từ chối' },
                                        ]"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="admin_note">Ghi chú admin</Label>
                                    <Textarea
                                        id="admin_note"
                                        v-model="statusForm.admin_note"
                                        placeholder="Nhập ghi chú..."
                                        rows="4"
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button
                                    @click="handleUpdateStatus"
                                    :disabled="statusForm.processing"
                                >
                                    Cập nhật
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                    <Button variant="destructive" @click="handleDelete">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Xóa
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Report Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Thông tin Report</CardTitle>
                        <CardDescription>Chi tiết về báo cáo này</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label class="text-muted-foreground">Trạng thái</Label>
                            <div>
                                <Badge :variant="getStatusVariant(report.status)" class="text-sm">
                                    {{ getStatusLabel(report.status) }}
                                </Badge>
                            </div>
                        </div>
                        <Separator />
                        <div class="space-y-2">
                            <Label class="text-muted-foreground">Lý do</Label>
                            <div class="text-sm font-medium">{{ getReasonLabel(report.reason) }}</div>
                        </div>
                        <Separator />
                        <div class="space-y-2">
                            <Label class="text-muted-foreground">Mô tả</Label>
                            <div class="text-sm">{{ report.description || 'Không có mô tả' }}</div>
                        </div>
                        <Separator />
                        <div class="space-y-2">
                            <Label class="text-muted-foreground">Thời gian tạo</Label>
                            <div class="flex items-center gap-2 text-sm">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                {{ new Date(report.created_at).toLocaleString('vi-VN') }}
                            </div>
                        </div>
                        <div v-if="report.reviewed_at" class="space-y-2">
                            <Label class="text-muted-foreground">Thời gian xử lý</Label>
                            <div class="flex items-center gap-2 text-sm">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                {{ new Date(report.reviewed_at).toLocaleString('vi-VN') }}
                            </div>
                        </div>
                        <div v-if="report.admin_note" class="space-y-2">
                            <Label class="text-muted-foreground">Ghi chú admin</Label>
                            <div class="text-sm">{{ report.admin_note }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- User Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Người báo cáo</CardTitle>
                        <CardDescription>Thông tin người tạo report</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="report.user" class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                                    <User class="h-6 w-6" />
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium">{{ report.user.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ report.user.email }}</div>
                                </div>
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="`/admin/users/${report.user.id}`">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            Không có thông tin
                        </div>
                        <Separator />
                        <div v-if="report.reviewer" class="space-y-2">
                            <Label class="text-muted-foreground">Người xử lý</Label>
                            <div class="text-sm font-medium">{{ report.reviewer.name }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Reported Item -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Đối tượng bị báo cáo</CardTitle>
                        <CardDescription>Thông tin về manga/chapter bị report</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="report.reportable" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline">{{ getTypeLabel(report.reportable_type) }}</Badge>
                                        <div class="font-medium">
                                            {{ report.reportable.name || report.reportable.title || 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        ID: {{ report.reportable.id }}
                                    </div>
                                </div>
                                <Button variant="outline" size="sm" as-child>
                                    <Link
                                        :href="report.reportable_type === 'App\\Models\\Manga' 
                                            ? `/admin/mangas/${report.reportable.id}`
                                            : `#`"
                                    >
                                        <Eye class="mr-2 h-4 w-4" />
                                        Xem chi tiết
                                    </Link>
                                </Button>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            Đối tượng không còn tồn tại
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
