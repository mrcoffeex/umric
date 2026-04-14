<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Camera, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import TagsInput from '@/components/TagsInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, details, avatar as avatarRoute } from '@/routes/profile';
import { remove as removeAvatar } from '@/routes/profile/avatar';
import { send } from '@/routes/verification';

type ProfileData = {
    role: string | null;
    department_id: number | null;
    program_id: number | null;
    specialization: string | null;
    institution: string | null;
    degree: string | null;
    graduation_year: string | null;
    bio: string | null;
    avatar_url: string | null;
};

type Program = { id: number; name: string; department_id: number };
type Department = { id: number; name: string; programs: Program[] };

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    departments: Department[];
    profile?: ProfileData | null;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => (page.props.auth.user as any).role ?? 'student');

// Initials fallback
function initials(name: string) {
    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}

// Avatar
const avatarPreview = ref<string | null>(props.profile?.avatar_url ?? null);
const avatarInput = ref<HTMLInputElement | null>(null);
const avatarForm = useForm({ avatar: null as File | null });
const removingAvatar = ref(false);

function pickAvatar() {
    avatarInput.value?.click();
}

function onAvatarChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    avatarPreview.value = URL.createObjectURL(file);
    avatarForm.avatar = file;
    avatarForm.post(avatarRoute.url(), {
        forceFormData: true,
        onError: () => {
            avatarPreview.value = props.profile?.avatar_url ?? null;
        },
    });
}

function onRemoveAvatar() {
    if (!confirm('Remove your profile photo?')) {
        return;
    }

    removingAvatar.value = true;
    useForm({}).delete(removeAvatar.url(), {
        onFinish: () => {
            removingAvatar.value = false;
            avatarPreview.value = null;
        },
    });
}

// Profile details form
const selectedDept = ref<number | ''>(props.profile?.department_id ?? '');

const filteredPrograms = computed(() => {
    if (!selectedDept.value) {
        return [];
    }

    const dept = props.departments.find(
        (d) => d.id === Number(selectedDept.value),
    );

    return dept?.programs ?? [];
});

const detailsForm = useForm({
    name: (user.value as any).name ?? '',
    email: (user.value as any).email ?? '',
    bio: props.profile?.bio ?? '',
    specialization: props.profile?.specialization ?? '',
    institution: props.profile?.institution ?? '',
    department_id: props.profile?.department_id ?? ('' as number | ''),
    program_id: props.profile?.program_id ?? ('' as number | ''),
});

function submitDetails() {
    detailsForm.patch(details.url());
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile settings</h1>

        <section class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div class="flex items-center gap-5">
                    <div class="group relative shrink-0 cursor-pointer">
                        <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-orange-400 to-teal-400 shadow-sm ring-1 ring-border">
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                alt="Avatar"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex flex-col items-center justify-center text-white"
                            >
                                <span class="text-2xl font-bold leading-none">
                                    {{ initials(user.name) }}
                                </span>
                                <span class="mt-1 text-[10px] font-medium leading-none tracking-wide uppercase">
                                    {{ role }}
                                </span>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="pickAvatar"
                            class="absolute inset-0 flex items-center justify-center rounded-full bg-black/60 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                            :disabled="avatarForm.processing"
                        >
                            <Camera class="h-5 w-5 text-white" />
                        </button>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold leading-tight text-foreground">{{ user.name }}</h2>
                        <span class="mt-1.5 inline-flex items-center rounded-full bg-orange-500/15 px-2.5 py-0.5 text-xs font-semibold capitalize text-orange-600 dark:text-orange-400">{{ role }}</span>
                        <p v-if="props.profile?.bio" class="mt-2 line-clamp-2 max-w-xs text-sm text-muted-foreground">{{ props.profile.bio }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:flex-col sm:items-end">
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="pickAvatar"
                            :disabled="avatarForm.processing"
                        >
                            {{ avatarForm.processing ? 'Uploading…' : 'Upload photo' }}
                        </Button>
                        <Button
                            v-if="avatarPreview"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="text-muted-foreground hover:text-destructive"
                            @click="onRemoveAvatar"
                            :disabled="removingAvatar"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                    <p class="text-[0.75rem] text-muted-foreground">JPEG, PNG or WebP · max 2 MB</p>
                    <p v-if="avatarForm.errors.avatar" class="text-xs text-destructive">{{ avatarForm.errors.avatar }}</p>
                </div>

                <input
                    ref="avatarInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="onAvatarChange"
                />
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Profile information</p>
                <p class="mt-0.5 text-sm text-muted-foreground">Update your personal and academic details.</p>
            </div>

            <form @submit.prevent="submitDetails">
                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="detailsForm.name"
                            class="block w-full"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError :message="detailsForm.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="detailsForm.email"
                            class="block w-full"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError :message="detailsForm.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at" class="md:col-span-2">
                        <p class="text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div v-if="role === 'student' || role === 'faculty'" class="grid gap-2">
                        <Label for="department_id">Department</Label>
                        <select
                            id="department_id"
                            v-model="selectedDept"
                            @change="
                                detailsForm.department_id = selectedDept as
                                    | number
                                    | '';
                                detailsForm.program_id = '';
                            "
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                        >
                            <option value="">Select department…</option>
                            <option
                                v-for="dept in departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                        <InputError :message="detailsForm.errors.department_id" />
                    </div>

                    <div v-if="role === 'student'" class="grid gap-2">
                        <Label for="program_id">Program</Label>
                        <select
                            id="program_id"
                            v-model="detailsForm.program_id"
                            :disabled="!selectedDept"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:ring-2 focus:ring-ring focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">
                                {{
                                    selectedDept
                                        ? 'Select program…'
                                        : 'Select a department first'
                                }}
                            </option>
                            <option
                                v-for="prog in filteredPrograms"
                                :key="prog.id"
                                :value="prog.id"
                            >
                                {{ prog.name }}
                            </option>
                        </select>
                        <InputError :message="detailsForm.errors.program_id" />
                    </div>

                    <div v-if="role !== 'student'" class="grid gap-2">
                        <Label for="specialization">Specialization</Label>
                        <TagsInput
                            v-model="detailsForm.specialization"
                            placeholder="e.g. Machine Learning"
                        />
                        <InputError :message="detailsForm.errors.specialization" />
                    </div>

                    <div v-if="role !== 'student'" class="grid gap-2">
                        <Label for="institution">Institution</Label>
                        <Input
                            id="institution"
                            v-model="detailsForm.institution"
                            placeholder="e.g. UM Digos College"
                        />
                        <InputError :message="detailsForm.errors.institution" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="bio">Bio</Label>
                        <textarea
                            id="bio"
                            v-model="detailsForm.bio"
                            rows="3"
                            maxlength="1000"
                            placeholder="Tell us a bit about yourself…"
                            class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-0 focus:outline-none"
                        />
                        <InputError :message="detailsForm.errors.bio" />
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-border bg-muted/30 px-6 py-3">
                    <p v-if="detailsForm.recentlySuccessful" class="text-sm font-medium text-teal-600 dark:text-teal-400">Saved!</p>
                    <span v-else></span>
                    <Button
                        type="submit"
                        :disabled="detailsForm.processing"
                        data-test="update-profile-button"
                    >
                        {{ detailsForm.processing ? 'Saving…' : 'Save' }}
                    </Button>
                </div>
            </form>
        </section>

        <div class="overflow-hidden rounded-lg border border-destructive/30 bg-destructive/5">
            <div class="border-b border-destructive/20 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-destructive/70">Danger zone</p>
            </div>
            <div class="p-6">
                <DeleteUser />
            </div>
        </div>
    </div>
</template>
