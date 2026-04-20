<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    GraduationCap,
    Users,
    AlertTriangle,
    CheckCircle2,
} from 'lucide-vue-next';
import ClassJoinController from '@/actions/App/Http/Controllers/Faculty/ClassJoinController';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type Props = {
    schoolClass: {
        id: number;
        name: string;
        section: string;
        school_year: string | null;
        semester: number | null;
        description: string | null;
        is_active: boolean;
        faculty_name: string | null;
        members_count: number;
        subjects: Array<{
            id: number;
            code: string;
            name: string;
            program: { name: string; code: string } | null;
        }>;
    };
    join_code: string;
    already_joined: boolean;
};

const props = defineProps<Props>();

const form = useForm({});

const joinClass = () => {
    form.post(ClassJoinController.store.url({ code: props.join_code }));
};
</script>

<template>
    <Head :title="`Join Class: ${props.schoolClass.name}`" />

    <div class="flex min-h-[70vh] items-center justify-center p-4">
        <div
            class="flex w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-white shadow-sm dark:bg-sidebar"
        >
            <div
                class="h-1.5 w-full shrink-0 bg-gradient-to-r from-orange-500 to-teal-500"
            ></div>

            <div class="flex flex-col items-center p-8">
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                >
                    <GraduationCap class="h-10 w-10 text-orange-500" />
                </div>

                <h1
                    class="text-center text-2xl font-black text-slate-900 dark:text-white"
                >
                    {{ props.schoolClass.name }}
                </h1>

                <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                    <span
                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >Sec {{ props.schoolClass.section }}</span
                    >
                    <span
                        v-if="props.schoolClass.semester"
                        class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
                        >{{
                            props.schoolClass.semester === 1
                                ? '1st Sem'
                                : '2nd Sem'
                        }}</span
                    >
                    <span
                        v-if="props.schoolClass.school_year"
                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >{{ props.schoolClass.school_year }}</span
                    >
                    <span
                        v-for="subject in props.schoolClass.subjects"
                        :key="subject.id"
                        class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-900/30 dark:text-violet-400"
                        >{{ subject.code }}</span
                    >
                </div>

                <p class="mt-3 text-center text-sm text-muted-foreground">
                    Taught by
                    <span class="font-medium text-foreground">{{
                        props.schoolClass.faculty_name ?? 'Faculty'
                    }}</span>
                </p>

                <p
                    v-if="props.schoolClass.description"
                    class="mt-4 max-w-sm text-center text-sm text-muted-foreground"
                >
                    {{ props.schoolClass.description }}
                </p>

                <hr class="my-6 w-full border-sidebar-border/50" />

                <div
                    class="mb-6 flex items-center gap-1.5 text-sm text-muted-foreground"
                >
                    <Users class="h-4 w-4" />
                    <span
                        >{{ props.schoolClass.members_count }} students
                        enrolled</span
                    >
                </div>

                <div class="w-full">
                    <div
                        v-if="!props.schoolClass.is_active"
                        class="flex flex-col items-center rounded-xl border border-amber-200 bg-amber-50 p-4 text-center dark:border-amber-800 dark:bg-amber-950/20"
                    >
                        <AlertTriangle class="mb-2 h-6 w-6 text-amber-500" />
                        <p
                            class="text-sm font-medium text-amber-800 dark:text-amber-400"
                        >
                            This class is no longer accepting new members.
                        </p>
                    </div>

                    <div
                        v-else-if="props.already_joined"
                        class="flex flex-col items-center rounded-xl border border-teal-200 bg-teal-50 p-5 text-center dark:border-teal-800 dark:bg-teal-950/20"
                    >
                        <CheckCircle2 class="mb-3 h-8 w-8 text-teal-500" />
                        <p
                            class="mb-4 text-base font-bold text-teal-900 dark:text-teal-300"
                        >
                            You're already enrolled in this class.
                        </p>
                        <Button
                            as-child
                            variant="outline"
                            class="w-full border-teal-200 font-semibold text-teal-800 hover:bg-teal-100 dark:border-teal-800 dark:text-teal-300 dark:hover:bg-teal-900/50"
                        >
                            <Link :href="dashboard()">Go to Dashboard</Link>
                        </Button>
                    </div>

                    <div v-else>
                        <Button
                            @click="joinClass"
                            :disabled="form.processing"
                            class="h-12 w-full rounded-xl border-0 bg-orange-500 text-base font-bold text-white shadow-lg shadow-orange-500/20 transition-all hover:bg-orange-600"
                        >
                            <span v-if="form.processing">Joining...</span>
                            <span v-else>Join This Class</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
