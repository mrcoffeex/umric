<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Image as ImageIcon, Upload } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BrandingController from '@/actions/App/Http/Controllers/Admin/BrandingController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type BrandingData = {
    site_name: string;
    tagline: string | null;
    logo_url: string | null;
};

const props = defineProps<{
    branding: BrandingData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Site branding', href: admin.branding.index.url() },
        ],
    },
});

const logoInput = ref<HTMLInputElement | null>(null);
const localPreview = ref<string | null>(null);

const form = useForm({
    site_name: props.branding.site_name,
    tagline: props.branding.tagline ?? '',
    logo: null as File | null,
    remove_logo: false,
});

watch(
    () => props.branding,
    (b) => {
        form.site_name = b.site_name;
        form.tagline = b.tagline ?? '';
        form.logo = null;
        form.remove_logo = false;
        form.clearErrors();
        localPreview.value = null;
    },
    { deep: true },
);

watch(
    () => form.remove_logo,
    (v) => {
        if (v) {
            form.logo = null;

            if (localPreview.value) {
                URL.revokeObjectURL(localPreview.value);
                localPreview.value = null;
            }
        }
    },
);

const previewUrl = computed(() => {
    if (form.remove_logo) {
        return null;
    }

    if (localPreview.value) {
        return localPreview.value;
    }

    if (props.branding.logo_url) {
        return props.branding.logo_url;
    }

    return null;
});

function pickLogo() {
    logoInput.value?.click();
}

function onLogoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (!file) {
        form.logo = null;

        if (localPreview.value) {
            URL.revokeObjectURL(localPreview.value);
            localPreview.value = null;
        }

        return;
    }

    if (localPreview.value) {
        URL.revokeObjectURL(localPreview.value);
    }

    form.logo = file;
    form.remove_logo = false;
    localPreview.value = URL.createObjectURL(file);
}

/**
 * real PUT + multipart/form-data is not parsed into $_POST in PHP, so
 * Inertia "put" with forceFormData drops text fields. Without a file, send JSON
 * via put() with no FormData. With a file, POST to the same URL with
 * _method=PUT (Laravel method spoofing) so the body is parsed.
 */
function submit() {
    const url = BrandingController.update.url();
    const options = { preserveScroll: true };
    const hasNewLogo = form.logo instanceof File;

    if (hasNewLogo) {
        form.transform((data) => ({ ...data, _method: 'PUT' as const })).post(
            url,
            { ...options, forceFormData: true },
        );
    } else {
        form.transform((data) => data).put(url, options);
    }
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Site branding" />

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="mb-6 flex items-start gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30"
                >
                    <ImageIcon
                        class="h-5 w-5 text-orange-600 dark:text-orange-400"
                    />
                </div>
                <div>
                    <h1 class="text-lg font-semibold tracking-tight sm:text-xl">
                        Site name &amp; logo
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Shown in the sidebar, public pages, and document titles
                        across the app.
                    </p>
                </div>
            </div>

            <form class="max-w-lg space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="site_name">Site name</Label>
                    <Input
                        id="site_name"
                        v-model="form.site_name"
                        type="text"
                        maxlength="100"
                        required
                        autocomplete="organization"
                    />
                    <InputError :message="form.errors.site_name" />
                </div>

                <div class="space-y-2">
                    <Label for="tagline">Tagline (optional)</Label>
                    <Input
                        id="tagline"
                        v-model="form.tagline"
                        type="text"
                        placeholder="Short line under the name in the app header"
                    />
                    <InputError :message="form.errors.tagline" />
                </div>

                <div class="space-y-2">
                    <span class="text-sm font-medium">Logo (optional)</span>
                    <p class="text-xs text-muted-foreground">
                        PNG, JPEG, WebP, or SVG. Max 2&nbsp;MB. Square images
                        look best in the header.
                    </p>

                    <div
                        v-if="previewUrl"
                        class="mt-2 flex max-w-xs items-center gap-3 rounded-lg border p-3"
                    >
                        <img
                            :src="previewUrl"
                            alt=""
                            class="h-12 w-12 object-contain"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            ref="logoInput"
                            type="file"
                            class="sr-only"
                            accept="image/jpeg,image/png,image/webp,image/svg+xml"
                            @change="onLogoChange"
                        />
                        <Button
                            type="button"
                            variant="secondary"
                            @click="pickLogo"
                        >
                            <Upload class="mr-2 h-4 w-4" />
                            {{ previewUrl ? 'Replace logo' : 'Upload logo' }}
                        </Button>
                        <label
                            v-if="props.branding.logo_url || localPreview"
                            class="inline-flex items-center gap-2 text-sm"
                        >
                            <input
                                v-model="form.remove_logo"
                                type="checkbox"
                                class="size-4 rounded border-input"
                            />
                            Remove current logo
                        </label>
                    </div>
                    <InputError :message="form.errors.logo" />
                </div>

                <div class="pt-2">
                    <Button type="submit" :disabled="form.processing">
                        Save changes
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
