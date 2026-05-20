<script setup lang="ts">
import echo from "@/lib/echo.ts";
import api from '@/api/axios';
import {onMounted, ref, computed, onUnmounted} from 'vue'
import { useRoute } from 'vue-router'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from "@/stores/toast";
import ToastContainer from "@/components/ToastContainer.vue";

const route = useRoute()

const auth = useAuthStore()
const isAuth = computed(() => !!auth.user)
const user = computed(() => auth.user)
let channel: any = null
const chat = useChatStore()
const toast = useToastStore()

const notificationsCount = ref(0)

const mobileMenuOpen = ref(false)

const isActive = (path: string) => {
  return route.path === path
}

const loadNotificationsCount = async () => {

  try {

    const res = await api.get('/notifications/unread-count')

    notificationsCount.value = res.data.count || 0

  } catch (e) {

    console.error(e)

  }
}

onMounted(async () => {
  if (!auth.user) {
    return
  }
  console.log(auth.user.id);
  channel = echo.private(`user.${auth.user.id}`)

  channel.listen(
      '.platform.event',
      (e: any) => {
        toast.show({
          title: e.title,
          message: e.message,
          type: e.type,
        })
        loadNotificationsCount()
        chat.fetchUnread()
      }
  )

  await chat.fetchUnread()

  chat.listen()

  await loadNotificationsCount()

})
onUnmounted(() => {
  if(channel) {
    echo.leave(`private-user.${auth.user.id}`)
  }
})
</script>

<template>

  <div class="min-h-screen bg-gray-100">

    <!-- DESKTOP + MOBILE HEADER -->

    <header class="bg-white border-b sticky top-0 z-50">

      <div class="max-w-7xl mx-auto px-4 sm:px-6">

        <div class="h-16 flex items-center justify-between">

          <!-- LEFT -->

          <div class="flex items-center gap-8" v-if="isAuth">

            <!-- LOGO -->

            <RouterLink
                to="/"
                class="text-2xl font-black text-blue-600"
            >
              Pognali24
            </RouterLink>

            <!-- DESKTOP NAV -->

            <nav class="hidden lg:flex items-center gap-6">

              <RouterLink
                  to="/"
                  class="text-sm font-medium transition"
                  :class="isActive('/')
                  ? 'text-blue-600'
                  : 'text-gray-700 hover:text-blue-600'"
              >
                Главная
              </RouterLink>

              <RouterLink
                  to="/search"
                  class="text-sm font-medium transition"
                  :class="isActive('/search')
                  ? 'text-blue-600'
                  : 'text-gray-700 hover:text-blue-600'"
              >
                Поиск
              </RouterLink>

              <RouterLink
                  to="/driver/trips"
                  class="text-sm font-medium transition"
                  :class="isActive('/driver/trips')
                  ? 'text-blue-600'
                  : 'text-gray-700 hover:text-blue-600'"
              >
                Поездки
              </RouterLink>

              <RouterLink
                  to="/bookings"
                  class="text-sm font-medium transition"
                  :class="isActive('/bookings')
                  ? 'text-blue-600'
                  : 'text-gray-700 hover:text-blue-600'"
              >
                Брони
              </RouterLink>

              <RouterLink
                  to="/conversations"
                  class="relative text-sm font-medium transition"
                  :class="isActive('/conversations')
                  ? 'text-blue-600'
                  : 'text-gray-700 hover:text-blue-600'"
              >
                Сообщения

                <span
                    v-if="chat.unreadConversations > 0"
                    class="absolute -top-2 -right-3 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"
                >
                  {{ chat.unreadConversations }}
                </span>

              </RouterLink>

              <RouterLink
                  to="/notifications"
                  class="relative text-sm font-medium transition"
                  :class="isActive('/notifications')
                  ? 'text-blue-600'
                  : 'text-gray-700 hover:text-blue-600'"
              >
                Уведомления

                <div
                    v-if="notificationsCount > 0"
                    class="absolute -top-2 -right-3 min-w-[20px] h-5 px-1 rounded-full bg-red-500 text-white text-xs flex items-center justify-center"
                >
                  {{ notificationsCount }}
                </div>

              </RouterLink>

            </nav>

          </div>
          <div class="flex items-center gap-8" v-else>

            <!-- LOGO -->

            <RouterLink
                to="/"
                class="text-2xl font-black text-blue-600"
            >
              Pognali24
            </RouterLink>

            <!-- DESKTOP NAV -->

            <nav class="hidden lg:flex items-center gap-6">

              <RouterLink
                  to="/"
                  class="text-sm font-medium transition"
                  :class="isActive('/')
                  ? 'text-blue-600'
                  : 'text-gray-700 hover:text-blue-600'"
              >
                Главная
              </RouterLink>

              <RouterLink
                  to="/search"
                  class="text-sm font-medium transition"
                  :class="isActive('/search')
                  ? 'text-blue-600'
                  : 'text-gray-700 hover:text-blue-600'"
              >
                Поиск
              </RouterLink>

            </nav>

          </div>

          <!-- RIGHT -->

          <div class="flex items-center gap-3">

            <!-- MOBILE MENU BUTTON -->

            <button
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-100 transition"
            >
              ☰
            </button>

            <!-- USER -->

            <RouterLink
                v-if="isAuth"
                to="/profile"
                class="flex items-center gap-3 hover:bg-gray-100 rounded-xl px-2 sm:px-3 py-2 transition"
            >

              <div
                  class="relative w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold overflow-hidden"
              >

                <img
                    v-if="user?.profile?.avatar_url"
                    :src="user.profile.avatar_url"
                    class="w-full h-full object-cover"
                />

                <template v-else>
                  {{ user?.name?.[0] || 'U' }}
                </template>

              </div>

              <div class="hidden md:block">

                <div class="text-sm font-semibold text-gray-800">
                  {{ user?.name }}
                </div>

                <div class="text-xs text-gray-500">
                  Личный кабинет
                </div>

              </div>

            </RouterLink>
            <div v-else class="flex items-center gap-3">

              <RouterLink
                  to="/login"
                  class="text-sm font-medium text-gray-700 hover:text-blue-600"
              >
                Войти
              </RouterLink>

              <RouterLink
                  to="/register"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium"
              >
                Регистрация
              </RouterLink>

            </div>

          </div>

        </div>

      </div>

      <!-- MOBILE MENU -->

      <div
          v-if="mobileMenuOpen"
          class="lg:hidden border-t bg-white"
      >

        <div v-if="isAuth" class="px-4 py-4 flex flex-col gap-2">

          <RouterLink
              @click="mobileMenuOpen = false"
              to="/"
              class="px-4 py-3 rounded-xl"
              :class="isActive('/')
              ? 'bg-blue-50 text-blue-600 font-medium'
              : 'hover:bg-gray-50 text-gray-700'"
          >
            🏠 Главная
          </RouterLink>

          <RouterLink
              @click="mobileMenuOpen = false"
              to="/search"
              class="px-4 py-3 rounded-xl"
              :class="isActive('/search')
              ? 'bg-blue-50 text-blue-600 font-medium'
              : 'hover:bg-gray-50 text-gray-700'"
          >
            🔍 Поиск поездок
          </RouterLink>

          <RouterLink
              @click="mobileMenuOpen = false"
              to="/driver/trips"
              class="px-4 py-3 rounded-xl"
              :class="isActive('/driver/trips')
              ? 'bg-blue-50 text-blue-600 font-medium'
              : 'hover:bg-gray-50 text-gray-700'"
          >
            🚗 Мои поездки
          </RouterLink>

          <RouterLink
              @click="mobileMenuOpen = false"
              to="/bookings"
              class="px-4 py-3 rounded-xl"
              :class="isActive('/bookings')
              ? 'bg-blue-50 text-blue-600 font-medium'
              : 'hover:bg-gray-50 text-gray-700'"
          >
            📋 Мои бронирования
          </RouterLink>

          <RouterLink
              @click="mobileMenuOpen = false"
              to="/conversations"
              class="flex items-center justify-between px-4 py-3 rounded-xl"
              :class="isActive('/conversations')
              ? 'bg-blue-50 text-blue-600 font-medium'
              : 'hover:bg-gray-50 text-gray-700'"
          >
            <span>💬 Сообщения</span>

            <span
                v-if="chat.unreadConversations > 0"
                class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"
            >
              {{ chat.unreadConversations }}
            </span>

          </RouterLink>

          <RouterLink
              @click="mobileMenuOpen = false"
              to="/notifications"
              class="flex items-center justify-between px-4 py-3 rounded-xl"
              :class="isActive('/notifications')
              ? 'bg-blue-50 text-blue-600 font-medium'
              : 'hover:bg-gray-50 text-gray-700'"
          >
            <span>🔔 Уведомления</span>

            <span
                v-if="notificationsCount > 0"
                class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"
            >
              {{ notificationsCount }}
            </span>

          </RouterLink>

        </div>
        <div v-else class="px-4 py-4 flex flex-col gap-2">

          <RouterLink
              @click="mobileMenuOpen = false"
              to="/"
              class="px-4 py-3 rounded-xl"
              :class="isActive('/')
              ? 'bg-blue-50 text-blue-600 font-medium'
              : 'hover:bg-gray-50 text-gray-700'"
          >
            🏠 Главная
          </RouterLink>

          <RouterLink
              @click="mobileMenuOpen = false"
              to="/search"
              class="px-4 py-3 rounded-xl"
              :class="isActive('/search')
              ? 'bg-blue-50 text-blue-600 font-medium'
              : 'hover:bg-gray-50 text-gray-700'"
          >
            🔍 Поиск поездок
          </RouterLink>

        </div>

      </div>

    </header>

    <!-- PAGE -->

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8 pb-28 lg:pb-8">

      <slot />

    </main>

    <!-- MOBILE BOTTOM NAV -->

    <div
        v-if="isAuth"
        class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t z-50"
    >

      <div class="grid grid-cols-5 h-16">

        <RouterLink
            to="/"
            class="flex flex-col items-center justify-center text-xs"
            :class="isActive('/')
            ? 'text-blue-600'
            : 'text-gray-500'"
        >
          <span class="text-lg">🏠</span>
          Главная
        </RouterLink>

        <RouterLink
            to="/search"
            class="flex flex-col items-center justify-center text-xs"
            :class="isActive('/search')
            ? 'text-blue-600'
            : 'text-gray-500'"
        >
          <span class="text-lg">🔍</span>
          Поиск
        </RouterLink>

        <RouterLink
            to="/conversations"
            class="relative flex flex-col items-center justify-center text-xs"
            :class="isActive('/conversations')
            ? 'text-blue-600'
            : 'text-gray-500'"
        >
          <span class="text-lg">💬</span>
          Чаты

          <span
              v-if="chat.unreadConversations > 0"
              class="absolute top-1 right-4 bg-red-500 text-white text-[10px] min-w-[18px] h-[18px] rounded-full flex items-center justify-center px-1"
          >
            {{ chat.unreadConversations }}
          </span>

        </RouterLink>

        <RouterLink
            to="/bookings"
            class="flex flex-col items-center justify-center text-xs"
            :class="isActive('/bookings')
            ? 'text-blue-600'
            : 'text-gray-500'"
        >
          <span class="text-lg">📋</span>
          Брони
        </RouterLink>

        <RouterLink
            to="/profile"
            class="flex flex-col items-center justify-center text-xs"
            :class="isActive('/profile')
            ? 'text-blue-600'
            : 'text-gray-500'"
        >
          <span class="text-lg">👤</span>
          Профиль
        </RouterLink>

      </div>

    </div>

  </div>

  <ToastContainer />

</template>