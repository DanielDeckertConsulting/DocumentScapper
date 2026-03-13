import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as apiLogin, logout as apiLogout, getUser } from '@/api/auth'
import type { User } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isAuthenticated = computed(() => user.value !== null)

  async function initialize() {
    try {
      user.value = await getUser()
    } catch {
      user.value = null
    }
  }

  async function login(email: string, password: string) {
    user.value = await apiLogin(email, password)
  }

  async function logout() {
    try {
      await apiLogout()
    } finally {
      user.value = null
    }
  }

  function clearUser() {
    user.value = null
  }

  return { user, isAuthenticated, initialize, login, logout, clearUser }
})
