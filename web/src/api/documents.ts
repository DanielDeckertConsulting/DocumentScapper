import client from './client'
import type { Document } from '@/types/document'

export async function listDocuments(): Promise<Document[]> {
  const response = await client.get<{ data: Document[] }>('/api/documents')
  return response.data.data
}

export async function getDocument(id: string): Promise<Document> {
  const response = await client.get<{ data: Document }>(`/api/documents/${id}`)
  return response.data.data
}

export async function uploadDocument(file: File, onProgress?: (percent: number) => void): Promise<Document> {
  const formData = new FormData()
  formData.append('file', file)

  const response = await client.post<{ data: Document }>('/api/documents', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    onUploadProgress: (event) => {
      if (onProgress && event.total) {
        onProgress(Math.round((event.loaded / event.total) * 100))
      }
    },
  })
  return response.data.data
}

export async function deleteDocument(id: string): Promise<void> {
  await client.delete(`/api/documents/${id}`)
}
