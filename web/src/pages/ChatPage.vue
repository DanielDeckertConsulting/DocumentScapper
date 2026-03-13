<template>
  <div class="min-h-screen bg-[#f8f9fa] flex flex-col">
    <AppHeader :show-logout="true" @logout="handleLogout">
      <template #breadcrumb>
        <RouterLink to="/documents" class="text-[#005d22] hover:text-[#004a1b] shrink-0">
          ← Dokumente
        </RouterLink>
        <span class="text-[#ced4da]">/</span>
        <span class="truncate text-[#6c757d]">
          {{ session?.title || 'Chat' }}
          <span v-if="!session?.document_id" class="text-xs text-[#adb5bd] ml-1">(Alle Dokumente)</span>
        </span>
      </template>
    </AppHeader>

    <!-- Nachrichten -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-4">
        <!-- Laden -->
        <div v-if="chatStore.isLoading && messages.length === 0" class="flex justify-center py-8">
          <AppSpinner size="lg" class="text-[#005d22]" />
        </div>

        <!-- Leer-Zustand -->
        <div
          v-else-if="messages.length === 0"
          class="text-center py-12 text-[#adb5bd]"
        >
          <p class="text-sm">Stellen Sie Ihre erste Frage zum Dokument.</p>
        </div>

        <!-- Nachrichten -->
        <template v-else>
          <div
            v-for="message in messages"
            :key="message.id"
            :class="['flex', message.role === 'user' ? 'justify-end' : 'justify-start']"
          >
            <div
              :class="[
                'max-w-[80%] rounded-lg px-4 py-3',
                message.role === 'user'
                  ? 'bg-[#005d22] text-white'
                  : 'bg-white border border-[#dee2e6] text-[#212529]',
              ]"
            >
              <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ message.content }}</p>

              <!-- Quellen -->
              <div
                v-if="message.citations_json && message.citations_json.length > 0"
                class="mt-3 pt-3 space-y-2"
                :class="message.role === 'user' ? 'border-t border-white/20' : 'border-t border-[#dee2e6]'"
              >
                <p
                  class="text-xs font-medium"
                  :class="message.role === 'user' ? 'text-white/70' : 'text-[#6c757d]'"
                >
                  Quellen:
                </p>
                <div
                  v-for="(citation, idx) in message.citations_json"
                  :key="idx"
                  class="text-xs rounded px-2 py-1.5"
                  :class="message.role === 'user' ? 'bg-white/10' : 'bg-[#f8f9fa]'"
                >
                  <p
                    class="font-medium"
                    :class="message.role === 'user' ? 'text-white' : 'text-[#495057]'"
                  >
                    {{ citation.document_title }}
                  </p>
                  <p
                    class="mt-0.5 italic"
                    :class="message.role === 'user' ? 'text-white/70' : 'text-[#6c757d]'"
                  >
                    "{{ citation.excerpt }}"
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Denkindikator -->
          <div v-if="chatStore.isSending" class="flex justify-start">
            <div class="bg-white border border-[#dee2e6] rounded-lg px-4 py-3 flex items-center gap-2">
              <AppSpinner size="sm" class="text-[#adb5bd]" />
              <span class="text-sm text-[#6c757d]">Analysiere Dokument…</span>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Eingabe-Leiste -->
    <div class="bg-white border-t border-[#dee2e6] flex-shrink-0 shadow-sm">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4">
        <!-- Fehler -->
        <div
          v-if="chatStore.error"
          class="mb-2 text-sm text-[#dc3545]"
          role="alert"
        >
          {{ chatStore.error }}
        </div>

        <form @submit.prevent="handleSend" class="flex gap-3">
          <input
            v-model="inputMessage"
            type="text"
            placeholder="Ihre Frage zum Dokument…"
            :disabled="chatStore.isSending"
            class="flex-1 rounded-lg border border-[#ced4da] px-4 py-2.5 text-sm text-[#212529] placeholder-[#adb5bd] bg-white focus:border-[#005d22] focus:outline-none focus:ring-2 focus:ring-[#005d22]/20 disabled:opacity-50 transition-colors"
            @keydown.enter.exact.prevent="handleSend"
          />
          <button
            type="submit"
            :disabled="!inputMessage.trim() || chatStore.isSending"
            class="rounded-lg bg-[#005d22] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#004a1b] focus:outline-none focus:ring-2 focus:ring-[#005d22] focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Senden
          </button>
        </form>
        <p class="mt-2 text-xs text-[#adb5bd]">
          Antworten basieren ausschließlich auf Ihren Dokumenten. Keine Rechtsberatung.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'
import AppHeader from '@/components/common/AppHeader.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'

const route = useRoute()
const router = useRouter()
const chatStore = useChatStore()
const authStore = useAuthStore()
const messagesContainer = ref<HTMLElement | null>(null)
const inputMessage = ref('')

const sessionId = computed(() => route.params.sessionId as string)
const session = computed(() => chatStore.currentSession)
const messages = computed(() => chatStore.messages)

onMounted(async () => {
  await chatStore.fetchSessions()
  const found = chatStore.sessions.find((s) => s.id === sessionId.value)
  if (found) {
    await chatStore.loadSession(found)
  }
  scrollToBottom()
})

watch(messages, () => {
  nextTick(() => scrollToBottom())
})

async function handleSend() {
  const content = inputMessage.value.trim()
  if (!content || chatStore.isSending) return
  inputMessage.value = ''
  try {
    await chatStore.sendMessage(content)
  } catch {
    // Fehler über chatStore.error angezeigt
  }
}

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}
</script>
