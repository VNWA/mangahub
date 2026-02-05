<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Edit,
    Server,
    FileText,
} from 'lucide-vue-next';
import servers from '@/routes/servers';

interface Props {
    server: {
        id: number;
        name: string;
        description?: string;
        chapter_contents_count?: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Servers', href: servers.index().url },
    { title: props.server.name, href: '#' },
];
</script>

<template>
    <Head :title="server.name" />

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
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-primary/10 p-2">
                            <Server class="h-6 w-6 text-primary" />
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ server.name }}</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ server.chapter_contents_count || 0 }} chapters
                        </Badge>
                    </div>
                    <p v-if="server.description" class="text-muted-foreground mt-2">
                        {{ server.description }}
                    </p>
                </div>
                <Button as-child>
                    <Link :href="servers.edit(server.id).url">
                        <Edit class="mr-2 h-4 w-4" />
                        Chỉnh sửa
                    </Link>
                </Button>
            </div>

            <Separator />

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Thông tin Server</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Tên Server</p>
                                    <p class="text-sm font-medium mt-1">{{ server.name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Số lượng Chapter Contents</p>
                                    <p class="text-sm font-medium mt-1 flex items-center gap-1">
                                        <FileText class="h-4 w-4" />
                                        {{ server.chapter_contents_count || 0 }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="server.description" class="pt-4 border-t">
                                <p class="text-sm font-medium text-muted-foreground mb-2">Mô tả</p>
                                <p class="text-sm whitespace-pre-wrap">{{ server.description }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Chapter Contents
                            </CardTitle>
                            <CardDescription>
                                Danh sách các chapter contents được lưu trữ trên server này
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-center py-8 text-muted-foreground">
                                <FileText class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                <p>Chapter contents được quản lý trong trang quản lý Chapters</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Server Icon -->
                    <Card>
                        <CardContent class="p-8">
                            <div class="flex flex-col items-center justify-center">
                                <div class="rounded-full bg-primary/10 p-6 mb-4">
                                    <Server class="h-12 w-12 text-primary" />
                                </div>
                                <p class="text-sm font-medium">{{ server.name }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Thao tác nhanh</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button variant="outline" class="w-full justify-start" as-child>
                                <Link :href="servers.edit(server.id).url">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Chỉnh sửa Server
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
