<script setup lang="ts">
import { ref, onMounted, nextTick, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'
import echo from '@/lib/echo'

const route = useRoute()
const auth = useAuthStore()

const conversation = ref<any>(null)
const messages = ref<any[]>([])
const message = ref('')
const loading = ref(false)
const loadingMessages = ref(false)
const messagesContainer = ref<HTMLElement | null>(null)

let channel: any = null

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatTime = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (date >= today) return 'Сегодня'
  if (date >= yesterday) return 'Вчера'
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
}

const getOtherUser = () => {
  const member = conversation.value?.members.find((m: any) => m.user_id !== auth.user.id)
  return member?.user
}

const getInitials = (name: string) => {
  return name?.charAt(0)?.toUpperCase() || '?'
}

const loadConversation = async () => {
  try {
    const res = await api.get(`/conversations/${route.params.id}`)
    conversation.value = res.data.conversation
  } catch (e) {
    console.error(e)
  }
}

const loadMessages = async () => {
  loadingMessages.value = true
  try {
    const res = await api.get(`/conversations/${route.params.id}/messages`)
    messages.value = res.data.messages

    // Пометить как прочитанные
    await api.post(`/conversations/${route.params.id}/read`)
    await scrollToBottom()
  } catch (e) {
    console.error(e)
  } finally {
    loadingMessages.value = false
  }
}

const sendMessage = async () => {
  if (!message.value.trim()) return
  loading.value = true
  try {
    await api.post(`/conversations/${route.params.id}/messages`, { message: message.value })
    message.value = ''
    // Сообщение придёт через WebSocket, но для оптимизации можно добавить локально
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const setupWebSocket = () => {
  //if (channel) channel.stopListening()
  channel = echo.private(`conversation.${route.params.id}`)


  channel.listen('.message.sent', (e: any) => {
    messages.value.push(e)
    scrollToBottom()
    // Пометить как прочитанные при получении нового сообщения
    //api.post(`/conversations/${route.params.id}/read`).catch(console.error)
  })
}

onMounted(async () => {
  await loadConversation()
  await loadMessages()
  setupWebSocket()
  await scrollToBottom()
})
</script>

<template>
  <AuthLayout>
    <div class="max-w-5xl mx-auto px-4 py-8">
      <!-- Загрузка данных чата -->
      <div v-if="!conversation && !loadingMessages" class="flex justify-center py-20">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <div v-else-if="conversation" class="space-y-4">
        <!-- Header карточка -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Левый блок: пользователь -->
            <div class="flex items-center gap-4">
              <!-- Аватар -->
              <div class="w-14 h-14 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex-shrink-0">
                <img
                    v-if="getOtherUser()?.profile?.avatar_url"
                    :src="getOtherUser().profile.avatar_url"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-xl font-medium text-slate-500">
                  {{ getInitials(getOtherUser()?.name) }}
                </div>
              </div>
              <!-- Имя и маршрут -->
              <div>
                <div class="font-bold text-slate-900 text-lg">{{ getOtherUser()?.name }}</div>
                <div class="text-sm text-slate-500 mt-0.5">
                  {{ conversation.trip.from_locality.name }} → {{ conversation.trip.to_locality.name }}
                </div>
              </div>
            </div>

            <!-- Правый блок: инфо о поездке -->
            <div class="text-right text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-2">
              <div>🚗 {{ conversation.trip.car.brand }} {{ conversation.trip.car.model }}</div>
              <div class="mt-1">💰 {{ conversation.trip.price }} ₽</div>
            </div>
          </div>
        </div>

        <!-- Область сообщений -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div
              ref="messagesContainer"
              class="h-[500px] overflow-y-auto p-4 space-y-4"
          >
            <!-- Лоадер сообщений -->
            <div v-if="loadingMessages" class="flex justify-center py-10">
              <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-600 border-t-transparent"></div>
            </div>

            <!-- Группировка сообщений по датам -->
            <div v-else-if="messages.length" class="space-y-6">
              <template v-for="(msg, idx) in messages" :key="msg.id">
                <!-- Разделитель даты -->
                <div
                    v-if="idx === 0 || formatDate(msg.created_at) !== formatDate(messages[idx-1].created_at)"
                    class="text-center text-xs text-slate-400 my-2"
                >
                  {{ formatDate(msg.created_at) }}
                </div>
                <!-- Баллон сообщения -->
                <div
                    class="flex"
                    :class="msg.user_id === auth.user.id ? 'justify-end' : 'justify-start'"
                >
                  <div
                      class="max-w-[85%] sm:max-w-[75%] px-4 py-2.5 rounded-2xl shadow-sm"
                      :class="msg.user_id === auth.user.id
                      ? 'bg-blue-600 text-white rounded-br-none'
                      : 'bg-slate-100 text-slate-800 rounded-bl-none'
                    "
                  >
                    <div class="text-sm whitespace-pre-wrap break-words">
                      {{ msg.message }}
                    </div>
                    <div
                        class="text-[10px] mt-1.5 opacity-70 text-right"
                        :class="msg.user_id === auth.user.id ? 'text-blue-100' : 'text-slate-400'"
                    >
                      {{ formatTime(msg.created_at) }}
                    </div>
                  </div>
                </div>
              </template>
            </div>
            <div v-else class="text-center text-slate-400 py-10">
              Начните диалог — напишите первое сообщение
            </div>
            <!-- Якорь для скролла -->
            <div ref="scrollAnchor"></div>
          </div>

          <!-- Форма отправки -->
          <div class="border-t border-slate-100 p-4 bg-slate-50/50">
            <div class="flex gap-3 items-end">
              <textarea
                  v-model="message"
                  rows="1"
                  placeholder="Введите сообщение..."
                  class="flex-1 border border-slate-200 rounded-xl p-3 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                  @keydown.enter.exact.prevent="sendMessage"
                  @keydown.enter.shift.exact=""
              />
              <button
                  @click="sendMessage"
                  :disabled="loading || !message.trim()"
                  class="bg-blue-600 hover:bg-blue-700 transition text-white px-6 py-3 rounded-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
              >
                {{ loading ? '...' : '→' }}
              </button>
            </div>
            <div class="text-xs text-slate-400 mt-2 text-center sm:text-left">
              Shift + Enter — новая строка, Enter — отправить
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>

<style scoped>
/* Плавный скролл и переносы */
.overflow-y-auto {
  scroll-behavior: smooth;
}
.whitespace-pre-wrap {
  white-space: pre-wrap;
  word-break: break-word;
}
textarea {
  line-height: 1.5;
  min-height: 44px;
  max-height: 120px;
}
</style>