<template>
  <div class="min-h-screen bg-[#f8f9fa]">
    <AppHeader :show-logout="true" @logout="handleLogout" />

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
      <!-- Seitenheader -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-[#212529]">Meine Dokumente</h1>
        <label
          class="cursor-pointer rounded-md bg-[#005d22] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#004a1b] transition-colors focus-within:ring-2 focus-within:ring-[#005d22] focus-within:ring-offset-2"
        >
          Dokument hochladen
          <input
            ref="fileInput"
            type="file"
            accept=".pdf,application/pdf"
            class="hidden"
            @change="handleFileSelect"
          />
        </label>
      </div>

      <!-- Drag & Drop Zone (leer) -->
      <div
        v-if="!store.isLoading && documents.length === 0"
        class="mb-6 rounded-lg border-2 border-dashed transition-colors"
        :class="isDragging ? 'border-[#005d22] bg-[#e8f5ec]' : 'border-[#ced4da] bg-white'"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        <div class="flex flex-col items-center py-16 px-4 text-center">
          <svg
            class="h-12 w-12 mb-4 transition-colors"
            :class="isDragging ? 'text-[#005d22]' : 'text-[#ced4da]'"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0120 9.414V19a2 2 0 01-2 2z"
            />
          </svg>
          <p class="text-sm font-medium text-[#495057]">
            PDF hierher ziehen oder
            <label class="cursor-pointer text-[#005d22] hover:text-[#004a1b] underline">
              auswählen
              <input
                type="file"
                accept=".pdf,application/pdf"
                class="hidden"
                @change="handleFileSelect"
              />
            </label>
          </p>
          <p class="text-xs text-[#adb5bd] mt-1">Nur PDF-Dateien · max. 20 MB</p>
        </div>
      </div>

      <!-- Drag-Overlay (wenn Dokumente vorhanden) -->
      <div
        v-else-if="isDragging"
        class="mb-6 rounded-lg border-2 border-dashed border-[#005d22] bg-[#e8f5ec] px-4 py-6 text-center text-sm text-[#005d22] font-medium"
        @dragover.prevent
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        PDF hier ablegen zum Hochladen
      </div>

      <!-- Unsichtbares Drop-Ziel wenn Dokumente existieren -->
      <div
        v-else
        class="absolute inset-0 z-10 pointer-events-none"
        :class="{ 'pointer-events-auto': isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      />

      <!-- Upload-Fortschritt -->
      <div v-if="uploadProgress > 0" class="mb-4 rounded-lg bg-[#e8f5ec] border border-[#c3e0cc] p-4">
        <div class="flex items-center justify-between text-sm text-[#005d22] mb-2">
          <span>Wird hochgeladen…</span>
          <span>{{ uploadProgress }}%</span>
        </div>
        <div class="w-full bg-[#c3e0cc] rounded-full h-1.5">
          <div
            class="bg-[#005d22] h-1.5 rounded-full transition-all"
            :style="{ width: `${uploadProgress}%` }"
          />
        </div>
      </div>

      <!-- Fehler -->
      <div
        v-if="store.error"
        class="mb-4 rounded-lg bg-[#f8d7da] border border-[#f5c2c7] px-4 py-3 text-sm text-[#842029]"
        role="alert"
      >
        {{ store.error }}
      </div>

      <!-- Laden -->
      <div v-if="store.isLoading && documents.length === 0" class="flex justify-center py-16">
        <AppSpinner size="lg" class="text-[#005d22]" />
      </div>

      <!-- Dokumentenliste -->
      <div
        v-else-if="documents.length > 0"
        class="space-y-2"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        <RouterLink
          v-for="doc in documents"
          :key="doc.id"
          :to="{ name: 'document-detail', params: { id: doc.id } }"
          class="block bg-white rounded-lg border border-[#dee2e6] p-4 hover:border-[#005d22] hover:shadow-sm transition-all"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-[#212529] truncate">
                {{ doc.title || doc.original_filename }}
              </p>
              <p class="text-xs text-[#6c757d] mt-0.5 truncate">
                {{ doc.original_filename }} · {{ formatSize(doc.size_bytes) }}
              </p>
              <p v-if="doc.summary" class="text-xs text-[#495057] mt-1 line-clamp-2">
                {{ doc.summary }}
              </p>
            </div>
            <div class="flex-shrink-0">
              <DocumentStatusBadge :status="doc.status" />
            </div>
          </div>
          <div class="flex items-center justify-between mt-3">
            <span class="text-xs text-[#adb5bd]">
              {{ doc.document_type ? formatDocType(doc.document_type) : 'Unbekannter Typ' }}
            </span>
            <span class="text-xs text-[#adb5bd]">
              {{ formatDate(doc.created_at) }}
            </span>
          </div>
        </RouterLink>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useDocumentsStore } from '@/stores/documents'
import { useAuthStore } from '@/stores/auth'
import AppHeader from '@/components/common/AppHeader.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import DocumentStatusBadge from '@/components/documents/DocumentStatusBadge.vue'

const store = useDocumentsStore()
const authStore = useAuthStore()
const router = useRouter()

const documents = computed(() => store.documents)
const uploadProgress = computed(() => store.uploadProgress)
const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

onMounted(() => store.fetchDocuments())

async function uploadFile(file: File) {
  if (!file.type.includes('pdf') && !file.name.toLowerCase().endsWith('.pdf')) {
    store.error = 'Nur PDF-Dateien sind erlaubt.'
    return
  }
  try {
    await store.uploadDocument(file)
  } catch {
    // Fehler über store.error angezeigt
  }
}

async function handleFileSelect(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  await uploadFile(file)
  ;(event.target as HTMLInputElement).value = ''
}

async function handleDrop(event: DragEvent) {
  isDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (!file) return
  await uploadFile(file)
}

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDocType(type: string): string {
  return type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}
</script>
