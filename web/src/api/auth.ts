import client from './client'
import type { User } from '@/types/auth'

export async function login(email: string, password: string): Promise<User> {
  const response = await client.post<{ token: string; user: User }>('/api/auth/login', {
    email,
    password,
  })
  localStorage.setItem('auth_token', response.data.token)
  return response.data.user
}

export async function logout(): Promise<void> {
  await client.post('/api/auth/logout')
  localStorage.removeItem('auth_token')
}

export async function getUser(): Promise<User> {
  const response = await client.get<User>('/api/auth/user')
  return response.data
}
