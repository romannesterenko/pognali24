<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})

const loading = ref(false)
const error = ref('')

// Социальные сети (заглушки)
const socialAuth = (provider: string) => {
  alert(`Авторизация через ${provider} в разработке`)
}

const submit = async () => {
  error.value = ''
  if (!form.email.trim() || !form.password.trim()) {
    error.value = 'Заполните все поля'
    return
  }
  loading.value = true
  try {
    await auth.login(form)
    router.push('/')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Неверный email или пароль'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <MainLayout>
    <div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-md">
        <!-- Карточка входа -->
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6 sm:p-8">
          <div class="text-center mb-6">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent">
              Добро пожаловать
            </h1>
            <p class="text-slate-500 mt-2 text-sm">Войдите в свой аккаунт</p>
          </div>

          <!-- Форма -->
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
              <input
                  v-model="form.email"
                  type="email"
                  placeholder="example@mail.ru"
                  class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                  :class="{ 'border-red-500': error && !form.email }"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Пароль</label>
              <input
                  v-model="form.password"
                  type="password"
                  placeholder="••••••••"
                  class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                  :class="{ 'border-red-500': error && !form.password }"
              />
            </div>

            <div v-if="error" class="text-red-500 text-sm text-center">{{ error }}</div>

            <button
                type="submit"
                :disabled="loading"
                class="w-full bg-blue-600 hover:bg-blue-700 transition text-white py-3 rounded-xl font-medium shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <div v-if="loading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              {{ loading ? 'Вход...' : 'Войти' }}
            </button>
          </form>

          <!-- Разделитель -->
          <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-slate-200"></div>
            </div>
            <div class="relative flex justify-center text-xs">
              <span class="bg-white px-3 text-slate-400">или через соцсети</span>
            </div>
          </div>

          <!-- Соцсети (заглушки) -->
          <div class="grid grid-cols-3 gap-3">
            <button
                @click="socialAuth('VK')"
                class="flex items-center justify-center gap-2 border border-slate-200 rounded-xl py-2.5 hover:bg-slate-50 transition"
            >
              <span class="text-blue-600 text-xl">VK</span>
            </button>
            <button
                @click="socialAuth('Google')"
                class="flex items-center justify-center gap-2 border border-slate-200 rounded-xl py-2.5 hover:bg-slate-50 transition"
            >
              <span class="text-red-500 text-xl">G</span>
            </button>
            <button
                @click="socialAuth('Apple')"
                class="flex items-center justify-center gap-2 border border-slate-200 rounded-xl py-2.5 hover:bg-slate-50 transition"
            >
              <span class="text-slate-800 text-xl"></span>
            </button>
          </div>

          <!-- Ссылки -->
          <div class="mt-6 text-center text-sm">
            <router-link to="/forgot-password" class="text-blue-600 hover:underline">Забыли пароль?</router-link>
            <span class="mx-2 text-slate-300">|</span>
            <router-link to="/register" class="text-blue-600 hover:underline">Регистрация</router-link>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
/* Анимация спиннера */
.animate-spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>