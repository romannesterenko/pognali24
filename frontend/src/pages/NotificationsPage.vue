<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'

// Types
interface Notification {
  id: number
  title: string
  message: string
  type: string
  url?: string
  created_at: string
  read_at: string | null
}

const notifications = ref<Notification[]>([])
const loading = ref(false)
const unreadCount = ref(0)

// Helper: format date relative (сегодня, вчера, или дата)
const formatRelativeDate = (dateStr: string): string => {
  const date = new Date(dateStr)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (date >= today) return 'Сегодня'
  if (date >= yesterday) return 'Вчера'
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
}

// Group notifications by date
const groupedNotifications = computed(() => {
  const groups: Record<string, Notification[]> = {}
  for (const n of notifications.value) {
    const key = formatRelativeDate(n.created_at)
    if (!groups[key]) groups[key] = []
    groups[key].push(n)
  }
  return groups
})

// Icon mapping
const getIcon = (type: string): string => {
  const icons: Record<string, string> = {
    message: '💬',
    booking_approved: '✅',
    booking_rejected: '❌',
    trip_started: '🚘',
    trip_completed: '🏁',
    trip_cancelled: '🚫',
    review_received: '⭐',
    default: '🔔'
  }
  return icons[type] || icons.default
}

// Format time
const formatTime = (dateStr: string): string => {
  return new Date(dateStr).toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Load data
const loadNotifications = async () => {
  loading.value = true
  try {
    const res = await api.get('/notifications')
    notifications.value = res.data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const loadUnreadCount = async () => {
  try {
    const res = await api.get('/notifications/unread-count')
    unreadCount.value = res.data.count || 0
  } catch (e) {
    console.error(e)
  }
}

const markAsRead = async (notification: Notification) => {
  if (notification.read_at) return
  try {
    await api.post(`/notifications/${notification.id}/read`)
    notification.read_at = new Date().toISOString()
    unreadCount.value--
  } catch (e) {
    console.error(e)
  }
}

const markAllAsRead = async () => {
  try {
    await api.post('/notifications/read-all')
    notifications.value = notifications.value.map(n => ({
      ...n,
      read_at: new Date().toISOString()
    }))
    unreadCount.value = 0
  } catch (e) {
    console.error(e)
  }
}

onMounted(async () => {
  await Promise.all([loadNotifications(), loadUnreadCount()])
})
</script>

<template>
  <AuthLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent">
            Уведомления
          </h1>
          <p class="text-slate-500 mt-2">
            Все события и обновления вашего аккаунта
          </p>
        </div>
        <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="bg-blue-600 hover:bg-blue-700 transition-all transform hover:scale-105 text-white px-5 py-2.5 rounded-xl font-medium shadow-sm"
        >
          Прочитать все ({{ unreadCount }})
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-20">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="notifications.length === 0" class="bg-white rounded-2xl border border-slate-200 p-14 text-center">
        <div class="text-6xl mb-5">🔔</div>
        <h3 class="text-xl font-semibold text-slate-900 mb-2">Пока нет уведомлений</h3>
        <p class="text-slate-500">Здесь будут отображаться сообщения, заявки и события поездок</p>
      </div>

      <!-- Grouped List -->
      <div v-else class="space-y-6">
        <div v-for="(group, dateLabel) in groupedNotifications" :key="dateLabel" class="space-y-3">
          <!-- Date header -->
          <div class="flex items-center gap-2">
            <span class="w-1 h-5 bg-blue-500 rounded-full"></span>
            <h2 class="text-base font-semibold text-slate-700">{{ dateLabel }}</h2>
          </div>

          <!-- Notifications for this date -->
          <div class="space-y-3">
            <div
                v-for="notification in group"
                :key="notification.id"
                @click="markAsRead(notification)"
                class="group bg-white rounded-xl border transition-all duration-200 p-5 cursor-pointer hover:shadow-md"
                :class="[
                !notification.read_at
                  ? 'border-blue-300 bg-blue-50/30 hover:bg-blue-50/50'
                  : 'border-slate-200 hover:bg-slate-50'
              ]"
            >
              <div class="flex items-start gap-4">
                <!-- Icon -->
                <div
                    class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-2xl flex-shrink-0"
                    :class="!notification.read_at ? 'ring-2 ring-blue-200' : ''"
                >
                  {{ getIcon(notification.type) }}
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="flex items-center gap-2 flex-wrap">
                      <h3 class="text-base font-bold text-slate-900">
                        {{ notification.title }}
                      </h3>
                      <span
                          v-if="!notification.read_at"
                          class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"
                      ></span>
                    </div>
                    <span class="text-xs text-slate-400 whitespace-nowrap">
                      {{ formatTime(notification.created_at) }}
                    </span>
                  </div>

                  <p class="text-slate-600 mt-1.5 break-words whitespace-pre-wrap leading-relaxed">
                    {{ notification.message }}
                  </p>

                  <!-- Action link -->
                  <div v-if="notification.url" class="mt-3">
                    <RouterLink
                        :to="notification.url"
                        class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 font-medium transition"
                        @click.stop
                    >
                      Открыть
                      <span class="text-base transition-transform group-hover:translate-x-1">→</span>
                    </RouterLink>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>

<style scoped>
.whitespace-pre-wrap {
  white-space: pre-wrap;
  word-break: break-word;
}
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>