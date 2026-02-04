<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { computed, ref } from 'vue';
import { ArrowLeft, Coins } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import users from '@/routes/users';
import axios from '@/axios';
import { Spinner } from '@/components/ui/spinner';

const toast = useToast();

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        coin: number;
    };
}

const props = defineProps<Props>();

const amount = ref<number>(0);
const description = ref<string>('');
const addingCoin = ref<boolean>(false);

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
        href: users.show(props.user.id).url,
    },
    {
        title: 'Nạp Coin',
        href: '#',
    },
]);

const handleAddCoin = async () => {
    if (amount.value <= 0) {
        toast.error('Số lượng coin phải lớn hơn 0.');
        return;
    }

    addingCoin.value = true;
    try {
        const response = await axios.post(users.addCoin(props.user.id).url, {
            amount: amount.value,
            description: description.value,
        });
        toast.success(response.data.message || 'Đã thêm coin thành công.');
        router.visit(users.show(props.user.id).url);
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra.');
    } finally {
        addingCoin.value = false;
    }
};
</script>

<template>
    <Head title="Nạp Coin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="users.show(user.id).url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold tracking-tight">Nạp Coin</h1>
                    <p class="text-muted-foreground">Thêm coin cho người dùng {{ user.name }}</p>
                </div>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Coins class="h-5 w-5" />
                        Thông tin nạp coin
                    </CardTitle>
                    <CardDescription>
                        Người dùng hiện tại có {{ user.coin }} coin
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <Label for="amount">Số lượng coin</Label>
                            <Input
                                id="amount"
                                v-model.number="amount"
                                type="number"
                                min="1"
                                placeholder="Nhập số lượng coin"
                                required
                            />
                            <p class="text-sm text-muted-foreground">
                                Số lượng coin sẽ được thêm vào tài khoản của người dùng
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Mô tả (tùy chọn)</Label>
                            <Input
                                id="description"
                                v-model="description"
                                type="text"
                                placeholder="Mô tả giao dịch"
                            />
                            <p class="text-sm text-muted-foreground">
                                Mô tả sẽ được lưu trong lịch sử giao dịch
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4">
                            <Button variant="outline" as-child>
                                <Link :href="users.show(user.id).url">Hủy</Link>
                            </Button>
                            <Button @click="handleAddCoin" :disabled="addingCoin || amount <= 0">
                                <Spinner v-if="addingCoin" class="mr-2 h-4 w-4" />
                                <Coins v-else class="mr-2 h-4 w-4" />
                                {{ addingCoin ? 'Đang xử lý...' : 'Nạp Coin' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
