<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, UserPlus } from 'lucide-vue-next';
import { ref } from 'vue';
import admin from '@/routes/admin';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Users', href: admin.users.index() },
            { title: 'Create User', href: '#' },
        ],
    },
});

const showPassword = ref(false);
const showConfirm = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'staff' as 'admin' | 'staff',
});

function submit() {
    form.post(admin.users.store.url());
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Create User</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Add a new admin or staff account.
                </p>
            </div>
            <Link
                :href="admin.users.index()"
                class="rounded-lg border border-border bg-card px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted"
            >
                Cancel
            </Link>
        </div>

        <!-- Form -->
        <div class="mx-auto w-full max-w-lg">
            <form
                @submit.prevent="submit"
                class="overflow-hidden rounded-2xl border border-border bg-card"
            >
                <div class="flex items-center gap-3 border-b border-border p-5">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <UserPlus class="h-5 w-5 text-orange-500" />
                    </div>
                    <div>
                        <p class="font-semibold text-foreground">
                            Account Details
                        </p>
                        <p class="text-xs text-muted-foreground">
                            The user will be immediately active upon creation.
                        </p>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    <!-- Role -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-foreground"
                            >Role</label
                        >
                        <div class="flex gap-3">
                            <label
                                v-for="option in [
                                    { value: 'staff', label: 'Staff' },
                                    { value: 'admin', label: 'Admin' },
                                ]"
                                :key="option.value"
                                :class="[
                                    'flex flex-1 cursor-pointer items-center gap-2.5 rounded-xl border-2 px-4 py-3 transition',
                                    form.role === option.value
                                        ? 'border-orange-500 bg-orange-50 dark:bg-orange-950/20'
                                        : 'border-border bg-card hover:border-orange-300',
                                ]"
                            >
                                <input
                                    type="radio"
                                    :value="option.value"
                                    v-model="form.role"
                                    class="hidden"
                                />
                                <span
                                    :class="[
                                        'h-4 w-4 shrink-0 rounded-full border-2',
                                        form.role === option.value
                                            ? 'border-orange-500 bg-orange-500'
                                            : 'border-border',
                                    ]"
                                />
                                <span
                                    class="text-sm font-semibold text-foreground"
                                    >{{ option.label }}</span
                                >
                            </label>
                        </div>
                        <p
                            v-if="form.errors.role"
                            class="mt-1.5 text-xs text-red-500"
                        >
                            {{ form.errors.role }}
                        </p>
                    </div>

                    <!-- Name -->
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 block text-sm font-semibold text-foreground"
                            >Full Name</label
                        >
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            autocomplete="name"
                            placeholder="e.g. Juan dela Cruz"
                            :class="[
                                'w-full rounded-xl border bg-background px-3 py-2.5 text-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring/30',
                                form.errors.name
                                    ? 'border-red-400 focus:border-red-400'
                                    : 'border-input focus:border-ring',
                            ]"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1.5 text-xs text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-sm font-semibold text-foreground"
                            >Email Address</label
                        >
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            placeholder="user@example.com"
                            :class="[
                                'w-full rounded-xl border bg-background px-3 py-2.5 text-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring/30',
                                form.errors.email
                                    ? 'border-red-400 focus:border-red-400'
                                    : 'border-input focus:border-ring',
                            ]"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1.5 text-xs text-red-500"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            for="password"
                            class="mb-1.5 block text-sm font-semibold text-foreground"
                            >Password</label
                        >
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="At least 8 characters"
                                :class="[
                                    'w-full rounded-xl border bg-background py-2.5 pr-10 pl-3 text-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring/30',
                                    form.errors.password
                                        ? 'border-red-400 focus:border-red-400'
                                        : 'border-input focus:border-ring',
                                ]"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="mt-1.5 text-xs text-red-500"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-1.5 block text-sm font-semibold text-foreground"
                            >Confirm Password</label
                        >
                        <div class="relative">
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showConfirm ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="Repeat password"
                                :class="[
                                    'w-full rounded-xl border bg-background py-2.5 pr-10 pl-3 text-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring/30',
                                    form.errors.password_confirmation
                                        ? 'border-red-400 focus:border-red-400'
                                        : 'border-input focus:border-ring',
                                ]"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                @click="showConfirm = !showConfirm"
                            >
                                <EyeOff v-if="showConfirm" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password_confirmation"
                            class="mt-1.5 text-xs text-red-500"
                        >
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex items-center justify-end gap-3 border-t border-border px-5 py-4"
                >
                    <Link
                        :href="admin.users.index()"
                        class="rounded-lg border border-border bg-card px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-60"
                    >
                        <UserPlus class="h-4 w-4" />
                        {{ form.processing ? 'Creating…' : 'Create User' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
