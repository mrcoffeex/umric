<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookMarked,
    BookOpen,
    Building2,
    GraduationCap,
    LayoutGrid,
    ScrollText,
    Target,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user as { role?: string } | null);
const role = computed(() => user.value?.role ?? '');
const isAdmin = computed(() => ['admin', 'staff'].includes(role.value));

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    {
        title: role.value === 'student' ? 'My Proposals' : 'Research Papers',
        href: '/papers',
        icon: ScrollText,
    },
]);

const adminNavItems: NavItem[] = [
    {
        title: 'Departments & Programs',
        href: admin.departments.index(),
        icon: Building2,
    },
    { title: 'Subjects', href: admin.subjects.index(), icon: BookOpen },
    { title: 'Classes', href: admin.classes.index(), icon: GraduationCap },
    { title: 'SDGs', href: admin.sdgs.index.url(), icon: Target },
    { title: 'Agendas', href: admin.agendas.index.url(), icon: BookMarked },
    { title: 'Users', href: admin.users.index(), icon: Users },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs',
        icon: BookOpen,
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
            <NavMain :items="mainNavItems" label="Platform" />
            <NavMain
                v-if="isAdmin"
                :items="adminNavItems"
                label="Administration"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
