<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft } from 'lucide-vue-next';
import InputImage from '@/components/input/InputImage.vue';
import categories from '@/routes/categories';

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Categories', href: categories.index().url },
    { title: 'Thêm mới', href: '#' },
];

const form = {
    name: '',
    slug: '',
    description: '',
    icon: '',
    avatar: '',
};
</script>

<template>
    <Head title="Thêm Category mới" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="categories.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Quay lại
                    </Link>
                </Button>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Thêm Category mới</h1>
                    <p class="text-muted-foreground mt-1">Tạo một category mới</p>
                </div>
            </div>

            <Separator />

            <Form
                :action="categories.store().url"
                method="post"
                :data="form"
                class="max-w-2xl space-y-6"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Thông tin Category</CardTitle>
                        <CardDescription>Nhập thông tin của category</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Tên Category *</Label>
                            <Input id="name" v-model="form.name" required />
                        </div>

                        <div class="space-y-2">
                            <Label for="slug">Slug (tùy chọn)</Label>
                            <Input id="slug" v-model="form.slug" />
                        </div>

                        <div class="space-y-2">
                            <Label for="icon">Icon (emoji)</Label>
                            <Input id="icon" v-model="form.icon" placeholder="📚" maxlength="2" />
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Mô tả</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Ảnh đại diện</Label>
                            <InputImage v-model="form.avatar" :width="200" :height="200" format="webp" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex gap-2">
                    <Button type="submit">Tạo Category</Button>
                    <Button type="button" variant="outline" as-child>
                        <Link :href="categories.index().url">Hủy</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
