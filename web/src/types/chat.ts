export type MessageRole = 'user' | 'assistant' | 'system'

export interface Citation {
  document_id: string
  document_title: string
  chunk_index: number
  page_reference: number | null
  excerpt: string
}

export interface ChatMessage {
  id: string
  role: MessageRole
  content: string
  citations_json: Citation[]
  created_at: string
}

export interface ChatSession {
  id: string
  document_id: string | null
  title: string | null
  created_at: string
}
