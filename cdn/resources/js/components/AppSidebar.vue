<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import mangas from '@/routes/mangas';
import categories from '@/routes/categories';
import authors from '@/routes/authors';
import badges from '@/routes/badges';
import servers from '@/routes/servers';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    BookOpen,
    FolderTree,
    User,
    Award,
    Server,
    FileText,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Mangas',
        href: mangas.index(),
        icon: BookOpen,
    },
    {
        title: 'Categories',
        href: categories.index(),
        icon: FolderTree,
    },
    {
        title: 'Authors',
        href: authors.index(),
        icon: User,
    },
    {
        title: 'Badges',
        href: badges.index(),
        icon: Award,
    },
    {
        title: 'Servers',
        href: servers.index(),
        icon: Server,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FileText,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
