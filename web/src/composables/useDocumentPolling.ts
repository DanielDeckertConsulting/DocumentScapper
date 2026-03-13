import { onUnmounted } from 'vue'
import { useDocumentsStore } from '@/stores/documents'

export function useDocumentPolling(documentId: string) {
  const store = useDocumentsStore()
  let interval: ReturnType<typeof setInterval> | null = null

  function startPolling(intervalMs = 3000) {
    stopPolling()
    interval = setInterval(async () => {
      const doc = await store.refreshDocument(documentId)
      if (doc.status === 'processed' || doc.status === 'failed') {
        stopPolling()
      }
    }, intervalMs)
  }

  function stopPolling() {
    if (interval !== null) {
      clearInterval(interval)
      interval = null
    }
  }

  onUnmounted(stopPolling)

  return { startPolling, stopPolling }
}
