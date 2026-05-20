<script setup lang="ts">
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
</script>

<template>

  <div
      class="fixed top-5 right-5 z-[9999] space-y-3"
  >

    <TransitionGroup
        name="toast"
        tag="div"
    >

      <div
          v-for="item in toast.toasts"
          :key="item.id"
          class="w-[340px] bg-white border shadow-xl rounded-2xl p-5"
      >

        <div class="flex items-start justify-between gap-4">

          <div>

            <div class="font-bold text-gray-900">
              {{ item.title }}
            </div>

            <div class="text-sm text-gray-600 mt-1">
              {{ item.message }}
            </div>

          </div>

          <button
              @click="toast.remove(item.id)"
              class="text-gray-400 hover:text-gray-700"
          >
            ✕
          </button>

        </div>

      </div>

    </TransitionGroup>

  </div>

</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all .25s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>