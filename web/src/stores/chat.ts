import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as chatApi from '@/api/chat'
import type { ChatSession, ChatMessage } from '@/types/chat'

export const useChatStore = defineStore('chat', () => {
  const sessions = ref<ChatSession[]>([])
  const currentSession = ref<ChatSession | null>(null)
  const messages = ref<ChatMessage[]>([])
  const isLoading = ref(false)
  const isSending = ref(false)
  const error = ref<string | null>(null)

  async function fetchSessions() {
    isLoading.value = true
    try {
      sessions.value = await chatApi.listChatSessions()
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Sitzungen konnten nicht geladen werden.'
    } finally {
      isLoading.value = false
    }
  }

  async function createSession(documentId?: string | null, title?: string | null) {
    const session = await chatApi.createChatSession({ document_id: documentId, title })
    sessions.value.unshift(session)
    currentSession.value = session
    messages.value = []
    return session
  }

  async function loadSession(session: ChatSession) {
    currentSession.value = session
    isLoading.value = true
    try {
      messages.value = await chatApi.getMessages(session.id)
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Nachrichten konnten nicht geladen werden.'
    } finally {
      isLoading.value = false
    }
  }

  async function sendMessage(content: string) {
    if (!currentSession.value) return

    const optimisticMessage: ChatMessage = {
      id: `temp-${Date.now()}`,
      role: 'user',
      content,
      citations_json: [],
      created_at: new Date().toISOString(),
    }
    messages.value.push(optimisticMessage)

    isSending.value = true
    error.value = null
    try {
      const assistantMessage = await chatApi.sendMessage(currentSession.value.id, content)
      messages.value.push(assistantMessage)
    } catch (e: any) {
      messages.value = messages.value.filter((m) => m.id !== optimisticMessage.id)
      error.value = e.response?.data?.message ?? 'Nachricht konnte nicht gesendet werden.'
      throw e
    } finally {
      isSending.value = false
    }
  }

  return {
    sessions,
    currentSession,
    messages,
    isLoading,
    isSending,
    error,
    fetchSessions,
    createSession,
    loadSession,
    sendMessage,
  }
})
