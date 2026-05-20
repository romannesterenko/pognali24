<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>

  <AuthLayout>

    <h1 class="text-3xl font-bold">
      Драссти, {{ auth.user?.name }}! Погнали?
    </h1>
  </AuthLayout>

</template>