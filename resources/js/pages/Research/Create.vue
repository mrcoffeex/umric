<template>
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-3xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Submit Research Paper</h1>
        <p class="mt-0.5 text-sm text-muted-foreground">Fill in the details to register your research.</p>
      </div>
      <Link :href="index()">
        <Button variant="outline" size="sm" class="gap-1.5">
          <ArrowLeft class="w-3.5 h-3.5" />
          Papers
        </Button>
      </Link>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <!-- Title -->
      <NeuCard>
        <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Paper Title <span class="text-orange-500">*</span></label>
        <input
          v-model="form.title"
          type="text"
          placeholder="Enter the full title of your research paper"
          required
          class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
        />
        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
      </NeuCard>

      <!-- Abstract -->
      <NeuCard>
        <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Abstract <span class="text-orange-500">*</span></label>
        <textarea
          v-model="form.description"
          rows="5"
          placeholder="Brief description of your research"
          required
          class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50 resize-y"
        />
        <p v-if="form.errors.description" class="text-xs text-red-500 mt-1">{{ form.errors.description }}</p>
      </NeuCard>

      <!-- Category + Status -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <NeuCard>
          <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Category <span class="text-orange-500">*</span></label>
          <select
            v-model="form.category_id"
            required
            class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
          >
            <option value="">Select a category</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
          <p v-if="form.errors.category_id" class="text-xs text-red-500 mt-1">{{ form.errors.category_id }}</p>
        </NeuCard>

        <NeuCard>
          <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Initial Status</label>
          <select
            v-model="form.status"
            class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
          >
            <option value="submitted">Submitted</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="presented">Presented</option>
            <option value="published">Published</option>
          </select>
        </NeuCard>
      </div>

      <!-- Authors -->
      <NeuCard>
        <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Authors</label>
        <div class="space-y-2 mb-3">
          <div v-for="(_, i) in form.authors" :key="i" class="flex gap-2">
            <input
              v-model="form.authors[i]"
              type="text"
              placeholder="Author name"
              class="flex-1 px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
            />
            <button
              v-if="form.authors.length > 1"
              type="button"
              @click="form.authors.splice(i, 1)"
              class="h-10 w-10 rounded-xl text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 flex items-center justify-center transition-colors"
            >
              ✕
            </button>
          </div>
        </div>
        <button
          type="button"
          @click="form.authors.push('')"
          class="text-sm text-teal-600 dark:text-teal-400 font-medium hover:underline"
        >
          + Add Author
        </button>
      </NeuCard>

      <!-- Keywords -->
      <NeuCard>
        <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Keywords</label>
        <input
          v-model="form.keywords"
          type="text"
          placeholder="machine learning, AI, neural networks"
          class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
        />
        <p class="text-xs text-muted-foreground mt-1">Separate keywords with commas.</p>
      </NeuCard>

      <!-- File Upload -->
      <NeuCard>
        <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Paper File (PDF) <span class="text-orange-500">*</span></label>
        <input type="file" ref="fileInput" accept=".pdf" @change="handleFile" class="hidden" />
        <div
          @click="fileInput?.click()"
          class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 text-center cursor-pointer hover:border-orange-400 transition-colors"
        >
          <Upload class="w-8 h-8 text-muted-foreground mx-auto mb-2" />
          <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ fileName || 'Click to upload PDF' }}</p>
          <p class="text-xs text-muted-foreground mt-1">PDF files only</p>
        </div>
        <p v-if="form.errors.file" class="text-xs text-red-500 mt-1">{{ form.errors.file }}</p>
      </NeuCard>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link :href="index()">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
        <Button
          type="submit"
          :disabled="form.processing"
          class="bg-gradient-to-r from-orange-500 to-orange-600 text-white border-0 hover:from-orange-600 hover:to-orange-700 disabled:opacity-60"
        >
          {{ form.processing ? 'Submitting…' : 'Submit Paper' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft, Upload } from 'lucide-vue-next'
import NeuCard from '@/components/NeuCard.vue'
import { Button } from '@/components/ui/button'
import { create, index, store } from '@/routes/papers'

interface Category {
  id: number
  name: string
}

interface Props {
  categories: Category[]
}

const props = defineProps<Props>()

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Research Papers', href: index() },
      { title: 'Submit Paper', href: create() },
    ],
  },
})

const fileInput = ref<HTMLInputElement>()
const fileName = ref('')

const form = useForm({
  title: '',
  description: '',
  category_id: '' as string | number,
  status: 'submitted',
  authors: [''] as string[],
  keywords: '',
  file: null as File | null,
})

function handleFile(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) { return }
  form.file = file
  fileName.value = file.name
}

function submit() {
  form.post(store.url(), { forceFormData: true })
}
</script>
