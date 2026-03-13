<template>
  <header class="bg-white border-b border-[#dee2e6] shadow-sm">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
      <!-- Logo -->
      <RouterLink to="/documents" class="flex-shrink-0 flex items-center gap-3">
        <img
          src="/images/logo_2024.png"
          alt="FINAS Versicherungsmakler GmbH"
          class="h-9 w-auto object-contain"
        />
      </RouterLink>

      <!-- Slot für optionalen Breadcrumb / Seitentitel -->
      <div v-if="$slots.breadcrumb" class="hidden sm:flex items-center gap-2 text-sm text-[#6c757d] min-w-0 flex-1">
        <slot name="breadcrumb" />
      </div>

      <!-- Rechte Seite: Logout oder Custom Actions -->
      <div class="flex items-center gap-3 flex-shrink-0">
        <slot name="actions" />
        <button
          v-if="showLogout"
          @click="$emit('logout')"
          class="text-sm text-[#6c757d] hover:text-[#212529] transition-colors px-2 py-1 rounded hover:bg-[#f8f9fa]"
        >
          Abmelden
        </button>
      </div>
    </div>

    <!-- Mobile breadcrumb (under header) -->
    <div v-if="$slots.breadcrumb" class="sm:hidden border-t border-[#dee2e6] bg-[#f8f9fa]">
      <div class="max-w-5xl mx-auto px-4 py-2 text-xs text-[#6c757d] flex items-center gap-1.5 min-w-0">
        <slot name="breadcrumb" />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'

withDefaults(defineProps<{
  showLogout?: boolean
}>(), {
  showLogout: false,
})

defineEmits<{
  logout: []
}>()
</script>
