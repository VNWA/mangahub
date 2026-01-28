<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Form, Head, Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref } from 'vue';
import { ArrowLeft, Palette } from 'lucide-vue-next';
import badges from '@/routes/badges';

const toast = useToast();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Badges', href: badges.index().url },
    { title: 'Thêm mới', href: '#' },
];

const form = {
    name: '',
    slug: '',
    light_text_color: '#000000',
    light_bg_color: '#FFFFFF',
    dark_text_color: '#FFFFFF',
    dark_bg_color: '#000000',
};

const previewBadge = ref({
    light: {
        text: form.light_text_color,
        bg: form.light_bg_color,
    },
    dark: {
        text: form.dark_text_color,
        bg: form.dark_bg_color,
    },
});
</script>

<template>
    <Head title="Thêm Badge mới" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="badges.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Quay lại
                    </Link>
                </Button>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Thêm Badge mới</h1>
                    <p class="text-muted-foreground mt-1">Tạo một badge mới với màu sắc tùy chỉnh</p>
                </div>
            </div>

            <Separator />

            <!-- Form -->
            <Form
                :action="badges.store().url"
                method="post"
                :data="form"
                class="max-w-4xl space-y-6"
                @success="() => toast.success('Badge đã được tạo thành công.')"
                @error="(errors) => {
                    Object.values(errors).flat().forEach((message: any) => {
                        toast.error(message);
                    });
                }"
            >
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Thông tin Badge</CardTitle>
                                <CardDescription>Nhập thông tin của badge</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="name">Tên Badge *</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Ví dụ: Hot, New, Popular"
                                        required
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="slug">Slug (tùy chọn)</Label>
                                    <Input
                                        id="slug"
                                        v-model="form.slug"
                                        placeholder="hot (tự động tạo nếu để trống)"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Colors -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Palette class="h-5 w-5" />
                                    Màu sắc
                                </CardTitle>
                                <CardDescription>Cấu hình màu sắc cho badge</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Light Mode -->
                                <div class="space-y-4">
                                    <h4 class="font-semibold">Light Mode</h4>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="light_bg_color">Màu nền</Label>
                                            <div class="flex gap-2">
                                                <Input
                                                    id="light_bg_color"
                                                    v-model="form.light_bg_color"
                                                    type="color"
                                                    class="h-10 w-20 p-1 cursor-pointer"
                                                />
                                                <Input
                                                    v-model="form.light_bg_color"
                                                    placeholder="#FFFFFF"
                                                    class="flex-1"
                                                />
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="light_text_color">Màu chữ</Label>
                                            <div class="flex gap-2">
                                                <Input
                                                    id="light_text_color"
                                                    v-model="form.light_text_color"
                                                    type="color"
                                                    class="h-10 w-20 p-1 cursor-pointer"
                                                />
                                                <Input
                                                    v-model="form.light_text_color"
                                                    placeholder="#000000"
                                                    class="flex-1"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dark Mode -->
                                <div class="space-y-4">
                                    <h4 class="font-semibold">Dark Mode</h4>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="dark_bg_color">Màu nền</Label>
                                            <div class="flex gap-2">
                                                <Input
                                                    id="dark_bg_color"
                                                    v-model="form.dark_bg_color"
                                                    type="color"
                                                    class="h-10 w-20 p-1 cursor-pointer"
                                                />
                                                <Input
                                                    v-model="form.dark_bg_color"
                                                    placeholder="#000000"
                                                    class="flex-1"
                                                />
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="dark_text_color">Màu chữ</Label>
                                            <div class="flex gap-2">
                                                <Input
                                                    id="dark_text_color"
                                                    v-model="form.dark_text_color"
                                                    type="color"
                                                    class="h-10 w-20 p-1 cursor-pointer"
                                                />
                                                <Input
                                                    v-model="form.dark_text_color"
                                                    placeholder="#FFFFFF"
                                                    class="flex-1"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar Preview -->
                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Preview</CardTitle>
                                <CardDescription>Xem trước badge</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="form.name" class="space-y-3">
                                    <div>
                                        <p class="text-xs text-muted-foreground mb-2">Light Mode</p>
                                        <Badge
                                            :style="{
                                                color: form.light_text_color,
                                                backgroundColor: form.light_bg_color,
                                            }"
                                            class="text-sm font-semibold"
                                        >
                                            {{ form.name || 'Preview' }}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground mb-2">Dark Mode</p>
                                        <div class="rounded-lg p-4" style="background-color: #1a1a1a">
                                            <Badge
                                                :style="{
                                                    color: form.dark_text_color,
                                                    backgroundColor: form.dark_bg_color,
                                                }"
                                                class="text-sm font-semibold"
                                            >
                                                {{ form.name || 'Preview' }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground text-center py-4">
                                    Nhập tên badge để xem preview
                                </p>
                            </CardContent>
                        </Card>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Button type="submit" class="flex-1">Tạo Badge</Button>
                            <Button type="button" variant="outline" as-child>
                                <Link :href="badges.index().url">Hủy</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
