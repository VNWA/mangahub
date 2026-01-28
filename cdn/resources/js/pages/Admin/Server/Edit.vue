<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Form, Head, Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ArrowLeft, Server } from 'lucide-vue-next';
import servers from '@/routes/servers';

const toast = useToast();

interface Props {
    server: {
        id: number;
        name: string;
        description?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Servers', href: servers.index().url },
    { title: props.server.name, href: '#' },
    { title: 'Chỉnh sửa', href: '#' },
];

const form = {
    name: props.server.name,
    description: props.server.description || '',
};
</script>

<template>
    <Head :title="`Chỉnh sửa - ${server.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="servers.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Quay lại
                    </Link>
                </Button>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Chỉnh sửa Server</h1>
                    <p class="text-muted-foreground mt-1">{{ server.name }}</p>
                </div>
            </div>

            <Separator />

            <!-- Form -->
            <Form
                :action="servers.update(server.id).url"
                method="post"
                :data="form"
                class="max-w-2xl space-y-6"
                @success="() => toast.success('Server đã được cập nhật thành công.')"
                @error="(errors) => {
                    Object.values(errors).flat().forEach((message: any) => {
                        toast.error(message);
                    });
                }"
            >
                <input type="hidden" name="_method" value="PUT" />

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Server class="h-5 w-5" />
                            Thông tin Server
                        </CardTitle>
                        <CardDescription>Cập nhật thông tin của server</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Tên Server *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                Tên server phải là duy nhất
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Mô tả</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                placeholder="Mô tả về server..."
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex gap-2">
                    <Button type="submit">Cập nhật</Button>
                    <Button type="button" variant="outline" as-child>
                        <Link :href="servers.index().url">Hủy</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
