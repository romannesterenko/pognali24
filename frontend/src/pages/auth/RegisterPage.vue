<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const loading = ref(false)
const error = ref('')

// Проверка совпадения паролей
const passwordsMatch = computed(() => {
  return (
      form.password &&
      form.password_confirmation &&
      form.password === form.password_confirmation
  )
})

// Заглушки соцсетей
const socialAuth = (provider: string) => {
  alert(`Регистрация через ${provider} в разработке`)
}

const submit = async () => {

  error.value = ''

  if (
      !form.name.trim() ||
      !form.email.trim() ||
      !form.password.trim() ||
      !form.password_confirmation.trim()
  ) {
    error.value = 'Заполните все поля'
    return
  }

  if (form.password.length < 6) {
    error.value = 'Пароль должен содержать минимум 6 символов'
    return
  }

  if (form.password !== form.password_confirmation) {
    error.value = 'Пароли не совпадают'
    return
  }

  loading.value = true

  try {

    await auth.register(form)

    router.push('/')

  } catch (e: any) {

    error.value =
        e.response?.data?.message ||
        'Ошибка регистрации'

  } finally {

    loading.value = false

  }
}
</script>

<template>
  <MainLayout>

    <div class="min-h-[80vh] flex items-center justify-center px-4 py-12">

      <div class="w-full max-w-md">

        <!-- CARD -->

        <div
            class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6 sm:p-8"
        >

          <!-- HEADER -->

          <div class="text-center mb-6">

            <h1
                class="text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent"
            >
              Создать аккаунт
            </h1>

            <p class="text-slate-500 mt-2 text-sm">
              Присоединяйтесь к Pognali24
            </p>

          </div>

          <!-- FORM -->

          <form
              @submit.prevent="submit"
              class="space-y-4"
          >

            <!-- NAME -->

            <div>

              <label
                  class="block text-sm font-medium text-slate-700 mb-1"
              >
                Имя
              </label>

              <input
                  v-model="form.name"
                  type="text"
                  placeholder="Иван Иванов"
                  class="w-full border rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                  :class="{
                  'border-red-500': error && !form.name,
                  'border-slate-200': !(error && !form.name)
                }"
              />

            </div>

            <!-- EMAIL -->

            <div>

              <label
                  class="block text-sm font-medium text-slate-700 mb-1"
              >
                Email
              </label>

              <input
                  v-model="form.email"
                  type="email"
                  placeholder="example@mail.ru"
                  class="w-full border rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                  :class="{
                  'border-red-500': error && !form.email,
                  'border-slate-200': !(error && !form.email)
                }"
              />

            </div>

            <!-- PASSWORD -->

            <div>

              <label
                  class="block text-sm font-medium text-slate-700 mb-1"
              >
                Пароль
              </label>

              <input
                  v-model="form.password"
                  type="password"
                  placeholder="Минимум 6 символов"
                  class="w-full border rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                  :class="{
                  'border-red-500': error && !form.password,
                  'border-slate-200': !(error && !form.password)
                }"
              />

            </div>

            <!-- PASSWORD CONFIRM -->

            <div>

              <label
                  class="block text-sm font-medium text-slate-700 mb-1"
              >
                Повторите пароль
              </label>

              <input
                  v-model="form.password_confirmation"
                  type="password"
                  placeholder="Повторите пароль"
                  class="w-full border rounded-xl p-3 focus:outline-none focus:ring-2 transition"
                  :class="[
                  form.password_confirmation
                    ? passwordsMatch
                      ? 'border-green-500 focus:ring-green-500'
                      : 'border-red-500 focus:ring-red-500'
                    : 'border-slate-200 focus:ring-blue-500'
                ]"
              />

              <!-- LIVE STATUS -->

              <div
                  v-if="form.password_confirmation"
                  class="mt-2 text-sm"
              >

                <span
                    v-if="passwordsMatch"
                    class="text-green-600"
                >
                  ✓ Пароли совпадают
                </span>

                <span
                    v-else
                    class="text-red-500"
                >
                  Пароли не совпадают
                </span>

              </div>

            </div>

            <!-- ERROR -->

            <div
                v-if="error"
                class="text-red-500 text-sm text-center"
            >
              {{ error }}
            </div>

            <!-- SUBMIT -->

            <button
                type="submit"
                :disabled="loading || !passwordsMatch"
                class="w-full bg-blue-600 hover:bg-blue-700 transition text-white py-3 rounded-xl font-medium shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >

              <div
                  v-if="loading"
                  class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"
              />

              {{
                loading
                    ? 'Регистрация...'
                    : 'Зарегистрироваться'
              }}

            </button>

          </form>

          <!-- DIVIDER -->

          <div class="relative my-6">

            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-slate-200"></div>
            </div>

            <div class="relative flex justify-center text-xs">
              <span class="bg-white px-3 text-slate-400">
                или через соцсети
              </span>
            </div>

          </div>

          <!-- SOCIAL -->

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

          <!-- LOGIN LINK -->

          <div class="mt-6 text-center text-sm">

            <span class="text-slate-500">
              Уже есть аккаунт?
            </span>

            <router-link
                to="/login"
                class="ml-1 text-blue-600 hover:underline"
            >
              Войти
            </router-link>

          </div>

        </div>

      </div>

    </div>

  </MainLayout>
</template>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}
</style>