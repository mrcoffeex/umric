<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookMarked,
    BookOpen,
    Building2,
    GraduationCap,
    LayoutGrid,
    Megaphone,
    ScrollText,
    Target,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AnnouncementController from '@/actions/App/Http/Controllers/Admin/AnnouncementController';
import ResearchController from '@/actions/App/Http/Controllers/Admin/ResearchController';
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
import { index as facultyClassesIndex } from '@/routes/faculty/classes';
import { index as facultyResearchIndex } from '@/routes/faculty/research';
import student from '@/routes/student';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user as { role?: string } | null);
const role = computed(() => user.value?.role ?? '');
const isAdmin = computed(() => ['admin', 'staff'].includes(role.value));
const isFaculty = computed(() => role.value === 'faculty');
const isStudent = computed(() => role.value === 'student');

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
]);

const adminNavItems: NavItem[] = [
    { title: 'Research Papers', href: ResearchController.index(), icon: ScrollText },
    { title: 'Announcements', href: AnnouncementController.index(), icon: Megaphone },
    {
        title: 'Departments',
        href: admin.departments.index(),
        icon: Building2,
    },
    { title: 'Subjects', href: admin.subjects.index(), icon: BookOpen },
    { title: 'Classes', href: admin.classes.index(), icon: GraduationCap },
];

const AdminSettingsItems: NavItem[] = [
    { title: 'Users', href: admin.users.index(), icon: Users },
    { title: 'SDGs', href: admin.sdgs.index.url(), icon: Target },
    { title: 'Agendas', href: admin.agendas.index.url(), icon: BookMarked },
];

const facultyNavItems: NavItem[] = [
    { title: 'My Research', href: facultyResearchIndex(), icon: ScrollText },
    { title: 'My Classes', href: facultyClassesIndex(), icon: GraduationCap },
];

const studentNavItems: NavItem[] = [
    { title: 'Home', href: student.home(), icon: LayoutGrid },
    { title: 'My Research', href: student.research.index(), icon: ScrollText },
    { title: 'My Classes', href: student.classes.index(), icon: GraduationCap },
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
            <NavMain v-if="!isStudent" :items="mainNavItems" label="Platform" />
            <NavMain
                v-if="isAdmin"
                :items="adminNavItems"
                label="Administration"
            />
            <NavMain
                v-if="isAdmin"
                :items="AdminSettingsItems"
                label="Settings"
            />
            <NavMain
                v-if="isFaculty"
                :items="facultyNavItems"
                label="Faculty"
            />
            <NavMain
                v-if="isStudent"
                :items="studentNavItems"
                label="Student"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
