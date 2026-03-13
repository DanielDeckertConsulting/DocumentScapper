import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as documentsApi from '@/api/documents'
import type { Document } from '@/types/document'

export const useDocumentsStore = defineStore('documents', () => {
  const documents = ref<Document[]>([])
  const currentDocument = ref<Document | null>(null)
  const isLoading = ref(false)
  const uploadProgress = ref(0)
  const error = ref<string | null>(null)

  async function fetchDocuments() {
    isLoading.value = true
    error.value = null
    try {
      documents.value = await documentsApi.listDocuments()
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Dokumente konnten nicht geladen werden.'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchDocument(id: string) {
    isLoading.value = true
    error.value = null
    try {
      currentDocument.value = await documentsApi.getDocument(id)
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Dokument konnte nicht geladen werden.'
    } finally {
      isLoading.value = false
    }
  }

  async function uploadDocument(file: File) {
    uploadProgress.value = 0
    error.value = null
    try {
      const doc = await documentsApi.uploadDocument(file, (p) => {
        uploadProgress.value = p
      })
      documents.value.unshift(doc)
      return doc
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Upload fehlgeschlagen.'
      throw e
    } finally {
      uploadProgress.value = 0
    }
  }

  async function refreshDocument(id: string): Promise<Document> {
    const doc = await documentsApi.getDocument(id)
    const idx = documents.value.findIndex((d) => d.id === id)
    if (idx !== -1) documents.value[idx] = doc
    if (currentDocument.value?.id === id) currentDocument.value = doc
    return doc
  }

  async function deleteDocument(id: string) {
    await documentsApi.deleteDocument(id)
    documents.value = documents.value.filter((d) => d.id !== id)
    if (currentDocument.value?.id === id) currentDocument.value = null
  }

  return {
    documents,
    currentDocument,
    isLoading,
    uploadProgress,
    error,
    fetchDocuments,
    fetchDocument,
    uploadDocument,
    refreshDocument,
    deleteDocument,
  }
})
