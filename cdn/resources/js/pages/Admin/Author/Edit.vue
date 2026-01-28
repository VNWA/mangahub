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
import { ArrowLeft, Upload, X, User } from 'lucide-vue-next';
import authors from '@/routes/authors';

const toast = useToast();

interface Props {
    author: {
        id: number;
        name: string;
        slug: string;
        avatar?: string;
        description?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Authors', href: authors.index().url },
    { title: props.author.name, href: '#' },
    { title: 'Chỉnh sửa', href: '#' },
];

const form = {
    name: props.author.name,
    slug: props.author.slug,
    description: props.author.description || '',
    avatar: null as File | null,
};

const avatarPreview = ref<string | null>(props.author.avatar || null);

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.avatar = target.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(target.files[0]);
    }
};

const removeAvatar = () => {
    form.avatar = null;
    avatarPreview.value = props.author.avatar || null;
    const fileInput = document.querySelector('#avatar-input') as HTMLInputElement;
    if (fileInput) {
        fileInput.value = '';
    }
};
</script>

<template>
    <Head :title="`Chỉnh sửa - ${author.name}`" />

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
                    <h1 class="text-3xl font-bold tracking-tight">Chỉnh sửa Author</h1>
                    <p class="text-muted-foreground mt-1">{{ author.name }}</p>
                </div>
            </div>

            <Separator />

            <!-- Form -->
            <Form
                :action="authors.update(author.id).url"
                method="post"
                :data="form"
                enctype="multipart/form-data"
                class="max-w-3xl space-y-6"
                @success="() => toast.success('Author đã được cập nhật thành công.')"
                @error="(errors) => {
                    Object.values(errors).flat().forEach((message: any) => {
                        toast.error(message);
                    });
                }"
            >
                <input type="hidden" name="_method" value="PUT" />

                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Thông tin Author</CardTitle>
                                <CardDescription>Cập nhật thông tin của tác giả</CardDescription>
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
                                    <Label for="slug">Slug</Label>
                                    <Input
                                        id="slug"
                                        v-model="form.slug"
                                        placeholder="eiichiro-oda"
                                    />
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
                                <CardDescription>Upload ảnh đại diện mới</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="avatarPreview" class="relative">
                                    <img
                                        :src="avatarPreview"
                                        alt="Preview"
                                        class="w-full rounded-lg border"
                                    />
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        class="absolute right-2 top-2"
                                        @click="removeAvatar"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8">
                                    <User class="mb-2 h-12 w-12 text-muted-foreground" />
                                    <p class="text-sm text-muted-foreground text-center">
                                        Chưa có ảnh đại diện
                                    </p>
                                </div>
                                <div>
                                    <Label for="avatar-input" class="cursor-pointer">
                                        <div
                                            class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 hover:bg-muted/50 transition-colors"
                                        >
                                            <Upload class="mb-2 h-8 w-8 text-muted-foreground" />
                                            <p class="text-sm font-medium">Click để upload</p>
                                            <p class="text-xs text-muted-foreground">JPG, PNG (tối đa 2MB)</p>
                                        </div>
                                    </Label>
                                    <Input
                                        id="avatar-input"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleAvatarChange"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Button type="submit" class="flex-1">Cập nhật</Button>
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
