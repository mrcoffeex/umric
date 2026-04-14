<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { GraduationCap, Users, AlertTriangle, CheckCircle2 } from 'lucide-vue-next';
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
        subjects: Array<{ id: number; code: string; name: string; program: { name: string; code: string } | null }>;
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
        <div class="w-full max-w-lg rounded-2xl border border-sidebar-border/70 bg-white dark:bg-sidebar shadow-sm overflow-hidden flex flex-col">
            <div class="h-1.5 bg-gradient-to-r from-orange-500 to-teal-500 w-full shrink-0"></div>

            <div class="p-8 flex flex-col items-center">
                <div class="rounded-2xl bg-orange-50 dark:bg-orange-950/30 h-16 w-16 flex items-center justify-center mx-auto mb-5">
                    <GraduationCap class="h-10 w-10 text-orange-500" />
                </div>

                <h1 class="text-2xl font-black text-center text-slate-900 dark:text-white">{{ props.schoolClass.name }}</h1>

                <div class="flex flex-wrap justify-center gap-1.5 mt-3">
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Sec {{ props.schoolClass.section }}</span>
                    <span v-if="props.schoolClass.semester" class="rounded-full px-2 py-0.5 text-[10px] font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">{{ props.schoolClass.semester === 1 ? '1st Sem' : '2nd Sem' }}</span>
                    <span v-if="props.schoolClass.school_year" class="rounded-full px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ props.schoolClass.school_year }}</span>
                    <span v-for="subject in props.schoolClass.subjects" :key="subject.id" class="rounded-full px-2 py-0.5 text-[10px] font-semibold bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">{{ subject.code }}</span>
                </div>

                <p class="text-center text-sm text-muted-foreground mt-3">
                    Taught by <span class="font-medium text-foreground">{{ props.schoolClass.faculty_name ?? 'Faculty' }}</span>
                </p>

                <p v-if="props.schoolClass.description" class="text-center text-sm text-muted-foreground mt-4 max-w-sm">
                    {{ props.schoolClass.description }}
                </p>

                <hr class="my-6 border-sidebar-border/50 w-full" />

                <div class="flex items-center gap-1.5 text-sm text-muted-foreground mb-6">
                    <Users class="h-4 w-4" />
                    <span>{{ props.schoolClass.members_count }} students enrolled</span>
                </div>

                <div class="w-full">
                    <div v-if="!props.schoolClass.is_active" class="rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 p-4 text-center flex flex-col items-center">
                        <AlertTriangle class="h-6 w-6 text-amber-500 mb-2" />
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-400">This class is no longer accepting new members.</p>
                    </div>

                    <div v-else-if="props.already_joined" class="rounded-xl bg-teal-50 dark:bg-teal-950/20 border border-teal-200 dark:border-teal-800 p-5 text-center flex flex-col items-center">
                        <CheckCircle2 class="h-8 w-8 text-teal-500 mb-3" />
                        <p class="text-base font-bold text-teal-900 dark:text-teal-300 mb-4">You're already enrolled in this class.</p>
                        <Button as-child variant="outline" class="w-full font-semibold border-teal-200 dark:border-teal-800 text-teal-800 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/50">
                            <Link :href="dashboard()">Go to Dashboard</Link>
                        </Button>
                    </div>

                    <div v-else>
                        <Button
                            @click="joinClass"
                            :disabled="form.processing"
                            class="w-full h-12 text-base font-bold bg-orange-500 text-white hover:bg-orange-600 border-0 shadow-lg shadow-orange-500/20 transition-all rounded-xl"
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
