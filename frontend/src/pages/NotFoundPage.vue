<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-6">
    <div class="text-center max-w-lg">
      <div class="text-8xl font-bold text-blue-600 mb-6">
        404
      </div>

      <h1 class="text-3xl font-bold text-gray-900 mb-4">
        Страница не найдена
      </h1>

      <p class="text-gray-600 mb-8">
        Возможно страница была удалена,
        перемещена или ссылка неверна.
      </p>

      <RouterLink
          to="/"
          class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 transition"
      >
        На главную
      </RouterLink>
    </div>
  </div>
</template>