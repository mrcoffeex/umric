<template>
  <div
    :class="{
      'min-h-screen bg-gradient-to-br transition-colors duration-300': true,
      'dark from-slate-900 to-slate-800 text-white': isDark,
      'from-slate-100 to-slate-200 text-gray-900': !isDark
    }"
  >
    <!-- Header -->
    <div class="sticky top-0 z-50 backdrop-blur-md">
      <NeuCard class="!rounded-b-3xl">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
          <h1 class="text-3xl font-bold">Track Research Paper</h1>
          <button
            @click="isDark = !isDark"
            class="p-2 rounded-lg hover:bg-white/10 transition"
          >
            {{ isDark ? '☀️' : '🌙' }}
          </button>
        </div>
      </NeuCard>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-6 py-8">
      <!-- Hero Card -->
      <NeuCard class="mb-8">
        <div class="flex flex-col md:flex-row gap-8">
          <div class="flex-1">
            <h2 class="text-4xl font-bold mb-4">{{ paper.title }}</h2>
            <p class="text-lg text-gray-600 dark:text-gray-400 mb-6 line-clamp-3">
              {{ paper.description }}
            </p>

            <div class="flex flex-wrap gap-4">
              <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                <StatusBadge :status="paper.status" />
              </div>

              <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Category</p>
                <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-900 dark:text-purple-100 rounded-full text-sm font-medium">
                  {{ paper.category?.name }}
                </span>
              </div>

              <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Submitted</p>
                <span class="text-sm font-semibold">{{ formatDate(paper.created_at) }}</span>
              </div>
            </div>
          </div>

          <div class="md:w-48 text-center">
            <div class="inline-flex flex-col items-center">
              <div class="text-6xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                {{ paper.progress || 0 }}%
              </div>
              <p class="text-gray-600 dark:text-gray-400 mb-4">Complete</p>
              <div class="w-full h-2 bg-white/30 dark:bg-white/10 rounded-full overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-500"
                  :style="{ width: (paper.progress || 0) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </NeuCard>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Timeline -->
        <div class="lg:col-span-2">
          <NeuCard>
            <h3 class="text-2xl font-bold mb-6">Timeline</h3>
            <TrackingTimeline :current-status="paper.status" :tracking="paper.tracking_records || []" />
          </NeuCard>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
          <!-- Tracking ID -->
          <NeuCard>
            <h4 class="text-sm uppercase tracking-wider font-bold text-gray-600 dark:text-gray-400 mb-3">
              Tracking ID
            </h4>
            <div class="font-mono text-lg font-bold text-purple-600 dark:text-purple-400 p-3 bg-white/20 dark:bg-black/20 rounded-lg break-all">
              {{ paper.tracking_id }}
            </div>
          </NeuCard>

          <!-- Authors -->
          <NeuCard v-if="paper.authors && paper.authors.length > 0">
            <h4 class="text-sm uppercase tracking-wider font-bold text-gray-600 dark:text-gray-400 mb-3">
              Authors
            </h4>
            <div class="space-y-2">
              <div
                v-for="(author, index) in paper.authors"
                :key="author.id"
                class="p-2 bg-white/10 dark:bg-black/10 rounded"
              >
                <p class="font-medium">{{ author.name }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                  {{ author.pivot?.author_order || index + 1 }}. Author
                </p>
              </div>
            </div>
          </NeuCard>

          <!-- Keywords -->
          <NeuCard v-if="paper.keywords">
            <h4 class="text-sm uppercase tracking-wider font-bold text-gray-600 dark:text-gray-400 mb-3">
              Keywords
            </h4>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="keyword in paper.keywords.split(',')"
                :key="keyword.trim()"
                class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100 rounded-full text-xs font-medium"
              >
                {{ keyword.trim() }}
              </span>
            </div>
          </NeuCard>

          <!-- Statistics -->
          <NeuCard>
            <h4 class="text-sm uppercase tracking-wider font-bold text-gray-600 dark:text-gray-400 mb-3">
              Statistics
            </h4>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Files</span>
                <span class="font-semibold">{{ (paper.files || []).length }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Publications</span>
                <span class="font-semibold">{{ (paper.publication || []).length }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Citations</span>
                <span class="font-semibold">{{ (paper.citations || []).length }}</span>
              </div>
            </div>
          </NeuCard>
        </div>
      </div>

      <!-- Documents Section -->
      <NeuCard v-if="paper.files && paper.files.length > 0" class="mt-8">
        <h3 class="text-2xl font-bold mb-6">Documents</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="file in paper.files"
            :key="file.id"
            class="flex items-center justify-between p-4 bg-white/10 dark:bg-black/10 rounded-lg hover:bg-white/20 dark:hover:bg-black/20 transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-2xl">📄</span>
              <div>
                <p class="font-semibold">{{ file.file_name }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ formatFileSize(file.file_size) }}
                </p>
              </div>
            </div>
            <a
              :href="`/storage/${file.file_path}`"
              class="text-purple-600 dark:text-purple-400 hover:underline font-medium"
            >
              View
            </a>
          </div>
        </div>
      </NeuCard>

      <!-- Publications & Citations -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        <!-- Publications -->
        <NeuCard v-if="paper.publication && paper.publication.length > 0">
          <h3 class="text-xl font-bold mb-6">Publications</h3>
          <div class="space-y-4">
            <div
              v-for="pub in paper.publication"
              :key="pub.id"
              class="p-4 bg-white/10 dark:bg-black/10 rounded-lg"
            >
              <h4 class="font-semibold mb-2">{{ pub.journal_name }}</h4>
              <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                <p v-if="pub.doi">DOI: <code class="font-mono">{{ pub.doi }}</code></p>
                <p v-if="pub.publisher">Publisher: {{ pub.publisher }}</p>
                <p v-if="pub.volume || pub.issue">
                  Vol. {{ pub.volume }}, Issue {{ pub.issue }}
                </p>
              </div>
            </div>
          </div>
        </NeuCard>

        <!-- Citations -->
        <NeuCard v-if="paper.citations && paper.citations.length > 0">
          <h3 class="text-xl font-bold mb-6">Citations</h3>
          <div class="space-y-4">
            <div
              v-for="citation in paper.citations"
              :key="citation.id"
              class="p-4 bg-white/10 dark:bg-black/10 rounded-lg"
            >
              <p class="text-xs font-mono mb-2">{{ citation.citation_text }}</p>
              <p v-if="citation.format" class="text-xs text-gray-600 dark:text-gray-400">
                {{ citation.format }}
              </p>
            </div>
          </div>
        </NeuCard>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import NeuCard from '@/components/NeuCard.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import TrackingTimeline from '@/components/TrackingTimeline.vue'

interface Author {
  id: number
  name: string
  pivot?: { author_order?: number }
}

interface Category {
  id: number
  name: string
}

interface File {
  id: number
  file_name: string
  file_size: number
  file_path: string
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
  files?: File[]
  publication?: Publication[]
  citations?: Citation[]
  tracking_records?: TrackingRecord[]
}

interface Props {
  paper: Paper
}

const props = defineProps<Props>()
const isDark = ref(false)

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}
</script>
