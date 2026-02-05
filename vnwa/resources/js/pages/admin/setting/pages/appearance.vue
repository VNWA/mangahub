<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfigLayout from '@/layouts/ConfigLayout.vue';
import config from '@/routes/config';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { reactive, ref, onMounted } from 'vue';
import InputImage from '@/components/input/InputImage.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Config Settings',
        href: config.settings.index().url,
    },
    {
        title: 'Appearance Settings',
        href: config.settings.load('appearance').url,
    },
];
interface T {
    logo_icon: string;
    logo_full: string;
    favicon: string;
}
const form = reactive<T>({
    logo_icon: '',
    logo_full: '',
    favicon: '',
});
const loading = ref(false);
const loadData = async () => {
    loading.value = true;
    await axios.get(config.settings.load('appearance').url).then(res => {
        form.logo_icon = res.data.data.logo_icon || '';
        form.logo_full = res.data.data.logo_full || '';
        form.favicon = res.data.data.favicon || '';
    }).catch(e => {
        $toast(e.response.data.message, 'error');
    }).finally(() => {
        loading.value = false;
    });
}


const submit = async () => {
    loading.value = true;
    await axios.post(config.settings.update('appearance').url, form).then(res => {
        $toast(res.data.message, 'success');
        $tempDelete([form.logo_icon, form.logo_full, form.favicon]);
    }).catch(e => {
        $toast(e.response.data.message, 'error');
    }).finally(() => {
        loading.value = false;
    });
};
onMounted(() => {
    loadData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Appearance Settings" />

        <h1 class="sr-only">Appearance Settings</h1>

        <ConfigLayout>
            <div class="space-y-6">
                <Heading variant="small" title="Appearance Settings" description="Update website appearance settings" />

                <Form @submit.prevent="submit" class="space-y-6">

                    <div class="space-y-6">
                        <!-- Avatar -->

                        <div class="grid gap-6 lg:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Logo Icon</CardTitle>
                                    <CardDescription>Upload logo icon cho website</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <InputImage v-model="form.logo_icon" :width="100" :height="100" format="webp" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Logo Full</CardTitle>
                                    <CardDescription>Upload logo full cho website</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <InputImage v-model="form.logo_full" :width="300" :height="60" format="webp" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Favicon</CardTitle>
                                    <CardDescription>Upload favicon cho website</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <InputImage v-model="form.favicon" type="ico" />
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
