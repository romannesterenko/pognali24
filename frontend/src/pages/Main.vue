<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import MainLayout from '@/layouts/MainLayout.vue'
import  { useHead } from '@vueuse/head'

useHead({
  title: 'Попутчики и поездки по России и СНГ — Pognali24',

  meta: [
    {
      name: 'description',
      content:
          'Найдите попутную поездку или водителя за пару минут. Удобный сервис совместных поездок Pognali24 — быстро, безопасно и без переплат.',
    },

    {
      name: 'keywords',
      content:
          'поездки, попутчики, блаблакар, попутка, поездки Россия, поездки СНГ, найти водителя',
    },

    // OpenGraph
    {
      property: 'og:title',
      content: 'Попутчики и поездки — Pognali24',
    },
    {
      property: 'og:description',
      content:
          'Найдите попутную поездку или пассажира быстро и безопасно',
    },
    {
      property: 'og:type',
      content: 'website',
    },
    {
      property: 'og:url',
      content: 'https://pognali-24.ru',
    },
    {
      property: 'og:image',
      content: 'https://pognali-24.ru/og/home.jpg',
    },

    // Twitter
    {
      name: 'twitter:card',
      content: 'summary_large_image',
    },
  ],

  link: [
    {
      rel: 'canonical',
      href: 'https://pognali-24.ru',
    },
  ],
})

const router = useRouter()

const trips = ref<any[]>([])
const loading = ref(true)

const search = ref({
  from_city: '',
  to_city: '',
  date: '',
})

// Форматирование даты (краткое для карточек)
const formatShortDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (date >= today) {
    return `Сегодня в ${date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`
  }
  if (date >= yesterday) {
    return `Вчера в ${date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`
  }
  return date.toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Форматирование полной даты (для hero — не используется, но оставим)
const formatFullDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Рейтинг звёздами для водителя
const renderStars = (rating: number) => {
  const num = Number(rating) || 0
  const full = '★'.repeat(Math.floor(num))
  const half = num % 1 >= 0.5 ? '½' : ''
  const empty = '☆'.repeat(5 - Math.ceil(num))
  return (full + half + empty).slice(0, 5)
}

const getDriverRating = (driver: any) => {
  const rating = driver?.driver_profile?.rating || 0
  return Number(rating) || 0
}

const loadTrips = async () => {
  try {
    const res = await api.get('/trips/latest')
    trips.value = res.data.trips || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const goSearch = () => {
  const params = new URLSearchParams()
  if (search.value.from_city) params.append('from', search.value.from_city)
  if (search.value.to_city) params.append('to', search.value.to_city)
  if (search.value.date) params.append('date', search.value.date)
  router.push(`/search?${params.toString()}`)
}

onMounted(loadTrips)
</script>


<template>
  <MainLayout>


      <div class="min-h-screen bg-white">
        <!-- HERO -->
        <section class="relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-slate-100"></div>
          <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-4 flex justify-center">
            <div class="max-w-4xl">
              <h1 class="text-4xl sm:text-5xl lg:text-7xl font-black leading-tight text-slate-900">
                Поездки по
                <span class="text-blue-600">России</span>
              </h1>
              <p class="mt-6 text-lg sm:text-xl text-slate-600 leading-relaxed max-w-2xl">
                Находите попутчиков, бронируйте поездки и путешествуйте
                дешевле, быстрее и удобнее.
              </p>

              <!-- Search form -->
              <div class="mt-10 bg-white rounded-2xl shadow-xl border border-slate-200 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                  <input
                      v-model="search.from_city"
                      placeholder="Откуда"
                      class="h-12 rounded-xl border border-slate-200 px-4 outline-none focus:ring-2 focus:ring-blue-500 transition"
                  />
                  <input
                      v-model="search.to_city"
                      placeholder="Куда"
                      class="h-12 rounded-xl border border-slate-200 px-4 outline-none focus:ring-2 focus:ring-blue-500 transition"
                  />
                  <input
                      v-model="search.date"
                      type="date"
                      class="h-12 rounded-xl border border-slate-200 px-4 outline-none focus:ring-2 focus:ring-blue-500 transition"
                  />
                  <button
                      @click="goSearch"
                      class="h-12 bg-blue-600 hover:bg-blue-700 transition text-white rounded-xl font-semibold shadow-sm"
                  >
                    Найти поездку
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- FEATURES -->
        <section class="py-16 sm:py-24">
          <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
              <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">Почему Pognali24?</h2>
              <p class="mt-3 text-slate-500 text-lg">Всё что нужно для комфортных поездок</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-lg transition-all duration-200">
                <div class="text-4xl mb-4">🔒</div>
                <div class="text-xl font-bold text-slate-900">Безопасно</div>
                <p class="mt-2 text-slate-500 leading-relaxed">Рейтинги, отзывы и подтверждение поездок.</p>
              </div>
              <div class="bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-lg transition-all duration-200">
                <div class="text-4xl mb-4">💬</div>
                <div class="text-xl font-bold text-slate-900">Удобный чат</div>
                <p class="mt-2 text-slate-500 leading-relaxed">Общайтесь с водителем прямо внутри платформы.</p>
              </div>
              <div class="bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-lg transition-all duration-200">
                <div class="text-4xl mb-4">💰</div>
                <div class="text-xl font-bold text-slate-900">Выгодно</div>
                <p class="mt-2 text-slate-500 leading-relaxed">Поездки дешевле автобусов и такси.</p>
              </div>
              <div class="bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-lg transition-all duration-200">
                <div class="text-4xl mb-4">⚡</div>
                <div class="text-xl font-bold text-slate-900">Быстро</div>
                <p class="mt-2 text-slate-500 leading-relaxed">Найдите поездку за пару минут.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- LATEST TRIPS -->
        <section class="py-16 sm:py-24 bg-slate-50">
          <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-10">
              <div>
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">Последние поездки</h2>
                <p class="mt-2 text-slate-500">Свежие предложения от водителей</p>
              </div>
              <button
                  @click="router.push('/search')"
                  class="hidden lg:flex items-center gap-2 border border-slate-300 hover:bg-white transition px-5 py-2.5 rounded-xl text-slate-700 font-medium"
              >
                Смотреть все →
              </button>
            </div>

            <!-- Skeleton loaders -->
            <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div v-for="i in 4" :key="i" class="bg-white rounded-2xl h-64 animate-pulse"></div>
            </div>

            <!-- Trips grid -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div
                  v-for="trip in  trips"
                  :key="trip.id"
                  @click="router.push(`/trips/${trip.id}`)"
                  class="bg-white border border-slate-200 rounded-2xl p-5 cursor-pointer hover:shadow-md transition-all duration-200 group"
              >
                <div class="flex items-center justify-between flex-wrap gap-2">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xl font-bold text-slate-900">{{ trip.from_city }}</span>
                    <span class="text-slate-400">→</span>
                    <span class="text-xl font-bold text-slate-900">{{ trip.to_city }}</span>
                  </div>
                  <div class="bg-blue-100 text-blue-700 px-3 py-1 rounded-xl text-sm font-medium">
                    {{ trip.price }} ₽
                  </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-4 text-slate-500 text-sm">
                  <span class="flex items-center gap-1">🚗 {{ trip.car.brand }} {{ trip.car.model }}</span>
                  <span class="flex items-center gap-1">💺 {{ trip.available_seats }} мест</span>
                  <span class="flex items-center gap-1">📅 {{ formatShortDate(trip.departure_time) }}</span>
                </div>

                <!-- Driver info with rating -->
                <div class="mt-5 flex items-center gap-3 pt-2 border-t border-slate-100">
                  <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex-shrink-0">
                    <img
                        v-if="trip.driver.profile?.avatar_url"
                        :src="trip.driver.profile.avatar_url"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-base font-medium text-slate-500">
                      {{ trip.driver.name?.charAt(0) || '?' }}
                    </div>
                  </div>
                  <div>
                    <div class="font-semibold text-slate-900 group-hover:text-blue-600 transition">{{ trip.driver.name }}</div>
                    <div class="flex items-center gap-1 mt-0.5">
                      <span class="text-yellow-500 text-xs">{{ renderStars(getDriverRating(trip.driver)) }}</span>
                      <span class="text-xs text-slate-500">{{ getDriverRating(trip.driver).toFixed(1) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mobile "Show all" button -->
            <div class="mt-8 text-center lg:hidden">
              <button
                  @click="router.push('/search')"
                  class="border border-slate-300 hover:bg-white px-6 py-3 rounded-xl text-slate-700 font-medium transition"
              >
                Смотреть все поездки
              </button>
            </div>
          </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="py-16 sm:py-24">
          <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
              <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">Как это работает</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
              <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-blue-600 text-white flex items-center justify-center text-2xl font-bold">1</div>
                <div class="mt-4 text-xl font-bold text-slate-900">Найдите поездку</div>
                <p class="mt-2 text-slate-500">Укажите маршрут и дату</p>
              </div>
              <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-blue-600 text-white flex items-center justify-center text-2xl font-bold">2</div>
                <div class="mt-4 text-xl font-bold text-slate-900">Отправьте заявку</div>
                <p class="mt-2 text-slate-500">Водитель подтвердит поездку</p>
              </div>
              <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-blue-600 text-white flex items-center justify-center text-2xl font-bold">3</div>
                <div class="mt-4 text-xl font-bold text-slate-900">Общайтесь</div>
                <p class="mt-2 text-slate-500">Используйте встроенный чат</p>
              </div>
              <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-blue-600 text-white flex items-center justify-center text-2xl font-bold">4</div>
                <div class="mt-4 text-xl font-bold text-slate-900">Поехали</div>
                <p class="mt-2 text-slate-500">Путешествуйте выгодно</p>
              </div>
            </div>
          </div>
        </section>

        <!-- CTA -->
        <section class="py-16 sm:py-24">
          <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-8 sm:p-12 text-center text-white shadow-xl">
              <h2 class="text-3xl sm:text-4xl font-black">Станьте водителем на Pognali24</h2>
              <p class="mt-4 text-blue-100 text-lg">Публикуйте поездки и находите попутчиков</p>
              <button
                  @click="router.push('/driver/trips')"
                  class="mt-6 bg-white text-blue-700 hover:bg-blue-50 transition px-6 py-3 rounded-xl font-bold text-lg shadow-md"
              >
                Создать поездку
              </button>
            </div>
          </div>
        </section>

        <!-- FOOTER -->
        <footer class="border-t border-slate-200 py-8 mt-8">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="text-slate-500 text-sm">© 2026 Pognali24</div>
            <div class="flex items-center gap-6 text-sm text-slate-500">
              <a href="#" class="hover:text-slate-900 transition">Правила</a>
              <a href="#" class="hover:text-slate-900 transition">Политика</a>
              <a href="#" class="hover:text-slate-900 transition">Контакты</a>
            </div>
          </div>
        </footer>
      </div>


  </MainLayout>
</template>


<style scoped>
.animate-pulse {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>