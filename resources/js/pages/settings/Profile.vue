<script setup lang="ts">
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Camera, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
    bio: props.profile?.bio ?? '',
    specialization: props.profile?.specialization ?? '',
    institution: props.profile?.institution ?? '',
    degree: props.profile?.degree ?? '',
    graduation_year: props.profile?.graduation_year ?? '',
    department_id: props.profile?.department_id ?? ('' as number | ''),
    program_id: props.profile?.program_id ?? ('' as number | ''),
});

function submitDetails() {
    detailsForm.patch(details.url());
}
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-10">
        <!-- Avatar -->
        <div>
            <Heading
                variant="small"
                title="Profile photo"
                description="Upload a photo to personalize your account."
            />
            <div class="mt-4 flex items-center gap-5">
                <div class="group relative">
                    <div
                        class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-orange-400 to-teal-400 ring-2 ring-orange-200 dark:ring-orange-800/50"
                    >
                        <img
                            v-if="avatarPreview"
                            :src="avatarPreview"
                            alt="Avatar"
                            class="h-full w-full object-cover"
                        />
                        <span v-else class="text-2xl font-bold text-white">{{
                            initials(user.name)
                        }}</span>
                    </div>
                    <button
                        type="button"
                        @click="pickAvatar"
                        class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                        :disabled="avatarForm.processing"
                    >
                        <Camera class="h-5 w-5 text-white" />
                    </button>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="pickAvatar"
                            :disabled="avatarForm.processing"
                        >
                            {{
                                avatarForm.processing
                                    ? 'Uploading…'
                                    : 'Upload photo'
                            }}
                        </Button>
                        <Button
                            v-if="avatarPreview"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="text-muted-foreground hover:text-red-500"
                            @click="onRemoveAvatar"
                            :disabled="removingAvatar"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        JPEG, PNG or WebP · max 2 MB
                    </p>
                    <p
                        v-if="avatarForm.errors.avatar"
                        class="text-xs text-red-500"
                    >
                        {{ avatarForm.errors.avatar }}
                    </p>
                </div>
                <input
                    ref="avatarInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="onAvatarChange"
                />
            </div>
        </div>

        <!-- Name & email -->
        <div>
            <Heading
                variant="small"
                title="Account information"
                description="Update your name and email address"
            />

            <Form
                v-bind="ProfileController.update.form()"
                class="mt-4 space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        class="mt-1 block w-full"
                        name="name"
                        :default-value="user.name"
                        required
                        autocomplete="name"
                        placeholder="Full name"
                    />
                    <InputError class="mt-2" :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        name="email"
                        :default-value="user.email"
                        required
                        autocomplete="username"
                        placeholder="Email address"
                    />
                    <InputError class="mt-2" :message="errors.email" />
                </div>

                <div v-if="mustVerifyEmail && !user.email_verified_at">
                    <p class="-mt-4 text-sm text-muted-foreground">
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
                        A new verification link has been sent to your email
                        address.
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="update-profile-button"
                        >Save</Button
                    >
                </div>
            </Form>
        </div>

        <!-- Profile details -->
        <div>
            <Heading
                variant="small"
                :title="
                    role === 'student' || role === 'faculty'
                        ? 'Academic Information'
                        : 'Professional Information'
                "
                :description="
                    role === 'student'
                        ? 'Your department, program, and academic details.'
                        : role === 'faculty'
                          ? 'Your department affiliation and research focus.'
                          : 'Your professional details and research focus.'
                "
            />

            <form @submit.prevent="submitDetails" class="mt-4 space-y-5">
                <!-- Department + Program: student & faculty only -->
                <div
                    v-if="role === 'student' || role === 'faculty'"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <div class="grid gap-2">
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
                        <InputError
                            :message="detailsForm.errors.department_id"
                        />
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
                </div>

                <!-- Degree + Graduation Year: student only -->
                <div
                    v-if="role === 'student'"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <div class="grid gap-2">
                        <Label for="degree">Degree</Label>
                        <Input
                            id="degree"
                            v-model="detailsForm.degree"
                            placeholder="e.g. BSIT"
                        />
                        <InputError :message="detailsForm.errors.degree" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="graduation_year">Graduation Year</Label>
                        <Input
                            id="graduation_year"
                            v-model="detailsForm.graduation_year"
                            placeholder="e.g. 2025"
                            maxlength="4"
                        />
                        <InputError
                            :message="detailsForm.errors.graduation_year"
                        />
                    </div>
                </div>

                <!-- Specialization + Institution: faculty & staff/admin -->
                <div
                    v-if="role !== 'student'"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <div class="grid gap-2">
                        <Label for="specialization">Specialization</Label>
                        <Input
                            id="specialization"
                            v-model="detailsForm.specialization"
                            placeholder="e.g. Machine Learning"
                        />
                        <InputError
                            :message="detailsForm.errors.specialization"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="institution">Institution</Label>
                        <Input
                            id="institution"
                            v-model="detailsForm.institution"
                            placeholder="e.g. UM Digos College"
                        />
                        <InputError :message="detailsForm.errors.institution" />
                    </div>
                </div>

                <!-- Bio: all roles -->
                <div class="grid gap-2">
                    <Label for="bio">Bio</Label>
                    <textarea
                        id="bio"
                        v-model="detailsForm.bio"
                        rows="3"
                        maxlength="1000"
                        placeholder="Tell us a bit about yourself…"
                        class="mt-1 w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-0 focus:outline-none"
                    />
                    <InputError :message="detailsForm.errors.bio" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="detailsForm.processing">
                        {{
                            detailsForm.processing ? 'Saving…' : 'Save details'
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </div>

    <DeleteUser />
</template>
