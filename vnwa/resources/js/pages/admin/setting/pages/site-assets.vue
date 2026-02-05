<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfigLayout from '@/layouts/ConfigLayout.vue';
import config from '@/routes/config';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { reactive, ref, onMounted } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Config Settings',
        href: config.settings.index().url,
    },
    {
        title: 'Site Assets Settings',
        href: config.settings.load('site-assets').url,
    },
];
interface T {
    head: string;
    body_start: string;
    body_end: string;
}
const form = reactive<T>({
    head: '',
    body_start: '',
    body_end: '',
});
const loading = ref(false);
const loadData = async () => {
    loading.value = true;
    await axios.get(config.settings.load('site-assets').url).then(res => {
        form.body_start = res.data.data.body_start || '';
        form.body_end = res.data.data.body_end || '';
        form.head = res.data.data.head || '';
    }).catch(e => {
        $toast(e.response.data.message, 'error');
    }).finally(() => {
        loading.value = false;
    });
}
const submit = async () => {
    loading.value = true;
    await axios.post(config.settings.update('site-assets').url, form).then(res => {
        $toast(res.data.message, 'success');
    }).catch(e => {
        $toast(e.response.data.message, 'error');
    }).finally(() => {
        loading.value = false;
    });
}
onMounted(() => {
    loadData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Site Assets Settings" />

        <h1 class="sr-only">Site Assets Settings</h1>

        <ConfigLayout>
            <div class="space-y-6">
                <Heading variant="small" title="Site Assets Settings"
                    description="Update website site assets settings" />
                <Form @submit.prevent="submit" class="space-y-6">

                    <div class="space-y-6">
                        <!-- Avatar -->

                        <div class="grid gap-6 grid-cols-1">

                            <Card>
                                <CardHeader>
                                    <CardTitle>Head Script</CardTitle>
                                    <CardDescription>Upload head script cho website</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <Textarea v-model="form.head" rows="20" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Body Start Script</CardTitle>
                                    <CardDescription>Upload body script ngay trước thẻ body</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <Textarea v-model="form.body_start" rows="20" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Body End Script</CardTitle>
                                    <CardDescription>Upload body end script sau thẻ body</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <Textarea v-model="form.body_end" rows="20" />
                                </CardContent>
                            </Card>
                        </div>


                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Button :disabled="loading" type="submit" class="flex-1">Save</Button>

                        </div>
                    </div>

                </Form>
            </div>

        </ConfigLayout>
    </AppLayout>
</template>
