<template>
  <div class="min-h-screen bg-[#f8f9fa]">
    <AppHeader :show-logout="true" @logout="handleLogout">
      <template #breadcrumb>
        <RouterLink to="/documents" class="text-[#005d22] hover:text-[#004a1b] shrink-0">
          ← Dokumente
        </RouterLink>
        <span class="text-[#ced4da]">/</span>
        <span class="truncate text-[#6c757d]">{{ doc?.title || doc?.original_filename || 'Dokument' }}</span>
      </template>
    </AppHeader>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
      <!-- Laden -->
      <div v-if="store.isLoading" class="flex justify-center py-16">
        <AppSpinner size="lg" class="text-[#005d22]" />
      </div>

      <!-- Fehler -->
      <div
        v-else-if="store.error"
        class="rounded-lg bg-[#f8d7da] border border-[#f5c2c7] px-4 py-3 text-sm text-[#842029]"
        role="alert"
      >
        {{ store.error }}
      </div>

      <template v-else-if="doc">
        <!-- Kopfkarte -->
        <div class="bg-white rounded-lg border border-[#dee2e6] p-6 mb-4">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h1 class="text-xl font-semibold text-[#212529] leading-snug">
                {{ doc.title || doc.original_filename }}
              </h1>
              <p class="text-sm text-[#6c757d] mt-1">{{ doc.original_filename }}</p>
              <p v-if="doc.summary" class="text-sm text-[#495057] mt-3 leading-relaxed">{{ doc.summary }}</p>
            </div>
            <div class="flex-shrink-0">
              <DocumentStatusBadge :status="doc.status" />
            </div>
          </div>

          <!-- Fehler-Status -->
          <div
            v-if="doc.status === 'failed'"
            class="mt-4 rounded-md bg-[#f8d7da] border border-[#f5c2c7] px-3 py-2 text-sm text-[#842029]"
            role="alert"
          >
            Verarbeitung fehlgeschlagen. Bitte laden Sie das Dokument erneut hoch.
          </div>

          <!-- Chat-Aktion (verarbeitet) -->
          <div v-if="doc.status === 'processed'" class="mt-4">
            <button
              @click="openChat"
              class="rounded-md bg-[#005d22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#004a1b] focus:outline-none focus:ring-2 focus:ring-[#005d22] focus:ring-offset-2 transition-colors"
            >
              Chat mit diesem Dokument starten
            </button>
          </div>

          <!-- Platzhalter wenn noch nicht verarbeitet -->
          <div
            v-else-if="doc.status !== 'failed'"
            class="mt-4 rounded-md bg-[#f8f9fa] border border-[#dee2e6] px-3 py-2 text-sm text-[#6c757d]"
          >
            Chat mit diesem Dokument — folgt nach Verarbeitung
          </div>
        </div>

        <!-- Extrahierte Felder -->
        <div v-if="doc.status === 'processed'" class="bg-white rounded-lg border border-[#dee2e6] p-6 mb-4">
          <h2 class="text-base font-semibold text-[#212529] mb-4">Extrahierte Informationen</h2>

          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <template v-for="field in extractedFields" :key="field.key">
              <div v-if="field.value">
                <dt class="text-xs font-medium text-[#6c757d] uppercase tracking-wide">
                  {{ field.label }}
                </dt>
                <dd class="mt-1 text-sm text-[#212529]">{{ field.value }}</dd>
              </div>
            </template>
          </dl>

          <div
            v-if="extractedFields.every((f) => !f.value)"
            class="text-sm text-[#6c757d]"
          >
            Keine strukturierten Daten extrahiert.
          </div>
        </div>

        <!-- Datei-Metadaten -->
        <div class="bg-white rounded-lg border border-[#dee2e6] p-6">
          <h2 class="text-base font-semibold text-[#212529] mb-4">Datei-Informationen</h2>
          <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div>
              <dt class="text-xs font-medium text-[#6c757d] uppercase tracking-wide">Größe</dt>
              <dd class="mt-1 text-[#212529]">{{ formatSize(doc.size_bytes) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-[#6c757d] uppercase tracking-wide">Typ</dt>
              <dd class="mt-1 text-[#212529]">{{ doc.document_type ? formatDocType(doc.document_type) : '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-[#6c757d] uppercase tracking-wide">Hochgeladen</dt>
              <dd class="mt-1 text-[#212529]">{{ formatDate(doc.created_at) }}</dd>
            </div>
          </dl>

          <div class="mt-4 pt-4 border-t border-[#dee2e6]">
            <button
              @click="deleteDocument"
              class="text-sm text-[#dc3545] hover:text-[#b02a37] transition-colors"
            >
              Dokument löschen
            </button>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useDocumentsStore } from '@/stores/documents'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'
import { useDocumentPolling } from '@/composables/useDocumentPolling'
import AppHeader from '@/components/common/AppHeader.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import DocumentStatusBadge from '@/components/documents/DocumentStatusBadge.vue'

const route = useRoute()
const router = useRouter()
const store = useDocumentsStore()
const chatStore = useChatStore()
const authStore = useAuthStore()

const documentId = computed(() => route.params.id as string)
const doc = computed(() => store.currentDocument)

const { startPolling } = useDocumentPolling(documentId.value)

onMounted(async () => {
  await store.fetchDocument(documentId.value)
  if (doc.value?.status === 'uploaded' || doc.value?.status === 'processing') {
    startPolling()
  }
})

watch(() => doc.value?.status, (status) => {
  if (status === 'uploaded' || status === 'processing') {
    startPolling()
  }
})

const extractedFields = computed(() => [
  { key: 'counterparty_name', label: 'Vertragspartner', value: doc.value?.counterparty_name },
  { key: 'contract_holder_name', label: 'Versicherungsnehmer', value: doc.value?.contract_holder_name },
  { key: 'contract_number', label: 'Vertragsnummer', value: doc.value?.contract_number },
  { key: 'policy_number', label: 'Policennummer', value: doc.value?.policy_number },
  { key: 'start_date', label: 'Beginn', value: doc.value?.start_date },
  { key: 'end_date', label: 'Ende', value: doc.value?.end_date },
  { key: 'duration_text', label: 'Laufzeit', value: doc.value?.duration_text },
  { key: 'cancellation_period', label: 'Kündigungsfrist', value: doc.value?.cancellation_period },
  {
    key: 'payment',
    label: 'Beitrag',
    value: doc.value?.payment_amount
      ? `${doc.value.payment_amount} ${doc.value.payment_currency || 'EUR'} (${doc.value.payment_interval || ''})`
      : null,
  },
  { key: 'important_terms', label: 'Wichtige Konditionen', value: doc.value?.important_terms },
  { key: 'exclusions', label: 'Ausschlüsse', value: doc.value?.exclusions },
  { key: 'contact_details', label: 'Kontakt', value: doc.value?.contact_details },
])

async function openChat() {
  if (!doc.value) return
  const session = await chatStore.createSession(doc.value.id, doc.value.title || doc.value.original_filename)
  router.push({ name: 'chat', params: { sessionId: session.id } })
}

async function deleteDocument() {
  if (!doc.value) return
  if (!confirm('Dokument und alle zugehörigen Daten löschen?')) return
  await store.deleteDocument(doc.value.id)
  router.push({ name: 'documents' })
}

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}

function formatSize(bytes: number): string {
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('de-DE')
}

function formatDocType(type: string): string {
  return type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}
</script>
