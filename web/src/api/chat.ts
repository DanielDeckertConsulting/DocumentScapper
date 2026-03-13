import client from './client'
import type { ChatSession, ChatMessage } from '@/types/chat'

export async function listChatSessions(): Promise<ChatSession[]> {
  const response = await client.get<{ data: ChatSession[] }>('/api/chat-sessions')
  return response.data.data
}

export async function createChatSession(data: {
  document_id?: string | null
  title?: string | null
}): Promise<ChatSession> {
  const response = await client.post<{ data: ChatSession }>('/api/chat-sessions', data)
  return response.data.data
}

export async function getMessages(sessionId: string): Promise<ChatMessage[]> {
  const response = await client.get<{ data: ChatMessage[] }>(`/api/chat-sessions/${sessionId}/messages`)
  return response.data.data
}

export async function sendMessage(sessionId: string, content: string): Promise<ChatMessage> {
  const response = await client.post<{ data: ChatMessage }>(
    `/api/chat-sessions/${sessionId}/messages`,
    { content },
  )
  return response.data.data
}
