<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <h1 class="text-4xl font-bold text-gray-900">404</h1>
      <p class="mt-2 text-gray-500">Diese Seite wurde nicht gefunden.</p>
      <RouterLink to="/" class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-700">
        Zur Startseite
      </RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>
