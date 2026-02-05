<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import config from '@/routes/config';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Appearance',
        href: config.settings.file('appearance').url,
    },
    {
        title: 'Site Assets',
        href: config.settings.file('site-assets').url,
    },

];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0" aria-label="Config Settings">
                    <Button v-for="item in sidebarNavItems" :key="toUrl(item.href)" variant="ghost" :class="[
                        'w-full justify-start',
                        { 'bg-muted': isCurrentUrl(item.href) },
                    ]" as-child>
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1">
                <section class=" space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
