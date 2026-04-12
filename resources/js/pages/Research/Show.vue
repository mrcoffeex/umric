<template>
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white line-clamp-1">{{ paper.title }}</h1>
        <p class="mt-0.5 text-sm text-muted-foreground">{{ formatDate(paper.created_at) }}</p>
      </div>
      <div class="flex items-center gap-2">
        <Link :href="edit(paper.id)">
          <Button size="sm" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white border-0 hover:from-orange-600 hover:to-orange-700">Edit</Button>
        </Link>
        <Link :href="index()">
          <Button size="sm" variant="outline">Back</Button>
        </Link>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Tracking Status -->
        <NeuCard>
          <h2 class="text-lg font-bold mb-4 text-slate-800 dark:text-slate-100">Tracking Progress</h2>
          <TrackingTimeline :current-status="paper.status" :tracking="paper.tracking_records || []" />
        </NeuCard>

        <!-- Abstract -->
        <NeuCard>
          <h2 class="text-lg font-bold mb-3 text-slate-800 dark:text-slate-100">Abstract</h2>
          <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
            {{ paper.description }}
          </p>
        </NeuCard>

        <!-- Keywords -->
        <NeuCard v-if="paper.keywords">
          <h2 class="text-lg font-bold mb-3 text-slate-800 dark:text-slate-100">Keywords</h2>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="keyword in paper.keywords.split(',')"
              :key="keyword.trim()"
              class="px-3 py-1 bg-orange-100 dark:bg-orange-950/40 text-orange-800 dark:text-orange-300 rounded-full text-xs font-medium"
            >
              {{ keyword.trim() }}
            </span>
          </div>
        </NeuCard>

        <!-- Publications -->
        <NeuCard v-if="paper.publication && paper.publication.length > 0">
          <h2 class="text-lg font-bold mb-3 text-slate-800 dark:text-slate-100">Publications</h2>
          <div class="space-y-3">
            <div
              v-for="pub in paper.publication"
              :key="pub.id"
              class="p-4 bg-white/40 dark:bg-black/10 rounded-xl"
            >
              <h3 class="font-semibold text-sm text-slate-800 dark:text-slate-200 mb-2">{{ pub.journal_name }}</h3>
              <div class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
                <p v-if="pub.doi">DOI: <code class="font-mono">{{ pub.doi }}</code></p>
                <p v-if="pub.publisher">Publisher: {{ pub.publisher }}</p>
                <p v-if="pub.volume || pub.issue">Vol. {{ pub.volume }}, Issue {{ pub.issue }}</p>
              </div>
            </div>
          </div>
        </NeuCard>

        <!-- Citations -->
        <NeuCard v-if="paper.citations && paper.citations.length > 0">
          <h2 class="text-lg font-bold mb-3 text-slate-800 dark:text-slate-100">Citations</h2>
          <div class="space-y-3">
            <div
              v-for="citation in paper.citations"
              :key="citation.id"
              class="p-4 bg-white/40 dark:bg-black/10 rounded-xl"
            >
              <p class="text-xs font-mono text-slate-700 dark:text-slate-300">{{ citation.citation_text }}</p>
              <p v-if="citation.format" class="text-xs text-muted-foreground mt-1">{{ citation.format }}</p>
            </div>
          </div>
        </NeuCard>

        <!-- Files -->
        <NeuCard v-if="paper.files && paper.files.length > 0">
          <h2 class="text-lg font-bold mb-3 text-slate-800 dark:text-slate-100">Documents</h2>
          <div class="space-y-2">
            <div
              v-for="file in paper.files"
              :key="file.id"
              class="flex items-center justify-between p-3 bg-white/40 dark:bg-black/10 rounded-xl hover:bg-white/60 dark:hover:bg-black/20 transition"
            >
              <div class="flex items-center gap-3">
                <span class="text-xl">📄</span>
                <div>
                  <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ file.file_name }}</p>
                  <p class="text-xs text-muted-foreground">{{ formatFileSize(file.file_size) }}</p>
                </div>
              </div>
              <a
                :href="`/storage/${file.file_path}`"
                download
                class="text-xs font-semibold text-orange-600 dark:text-orange-400 hover:underline"
              >
                Download
              </a>
            </div>
          </div>
        </NeuCard>
      </div>

      <!-- Sidebar -->
      <div class="space-y-5">
        <!-- Status -->
        <NeuCard>
          <h3 class="text-xs uppercase tracking-wider font-bold text-muted-foreground mb-3">Status</h3>
          <StatusBadge :status="paper.status" />
          <div class="mt-4 p-3 bg-white/20 dark:bg-black/20 rounded-xl text-center">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ paper.progress || 0 }}%</div>
            <p class="text-xs text-muted-foreground">Complete</p>
          </div>
        </NeuCard>

        <!-- Category -->
        <NeuCard>
          <h3 class="text-xs uppercase tracking-wider font-bold text-muted-foreground mb-3">Category</h3>
          <div class="px-3 py-2 bg-orange-100 dark:bg-orange-950/40 text-orange-800 dark:text-orange-300 rounded-lg text-sm font-medium">
            {{ paper.category?.name || 'Uncategorized' }}
          </div>
        </NeuCard>

        <!-- Tracking ID -->
        <NeuCard>
          <h3 class="text-xs uppercase tracking-wider font-bold text-muted-foreground mb-3">Tracking ID</h3>
          <code class="block font-mono text-sm font-bold text-orange-600 dark:text-orange-400 p-3 bg-white/20 dark:bg-black/20 rounded-xl break-all">
            {{ paper.tracking_id }}
          </code>
          <button
            @click="copyToClipboard(paper.tracking_id)"
            class="mt-2 w-full py-2 text-xs font-semibold text-orange-600 dark:text-orange-400 hover:bg-white/20 dark:hover:bg-black/20 rounded-lg transition"
          >
            📋 Copy
          </button>
        </NeuCard>

        <!-- Authors -->
        <NeuCard v-if="paper.authors && paper.authors.length > 0">
          <h3 class="text-xs uppercase tracking-wider font-bold text-muted-foreground mb-3">Authors</h3>
          <div class="space-y-2">
            <div
              v-for="(author, index) in paper.authors"
              :key="author.id"
              class="p-2 bg-white/10 dark:bg-black/10 rounded-lg"
            >
              <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ author.name }}</p>
              <p class="text-xs text-muted-foreground">{{ author.pivot?.author_order || index + 1 }}. Author</p>
            </div>
          </div>
        </NeuCard>

        <!-- Public Tracking Link -->
        <NeuCard>
          <h3 class="text-xs uppercase tracking-wider font-bold text-muted-foreground mb-3">Public Tracking</h3>
          <p class="text-xs text-muted-foreground mb-3">Share this link for public tracking without authentication.</p>
          <code class="block font-mono text-xs p-2 bg-white/20 dark:bg-black/20 rounded-lg break-all text-slate-700 dark:text-slate-300">
            {{ publicTrackingUrl }}
          </code>
          <button
            @click="copyToClipboard(publicTrackingUrl)"
            class="mt-2 w-full py-2 text-xs font-semibold text-orange-600 dark:text-orange-400 hover:bg-white/20 dark:hover:bg-black/20 rounded-lg transition"
          >
            📋 Copy Link
          </button>
        </NeuCard>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import NeuCard from '@/components/NeuCard.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import TrackingTimeline from '@/components/TrackingTimeline.vue'
import { edit, index, show, publicTracking } from '@/routes/papers'

interface Author {
  id: number
  name: string
  pivot?: { author_order?: number }
}

interface Publication {
  id: number
  journal_name: string
  doi?: string
  publisher?: string
  volume?: number
  issue?: number
}

interface Citation {
  id: number
  citation_text: string
  format?: string
}

interface File {
  id: number
  file_name: string
  file_size: number
  file_path: string
}

interface Category {
  id: number
  name: string
}

interface TrackingRecord {
  id: number
  status: string
  created_at: string
  notes?: string
}

interface Paper {
  id: number
  title: string
  description: string
  status: string
  tracking_id: string
  created_at: string
  progress?: number
  keywords?: string
  category?: Category
  authors?: Author[]
  publication?: Publication[]
  citations?: Citation[]
  files?: File[]
  tracking_records?: TrackingRecord[]
}

interface Props {
  paper: Paper
}

const props = defineProps<Props>()

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Research Papers', href: index() },
      { title: 'View Paper' },
    ],
  },
})

const copied = ref(false)

const publicTrackingUrl = computed(
  () => `${window.location.origin}${publicTracking.url(props.paper.tracking_id)}`
)

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const copyToClipboard = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}
</script>
