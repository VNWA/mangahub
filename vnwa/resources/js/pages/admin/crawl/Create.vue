<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import crawl from '@/routes/crawl';
import { ref } from 'vue';
import { useToast } from 'vue-toastification';
import axios from '@/axios';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
const CRAWL_API_BASE = import.meta.env.VITE_CRAWL_API_URL || 'http://localhost:3000';

const toast = useToast();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Crawl', href: crawl.index().url },
    { title: 'Create', href: crawl.create().url },
];

const formData = ref({
    crawlUrl: '',
});
const resetForm = () => {
    formData.value = {
        crawlUrl: '',
    };
};
const isLoading = ref(false);
const handleCrawl = async () => {
    if (!formData.value.crawlUrl) {
        toast.error('Vui lòng nhập URL');
        return;
    }

    // Validate URL format
    try {
        new URL(formData.value.crawlUrl);
    } catch {
        toast.error('URL không hợp lệ');
        return;
    }

    try {
        isLoading.value = true;
        toast.info('Đang kiểm tra service và thêm vào hàng đợi crawl...');
        const response = await axios.post(`${CRAWL_API_BASE}/api/crawl-mangas`, {
            crawlUrl: formData.value.crawlUrl,
        });

        const manga = response.data;
        toast.success('Thêm URL thành công! Trạng thái: ' + manga.status);

        resetForm();
    } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.message;
        toast.error('Không thể tạo crawl manga: ' + errorMessage);
    } finally {
        isLoading.value = false;
    }
};

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Thêm Crawl Manga" />

        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="crawl.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Quay lại
                    </Link>
                </Button>
            </div>
            <Card>
                <CardHeader>
                    <CardTitle>Thông tin Crawl</CardTitle>
                    <CardDescription>Nhập URL manga để tự động detect source và crawl detail</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="crawlUrl">Manga URL *</Label>
                            <Input id="crawlUrl" v-model="formData.crawlUrl"
                                placeholder="https://manga18.me/manga/wireless-onahole" />
                            <p class="text-xs text-muted-foreground">
                                Hệ thống sẽ tự động kiểm tra domain có được hỗ trợ và crawl detail
                            </p>
                        </div>
                    </div>
                </CardContent>
                <CardFooter>
                    <Button @click="handleCrawl" :disabled="isLoading">
                        <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                        Tiến hành crawl
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
