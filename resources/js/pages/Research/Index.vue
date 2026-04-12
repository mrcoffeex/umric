<template>
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Research Papers</h1>
        <p class="mt-0.5 text-sm text-muted-foreground">Browse and manage research submissions.</p>
      </div>
      <Link :href="create()">
        <Button class="flex items-center gap-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700 border-0 shadow shadow-orange-500/25">
          <Plus class="w-4 h-4" />
          New Paper
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
        <input
          v-model="search"
          type="text"
          placeholder="Search by title or description…"
          class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm bg-white dark:bg-sidebar border border-slate-200 dark:border-sidebar-border text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400"
        />
      </div>
      <select
        v-model="selectedStatus"
        class="px-4 py-2.5 rounded-xl text-sm bg-white dark:bg-sidebar border border-slate-200 dark:border-sidebar-border text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
      >
        <option value="">All statuses</option>
        <option value="submitted">Submitted</option>
        <option value="under_review">Under Review</option>
        <option value="approved">Approved</option>
        <option value="presented">Presented</option>
        <option value="published">Published</option>
        <option value="archived">Archived</option>
      </select>
      <select
        v-model="selectedCategory"
        class="px-4 py-2.5 rounded-xl text-sm bg-white dark:bg-sidebar border border-slate-200 dark:border-sidebar-border text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-400/50"
      >
        <option value="">All categories</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
    </div>

    <!-- Papers Grid -->
    <div v-if="filteredPapers.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <Link
        v-for="paper in filteredPapers"
        :key="paper.id"
        :href="show(paper.id)"
        class="group"
      >
        <NeuCard class="h-full cursor-pointer transition-all group-hover:scale-[1.01]">
          <div class="flex flex-col h-full gap-3">
            <div class="flex items-start justify-between gap-2">
              <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 line-clamp-2 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
                {{ paper.title }}
              </h3>
            </div>
            <StatusBadge :status="paper.status" />
            <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 flex-1">
              {{ paper.description }}
            </p>
            <div class="pt-3 border-t border-white/30 dark:border-white/10 flex items-center justify-between">
              <code class="text-[11px] font-mono text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/40 px-2 py-0.5 rounded">
                {{ paper.tracking_id }}
              </code>
              <span class="text-xs text-slate-500">{{ formatDate(paper.created_at) }}</span>
            </div>
          </div>
        </NeuCard>
      </Link>
    </div>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center py-16">
      <div class="w-16 h-16 rounded-2xl bg-orange-50 dark:bg-orange-950/30 flex items-center justify-center mb-4">
        <FileText class="w-8 h-8 text-orange-400" />
      </div>
      <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-1">No papers found</h3>
      <p class="text-sm text-muted-foreground mb-5">
        {{ search || selectedStatus || selectedCategory ? 'Try adjusting your filters.' : 'Submit your first research paper.' }}
      </p>
      <Link :href="create()">
        <Button class="bg-gradient-to-r from-orange-500 to-orange-600 text-white border-0 hover:from-orange-600 hover:to-orange-700">
          Submit First Paper
        </Button>
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { FileText, Plus, Search } from 'lucide-vue-next'
import NeuCard from '@/components/NeuCard.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { Button } from '@/components/ui/button'
import { create, index, show } from '@/routes/papers'

interface Paper {
  id: number
  title: string
  description: string
  status: string
  tracking_id: string
  created_at: string
  authors_count?: number
  category?: { id: number; name: string }
}

interface Category {
  id: number
  name: string
}

interface Props {
  papers: Paper[]
  categories: Category[]
}

const props = defineProps<Props>()

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Research Papers', href: index() },
    ],
  },
})

const search = ref('')
const selectedStatus = ref('')
const selectedCategory = ref<number | ''>('')

const filteredPapers = computed(() => {
  return props.papers.filter(paper => {
    const matchesSearch =
      search.value === '' ||
      paper.title.toLowerCase().includes(search.value.toLowerCase()) ||
      paper.description?.toLowerCase().includes(search.value.toLowerCase())

    const matchesStatus = selectedStatus.value === '' || paper.status === selectedStatus.value

    const matchesCategory =
      selectedCategory.value === '' || paper.category?.id === selectedCategory.value

    return matchesSearch && matchesStatus && matchesCategory
  })
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>
