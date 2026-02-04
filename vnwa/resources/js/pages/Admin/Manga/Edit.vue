<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Select } from '@/components/ui/select';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref } from 'vue';
import { ArrowLeft, Upload, X } from 'lucide-vue-next';
import mangas from '@/routes/mangas';

const toast = useToast();

interface Props {
    manga: {
        id: number;
        name: string;
        slug: string;
        avatar?: string;
        avatar_url: string;
        description?: string;
        status: string;
        manga_author_id?: number;
        manga_badge_id?: number;
        categories: Array<{ id: number }>;
    };
    authors: Array<{ id: number; name: string }>;
    badges: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Mangas', href: mangas.index().url },
    { title: props.manga.name, href: '#' },
    { title: 'Chỉnh sửa', href: '#' },
];

const form = ref({
    name: props.manga.name,
    slug: props.manga.slug,
    description: props.manga.description || '',
    manga_author_id: props.manga.manga_author_id || null,
    manga_badge_id: props.manga.manga_badge_id || null,
    status: props.manga.status,
    avatar: null as File | null,
    categories: props.manga.categories.map((c) => c.id),
});

const avatarPreview = ref<string | null>(props.manga.avatar_url);

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.value.avatar = target.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(target.files[0]);
    }
};

const removeAvatar = () => {
    form.value.avatar = null;
    avatarPreview.value = props.manga.avatar || null;
    const fileInput = document.querySelector('#avatar-input') as HTMLInputElement;
    if (fileInput) {
        fileInput.value = '';
    }
};

const submit = () => {
    const formData = new FormData();
    Object.keys(form.value).forEach((key) => {
        const value = form.value[key as keyof typeof form.value];
        if (key === 'avatar' && value) {
            formData.append(key, value as File);
        } else if (key === 'categories') {
            (value as number[]).forEach((id) => {
                formData.append('categories[]', id.toString());
            });
        } else if (value !== null && value !== '') {
            formData.append(key, value as string | number);
        }
    });

    formData.append('_method', 'PUT');

    window.axios
        .post(mangas.update(props.manga.id).url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
        .then(() => {
            toast.success('Manga đã được cập nhật thành công.');
            router.visit(mangas.index().url);
        })
        .catch((error) => {
            if (error.response?.data?.errors) {
                Object.values(error.response.data.errors).flat().forEach((message: any) => {
                    toast.error(message);
                });
            } else {
                toast.error(error.response?.data?.message || 'Có lỗi xảy ra khi cập nhật manga.');
            }
        });
};
</script>

<template>

    <Head :title="`Chỉnh sửa - ${manga.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="mangas.index().url">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Quay lại
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Chỉnh sửa Manga</h1>
                        <p class="text-muted-foreground mt-1">{{ manga.name }}</p>
                    </div>
                </div>
            </div>

            <Separator />

            <!-- Form -->
            <Form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Basic Info -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Thông tin cơ bản</CardTitle>
                                <CardDescription>Cập nhật thông tin của manga</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="name">Tên Manga *</Label>
                                    <Input id="name" v-model="form.name" placeholder="Ví dụ: One Piece" required />
                                </div>

                                <div class="space-y-2">
                                    <Label for="slug">Slug</Label>
                                    <Input id="slug" v-model="form.slug" placeholder="one-piece" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="description">Mô tả</Label>
                                    <textarea id="description" v-model="form.description"
                                        class="min-h-[150px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                        placeholder="Mô tả về manga..." />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Categories -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Categories</CardTitle>
                                <CardDescription>Chọn các thể loại cho manga</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label v-for="category in props.categories" :key="category.id"
                                        class="flex items-center space-x-2 rounded-lg border p-3 cursor-pointer hover:bg-muted/50 transition-colors">
                                        <input type="checkbox" :value="category.id" v-model="form.categories"
                                            class="rounded border-gray-300" />
                                        <span class="text-sm font-medium">{{ category.name }}</span>
                                    </label>
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
                                    <img :src="avatarPreview" alt="Preview" class="w-full rounded-lg border" />
                                    <Button type="button" variant="destructive" size="sm" class="absolute right-2 top-2"
                                        @click="removeAvatar">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <div>
                                    <Label for="avatar-input" class="cursor-pointer">
                                        <div
                                            class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 hover:bg-muted/50 transition-colors">
                                            <Upload class="mb-2 h-8 w-8 text-muted-foreground" />
                                            <p class="text-sm font-medium">Click để upload</p>
                                            <p class="text-xs text-muted-foreground">JPG, PNG (tối đa 2MB)</p>
                                        </div>
                                    </Label>
                                    <Input id="avatar-input" type="file" accept="image/*" class="hidden"
                                        @change="handleAvatarChange" />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Settings -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Cài đặt</CardTitle>
                                <CardDescription>Các tùy chọn khác</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="status">Trạng thái *</Label>
                                    <Select id="status" v-model="form.status" :options="[
                                        { value: 'ongoing', label: 'Đang ra' },
                                        { value: 'completed', label: 'Hoàn thành' },
                                        { value: 'hiatus', label: 'Tạm ngưng' },
                                        { value: 'cancelled', label: 'Hủy' },
                                    ]" required />
                                </div>

                                <div class="space-y-2">
                                    <Label for="author">Tác giả</Label>
                                    <Select id="author" v-model="form.manga_author_id" :options="[
                                        { value: null, label: 'Chọn tác giả' },
                                        ...props.authors.map((author) => ({
                                            value: author.id,
                                            label: author.name,
                                        })),
                                    ]" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="badge">Badge</Label>
                                    <Select id="badge" v-model="form.manga_badge_id" :options="[
                                        { value: null, label: 'Chọn badge' },
                                        ...props.badges.map((badge) => ({
                                            value: badge.id,
                                            label: badge.name,
                                        })),
                                    ]" />
                                </div>

                            </CardContent>
                        </Card>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Button type="submit" class="flex-1">Cập nhật</Button>
                            <Button type="button" variant="outline" as-child>
                                <Link :href="mangas.index().url">Hủy</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
