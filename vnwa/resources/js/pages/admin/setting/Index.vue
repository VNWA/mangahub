<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfigLayout from '@/layouts/ConfigLayout.vue';
import config from '@/routes/config';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Config Settings',
        href: config.settings.index().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Config Settings" />

        <h1 class="sr-only">Config Settings</h1>

        <ConfigLayout>
            <div class="space-y-6">
                <Heading variant="small" title="Config Settings" description="Update website config settings" />
                <AppearanceTabs />
            </div>
        </ConfigLayout>
    </AppLayout>
</template>
