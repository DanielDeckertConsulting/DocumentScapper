<template>
  <div class="min-h-screen flex flex-col bg-[#f8f9fa]">
    <!-- Oberer Akzentstreifen -->
    <div class="h-1 bg-[#005d22]" />

    <div class="flex-1 flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-md">
        <!-- Logo-Block -->
        <div class="flex justify-center mb-8">
          <img
            src="/images/logo_2024.png"
            alt="FINAS Versicherungsmakler GmbH"
            class="h-14 w-auto object-contain"
          />
        </div>

        <!-- Login-Karte -->
        <div class="bg-white rounded-lg border border-[#dee2e6] shadow-sm p-8">
          <div class="mb-6">
            <h1 class="text-xl font-semibold text-[#212529]">Anmelden</h1>
            <p class="mt-1 text-sm text-[#6c757d]">Ihre Dokumente — intelligent analysiert</p>
          </div>

          <form @submit.prevent="handleLogin" class="space-y-4">
            <div>
              <label for="email" class="block text-sm font-medium text-[#495057] mb-1">
                E-Mail-Adresse
              </label>
              <input
                id="email"
                v-model="email"
                type="email"
                autocomplete="email"
                required
                class="w-full rounded-md border border-[#ced4da] px-3 py-2 text-sm text-[#212529] bg-white shadow-sm placeholder-[#adb5bd] focus:border-[#005d22] focus:outline-none focus:ring-2 focus:ring-[#005d22]/20 transition-colors"
                placeholder="ihre@email.de"
              />
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-[#495057] mb-1">
                Passwort
              </label>
              <input
                id="password"
                v-model="password"
                type="password"
                autocomplete="current-password"
                required
                class="w-full rounded-md border border-[#ced4da] px-3 py-2 text-sm text-[#212529] bg-white shadow-sm placeholder-[#adb5bd] focus:border-[#005d22] focus:outline-none focus:ring-2 focus:ring-[#005d22]/20 transition-colors"
                placeholder="••••••••••••"
              />
            </div>

            <div
              v-if="errorMessage"
              class="rounded-md bg-[#f8d7da] border border-[#f5c2c7] px-3 py-2 text-sm text-[#842029]"
              role="alert"
            >
              {{ errorMessage }}
            </div>

            <button
              type="submit"
              :disabled="isLoading"
              class="w-full rounded-md bg-[#005d22] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#004a1b] focus:outline-none focus:ring-2 focus:ring-[#005d22] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors mt-2"
            >
              <span v-if="isLoading">Anmelden…</span>
              <span v-else>Anmelden</span>
            </button>
          </form>

          <!-- Divider -->
          <div class="mt-4 relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-[#dee2e6]" />
            </div>
            <div class="relative flex justify-center text-xs">
              <span class="bg-white px-2 text-[#adb5bd]">oder</span>
            </div>
          </div>

          <button
            type="button"
            :disabled="isLoading"
            @click="loginAsDemo"
            class="mt-4 w-full rounded-md border border-[#ced4da] bg-white px-4 py-2.5 text-sm font-medium text-[#495057] shadow-sm hover:bg-[#f8f9fa] focus:outline-none focus:ring-2 focus:ring-[#005d22] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Demo-Zugang verwenden
          </button>
        </div>

        <p class="mt-6 text-xs text-[#adb5bd] text-center">
          Mit der Anmeldung stimmen Sie den Nutzungsbedingungen zu.
          Dieses System bietet keine Rechtsberatung.
        </p>
      </div>
    </div>

    <!-- Footer-Linie -->
    <div class="h-px bg-[#dee2e6]" />
    <div class="py-3 text-center text-xs text-[#adb5bd]">
      © FINAS Versicherungsmakler GmbH
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

async function handleLogin() {
  isLoading.value = true
  errorMessage.value = ''

  try {
    await authStore.login(email.value, password.value)
    const redirect = (route.query.redirect as string) || '/documents'
    router.push(redirect)
  } catch (error: any) {
    const errors = error.response?.data?.errors
    if (errors?.email) {
      errorMessage.value = errors.email[0]
    } else {
      errorMessage.value = error.response?.data?.message ?? 'Anmeldung fehlgeschlagen.'
    }
  } finally {
    isLoading.value = false
  }
}

async function loginAsDemo() {
  email.value = 'demo@example.com'
  password.value = 'password'
  await handleLogin()
}
</script>
