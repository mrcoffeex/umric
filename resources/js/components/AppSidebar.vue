<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookMarked,
    Building2,
    CalendarDays,
    ClipboardList,
    Clock,
    ListChecks,
    SlidersHorizontal,
    ExternalLink,
    GraduationCap,
    Image,
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
import { dashboard, home } from '@/routes';
import admin from '@/routes/admin';
import documentTransmissions from '@/routes/document-transmissions';
import faculty from '@/routes/faculty';
import { index as facultyClassesIndex } from '@/routes/faculty/classes';
import { index as facultyDefenseCalendarIndex } from '@/routes/faculty/defense-calendar';
import { index as facultyResearchIndex } from '@/routes/faculty/research';
import student from '@/routes/student';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user as { role?: string } | null);
const role = computed(() => user.value?.role ?? '');
const isAdmin = computed(() => ['admin', 'staff'].includes(role.value));
const isAdminOnly = computed(() => role.value === 'admin');
const isFaculty = computed(() => role.value === 'faculty');
const isStudent = computed(() => role.value === 'student');

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
]);

const roleBadge = computed(() => {
    const map: Record<string, { label: string; dot: string }> = {
        admin: { label: 'Admin', dot: 'bg-orange-500' },
        staff: { label: 'Staff', dot: 'bg-amber-500' },
        faculty: { label: 'Faculty', dot: 'bg-teal-500' },
        student: { label: 'Student', dot: 'bg-blue-500' },
    };

    return map[role.value] ?? { label: role.value, dot: 'bg-muted-foreground' };
});

const handoffPendingCount = computed(
    () => page.props.documentHandoffs?.incomingPending ?? 0,
);

function withHandoffBadge(base: readonly NavItem[], count: number): NavItem[] {
    return base.map((item) =>
        item.title === 'Handoffs' ? { ...item, badgeCount: count } : item,
    );
}

const adminNavItems = computed(() =>
    withHandoffBadge(
        [
            {
                title: 'Research Papers',
                href: ResearchController.index(),
                icon: ScrollText,
            },
            {
                title: 'Announcements',
                href: AnnouncementController.index(),
                icon: Megaphone,
            },
            {
                title: 'Defense Calendar',
                href: admin.defenseCalendar.index.url(),
                icon: CalendarDays,
            },
            {
                title: 'Evaluation',
                href: admin.evaluation.index.url(),
                icon: ListChecks,
            },
            {
                title: 'Classes',
                href: admin.classes.index(),
                icon: GraduationCap,
            },
            {
                title: 'Handoffs',
                href: documentTransmissions.index.url(),
                icon: ClipboardList,
            },
        ],
        handoffPendingCount.value,
    ),
);

const AdminSettingsItems: NavItem[] = [
    {
        title: 'Departments',
        href: admin.departments.index(),
        icon: Building2,
    },
    { title: 'Subjects', href: admin.subjects.index(), icon: ScrollText },
    { title: 'SDGs', href: admin.sdgs.index.url(), icon: Target },
    { title: 'Agendas', href: admin.agendas.index.url(), icon: BookMarked },
    {
        title: 'Evaluation Formats',
        href: admin.evaluationFormats.index.url(),
        icon: SlidersHorizontal,
    },
    {
        title: 'Site branding',
        href: admin.branding.index.url(),
        icon: Image,
    },
];

const adminOnlyItems: NavItem[] = [
    { title: 'Users', href: admin.users.index(), icon: Users },
    {
        title: 'System Logs',
        href: admin.activityLogs.index(),
        icon: Clock,
    },
];

const facultyNavItems = computed(() =>
    withHandoffBadge(
        [
            {
                title: 'Research Papers',
                href: facultyResearchIndex(),
                icon: ScrollText,
            },
            {
                title: 'Defense Calendar',
                href: facultyDefenseCalendarIndex(),
                icon: CalendarDays,
            },
            {
                title: 'Evaluation',
                href: faculty.evaluation.index.url(),
                icon: ListChecks,
            },
            {
                title: 'My Classes',
                href: facultyClassesIndex(),
                icon: GraduationCap,
            },
            {
                title: 'Handoffs',
                href: documentTransmissions.index.url(),
                icon: ClipboardList,
            },
        ],
        handoffPendingCount.value,
    ),
);

const studentNavItems = computed(() =>
    withHandoffBadge(
        [
            { title: 'Home', href: student.home(), icon: LayoutGrid },
            {
                title: 'My Research',
                href: student.research.index(),
                icon: ScrollText,
            },
            {
                title: 'Defense Calendar',
                href: student.defenseCalendar.index(),
                icon: CalendarDays,
            },
            {
                title: 'My Classes',
                href: student.classes.index(),
                icon: GraduationCap,
            },
            {
                title: 'Handoffs',
                href: documentTransmissions.index.url(),
                icon: ClipboardList,
            },
        ],
        handoffPendingCount.value,
    ),
);
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
            <NavMain v-if="!isStudent" :items="mainNavItems" />
            <NavMain
                v-if="isAdmin"
                :items="adminNavItems"
                label="Administration"
            />
            <NavMain
                v-if="isAdmin"
                :items="AdminSettingsItems"
                label="Configuration"
            />
            <NavMain
                v-if="isAdminOnly"
                :items="adminOnlyItems"
                label="User Management"
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
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <a
                            :href="home.url()"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-2"
                        >
                            <ExternalLink class="h-4 w-4" />
                            <span>Landing Page</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <div class="px-2 pb-1 group-data-[collapsible=icon]:hidden">
                <div
                    class="flex items-center gap-2 rounded-lg bg-sidebar-accent px-2.5 py-1.5"
                >
                    <span
                        class="size-1.5 shrink-0 rounded-full"
                        :class="roleBadge.dot"
                    />
                    <span
                        class="text-xs font-medium text-sidebar-accent-foreground"
                        >{{ roleBadge.label }}</span
                    >
                </div>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
