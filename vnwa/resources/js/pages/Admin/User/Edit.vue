<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { computed } from 'vue';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import users from '@/routes/users';

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

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    coin: props.user.coin,
});

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
        title: 'Chỉnh sửa',
        href: '#',
    },
]);

const submit = () => {
    form.put(users.update(props.user.id).url, {
        onSuccess: () => {
            toast.success('Thông tin người dùng đã được cập nhật thành công.');
        },
        onError: (errors) => {
            toast.error('Có lỗi xảy ra khi cập nhật thông tin.');
        },
    });
};
</script>

<template>
    <Head title="Chỉnh sửa User" />

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
                    <h1 class="text-3xl font-bold tracking-tight">Chỉnh sửa User</h1>
                    <p class="text-muted-foreground">Cập nhật thông tin người dùng</p>
                </div>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Thông tin người dùng</CardTitle>
                    <CardDescription>Điền thông tin để cập nhật người dùng</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Tên</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                :class="{ 'border-destructive': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-sm text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Mật khẩu mới (để trống nếu không đổi)</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                :class="{ 'border-destructive': form.errors.password }"
                            />
                            <p v-if="form.errors.password" class="text-sm text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Xác nhận mật khẩu</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                :class="{ 'border-destructive': form.errors.password_confirmation }"
                            />
                            <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="coin">Coin</Label>
                            <Input
                                id="coin"
                                v-model.number="form.coin"
                                type="number"
                                min="0"
                                required
                                :class="{ 'border-destructive': form.errors.coin }"
                            />
                            <p v-if="form.errors.coin" class="text-sm text-destructive">
                                {{ form.errors.coin }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" as-child>
                                <Link :href="users.show(user.id).url">Hủy</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                Lưu thay đổi
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
