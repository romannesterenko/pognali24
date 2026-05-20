<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/api/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const conversations = ref<any[]>([])
const loading = ref(false)

// Форматирование даты сообщения
const formatMessageDate = (dateStr: string | null) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (date >= today) {
    return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
  }
  if (date >= yesterday) {
    return 'Вчера'
  }
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}

// Получение собеседника
const getOtherUser = (conversation: any) => {
  const member = conversation.members?.find((m: any) => m.user_id !== auth.user.id)
  return member?.user
}

// Инициалы для аватара-заглушки
const getInitials = (name: string) => {
  return name?.charAt(0)?.toUpperCase() || '?'
}

const load = async () => {
  loading.value = true
  try {
    const res = await api.get('/conversations')
    conversations.value = res.data.conversations || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <AuthLayout>
    <div class="max-w-5xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent">
          Сообщения
        </h1>
        <p class="text-slate-500 mt-2">
          Ваши диалоги с водителями и пассажирами
        </p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-20">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <!-- Empty state -->
      <div
          v-else-if="conversations.length === 0"
          class="bg-white rounded-2xl border border-slate-200 p-12 text-center"
      >
        <div class="text-6xl mb-4">💬</div>
        <h3 class="text-xl font-semibold text-slate-900 mb-2">Пока нет сообщений</h3>
        <p class="text-slate-500">Забронируйте поездку и начните общение</p>
      </div>

      <!-- Conversations list -->
      <div v-else class="space-y-3">
        <div
            v-for="conversation in conversations"
            :key="conversation.id"
            @click="router.push(`/conversations/${conversation.id}`)"
            class="group bg-white rounded-2xl border border-slate-200 p-4 hover:shadow-md transition-all duration-200 cursor-pointer"
        >
          <div class="flex items-center gap-4">
            <!-- Avatar -->
            <div class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex-shrink-0">
              <img
                  v-if="getOtherUser(conversation)?.profile?.avatar_url"
                  :src="getOtherUser(conversation).profile.avatar_url"
                  class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-lg font-medium text-slate-500">
                {{ getInitials(getOtherUser(conversation)?.name) }}
              </div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-baseline justify-between gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-semibold text-slate-900 group-hover:text-blue-600 transition">
                    {{ getOtherUser(conversation)?.name }}
                  </span>
                  <span class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">
                    #{{ conversation.trip?.id }}
                  </span>
                </div>
                <span class="text-xs text-slate-400 whitespace-nowrap">
                  {{ formatMessageDate(conversation.latest_message?.created_at) }}
                </span>
              </div>

              <div class="text-sm text-slate-500 mt-0.5">
                {{ conversation.trip.from_locality.name }} → {{ conversation.trip.to_locality.name }}
              </div>

              <div class="text-sm text-slate-600 mt-1 truncate">
                {{ conversation.latest_message?.message || 'Сообщений пока нет' }}
              </div>
            </div>

            <!-- Optional unread badge (можно добавить позже) -->
            <div v-if="conversation.unread_count > 0" class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></div>
          </div>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>

<style scoped>
.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>