<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Form, Head, Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref } from 'vue';
import { ArrowLeft } from 'lucide-vue-next';
import InputImage from '@/components/input/InputImage.vue';
import authors from '@/routes/authors';

const toast = useToast();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Authors', href: authors.index().url },
    { title: 'Thêm mới', href: '#' },
];

const form = {
    name: '',
    slug: '',
    description: '',
    avatar: '',
};
</script>

<template>
    <Head title="Thêm Author mới" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="authors.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Quay lại
                    </Link>
                </Button>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Thêm Author mới</h1>
                    <p class="text-muted-foreground mt-1">Tạo một tác giả mới</p>
                </div>
            </div>

            <Separator />

            <!-- Form -->
            <Form
                :action="authors.store().url"
                method="post"
                :data="form"
                class="max-w-3xl space-y-6"
                @success="() => toast.success('Author đã được tạo thành công.')"
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
                                <CardTitle>Thông tin Author</CardTitle>
                                <CardDescription>Nhập thông tin của tác giả</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="name">Tên Author *</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Ví dụ: Eiichiro Oda"
                                        required
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="slug">Slug (tùy chọn)</Label>
                                    <Input
                                        id="slug"
                                        v-model="form.slug"
                                        placeholder="eiichiro-oda (tự động tạo nếu để trống)"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Slug sẽ được tạo tự động từ tên nếu để trống
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="description">Mô tả</Label>
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        class="min-h-[150px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                        placeholder="Mô tả về tác giả..."
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Avatar -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Ảnh đại diện</CardTitle>
                                <CardDescription>Upload ảnh đại diện cho author</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <InputImage v-model="form.avatar" :width="300" :height="300" format="webp" />
                            </CardContent>
                        </Card>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Button type="submit" class="flex-1">Tạo Author</Button>
                            <Button type="button" variant="outline" as-child>
                                <Link :href="authors.index().url">Hủy</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
