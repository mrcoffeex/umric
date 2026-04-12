<template>
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-3xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Edit Research Paper</h1>
        <p class="mt-0.5 text-sm text-muted-foreground">Update your research paper details.</p>
      </div>
      <Link :href="show(paper.id)">
        <Button variant="outline" size="sm" class="gap-1.5">
          <ArrowLeft class="w-3.5 h-3.5" />
          Back
        </Button>
      </Link>
    </div>

    <!-- Main Content -->
    <form @submit.prevent="submitForm" class="space-y-5">
        <!-- Title -->
        <NeuCard>
          <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Paper Title <span class="text-orange-500">*</span></label>
          <input
            v-model="form.title"
            type="text"
            placeholder="Enter the title of your research paper"
            class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
            required
          />
          <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
        </NeuCard>

        <!-- Description -->
        <NeuCard>
          <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Abstract <span class="text-orange-500">*</span></label>
          <textarea
            v-model="form.description"
            placeholder="Brief description of your research"
            rows="5"
            class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50 resize-y"
            required
          />
          <p v-if="form.errors.description" class="text-xs text-red-500 mt-1">{{ form.errors.description }}</p>
        </NeuCard>

        <!-- Category and Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <NeuCard>
            <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Category <span class="text-orange-500">*</span></label>
            <select
              v-model="form.category_id"
              class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
              required
            >
              <option value="">Select a category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
            <p v-if="form.errors.category_id" class="text-xs text-red-500 mt-1">{{ form.errors.category_id }}</p>
          </NeuCard>

          <NeuCard>
            <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Status</label>
            <select
              v-model="form.status"
              class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
            >
              <option v-for="status in statuses" :key="status" :value="status">
                {{ formatStatus(status) }}
              </option>
            </select>
          </NeuCard>
        </div>

        <!-- Keywords -->
        <NeuCard>
          <label class="block mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Keywords</label>
          <input
            v-model="form.keywords"
            type="text"
            placeholder="Separate keywords with commas"
            class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/60 dark:bg-slate-800 border border-white/50 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
          />
          <p class="text-xs text-muted-foreground mt-1">Example: machine learning, AI, neural networks</p>
        </NeuCard>

        <!-- Action Buttons -->
        <div class="flex gap-3 justify-end">
          <Link :href="show(paper.id)">
            <Button type="button" variant="outline">Cancel</Button>
          </Link>
          <Button
            type="submit"
            :disabled="form.processing"
            class="bg-gradient-to-r from-orange-500 to-orange-600 text-white border-0 hover:from-orange-600 hover:to-orange-700 disabled:opacity-60"
          >
            {{ form.processing ? 'Saving…' : 'Save Changes' }}
          </Button>
        </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import NeuCard from '@/components/NeuCard.vue'
import { Button } from '@/components/ui/button'
import { edit, index, show, update } from '@/routes/papers'

interface Category {
  id: number
  name: string
}

interface Paper {
  id: number
  title: string
  description: string
  category_id: number
  status: string
  keywords?: string
}

interface Props {
  paper: Paper
  categories: Category[]
}

const props = defineProps<Props>()

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Research Papers', href: index() },
      { title: 'Edit Paper' },
    ],
  },
})

const statuses = [
  'submitted',
  'under_review',
  'approved',
  'presented',
  'published',
  'archived',
]

const form = useForm({
  title: props.paper.title,
  description: props.paper.description,
  category_id: props.paper.category_id,
  status: props.paper.status,
  keywords: props.paper.keywords || '',
})

const formatStatus = (status: string) => {
  const map: Record<string, string> = {
    submitted: 'Submitted',
    under_review: 'Under Review',
    approved: 'Approved',
    presented: 'Presented',
    published: 'Published',
    archived: 'Archived',
  }
  return map[status] || status
}

const submitForm = () => {
  form.put(update.url(props.paper.id), {
    onSuccess: () => {
      // Inertia handles redirect via server response
    },
  })
}
</script>
