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
import comments from '@/routes/comments';
import coins from '@/routes/coins';
import users from '@/routes/users';
import reports from '@/routes/reports';
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
    MessageSquare,
    Coins,
    Users,
    AlertTriangle,
    Folder,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import files from '@/routes/files';

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
    {
        title: 'Users',
        href: users.index(),
        icon: Users,
    },
    {
        title: 'Comments',
        href: comments.index(),
        icon: MessageSquare,
    },
    {
        title: 'Coins',
        href: coins.index(),
        icon: Coins,
    },
    {
        title: 'Reports',
        href: reports.index(),
        icon: AlertTriangle,
    },
    {
        title: 'Files',
        href: files.index(),
        icon: Folder,
    },
];

const footerNavItems: NavItem[] = [

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
