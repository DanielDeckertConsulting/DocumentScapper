<template>
  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', badgeClass]">
    <span v-if="status === 'processing'" class="mr-1">
      <AppSpinner size="sm" />
    </span>
    {{ label }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import type { DocumentStatus } from '@/types/document'

const props = defineProps<{ status: DocumentStatus }>()

const badgeClass = computed(() => ({
  'bg-[#fff3cd] text-[#664d03]': props.status === 'uploaded',
  'bg-[#e8f5ec] text-[#005d22]': props.status === 'processing',
  'bg-[#d1e7dd] text-[#0a3622]': props.status === 'processed',
  'bg-[#f8d7da] text-[#842029]': props.status === 'failed',
}))

const label = computed(() => ({
  uploaded: 'Hochgeladen',
  processing: 'Wird verarbeitet…',
  processed: 'Verarbeitet',
  failed: 'Fehler',
}[props.status]))
</script>
