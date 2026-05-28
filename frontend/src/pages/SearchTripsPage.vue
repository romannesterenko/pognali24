<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/api/axios'
import MainLayout from '@/layouts/MainLayout.vue'
import { getUserRating, getReviewsCount } from '@/utils/userRating'
import { useRouter } from 'vue-router'
import CityAutocomplete from "@/components/CityAutocomplete.vue";
import  { useHead } from '@vueuse/head'

useHead({
  title: 'Поиск попутчиков и поездок по России и СНГ — Pognali24',

  meta: [
    {
      name: 'description',
      content:
          'Поиск. Найдите попутную поездку или водителя за пару минут. Удобный сервис совместных поездок Pognali24 — быстро, безопасно и без переплат.',
    },

    {
      name: 'keywords',
      content:
          'поиск, поездки, попутчики, попутка, поездки Россия, поездки СНГ, найти водителя',
    },

    // OpenGraph
    {
      property: 'og:title',
      content: 'Поиск попутчиков и поездок — Pognali24',
    },
    {
      property: 'og:description',
      content:
          'Поиск. Найдите попутную поездку или пассажира быстро и безопасно',
    },
    {
      property: 'og:type',
      content: 'website',
    },
    {
      property: 'og:url',
      content: 'https://pognali-24.ru/search',
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
      href: 'https://pognali-24.ru/search',
    },
  ],
})
const router = useRouter()
const trips = ref<any[]>([])
const loading = ref(false)

const filters = ref({
  from_city: '',
  to_city: '',
  date: '',
})

// Group trips by date
const groupedTrips = computed(() => {
  const groups: Record<string, any[]> = {}
  for (const trip of trips.value) {
    const dateKey = new Date(trip.departure_time).toLocaleDateString('ru-RU', {
      day: 'numeric',
      month: 'long',
      weekday: 'long'
    })
    if (!groups[dateKey]) groups[dateKey] = []
    groups[dateKey].push(trip)
  }
  return groups
})

const formatTime = (dateStr: string) => {
  return new Date(dateStr).toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const renderStars = (rating: number) => {
  const numRating = Number(rating) || 0
  const full = '★'.repeat(Math.floor(numRating))
  const half = numRating % 1 >= 0.5 ? '½' : ''
  const empty = '☆'.repeat(5 - Math.ceil(numRating))
  return (full + half + empty).slice(0, 5)
}

// Безопасное получение рейтинга числом
const safeRating = (driver: any) => {
  const rating = getUserRating(driver)
  const num = Number(rating)
  return isNaN(num) ? 0 : num
}

const search = async () => {
  loading.value = true
  try {
    const res = await api.get('/trips/search', { params: filters.value })
    trips.value = res.data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.value = { from_city: '', to_city: '', date: '' }
  search()
}

onMounted(search)
</script>

<template>
  <MainLayout>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Search Form -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent mb-6">
          Поиск поездок
        </h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">📍</span>
            <CityAutocomplete
                v-model="filters.from_city"
                placeholder="Откуда"
                input-class="w-full border border-slate-200 rounded-xl p-3 pl-10 focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🏁</span>
            <CityAutocomplete
                v-model="filters.to_city"
                placeholder="Куда"
                input-class="w-full border border-slate-200 rounded-xl p-3 pl-10 focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">📅</span>
            <input
                v-model="filters.date"
                type="date"
                class="w-full border border-slate-200 rounded-xl p-3 pl-10 focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="flex gap-3">
            <button
                @click="search"
                class="flex-1 bg-blue-600 hover:bg-blue-700 transition text-white rounded-xl font-medium shadow-sm"
            >
              🔍 Найти
            </button>
            <button
                v-if="filters.from_city || filters.to_city || filters.date"
                @click="clearFilters"
                class="px-4 border border-slate-300 rounded-xl hover:bg-slate-50 transition"
                title="Сбросить фильтры"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-20">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <!-- No results -->
      <div v-else-if="trips.length === 0" class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="text-6xl mb-4">🚗💨</div>
        <h3 class="text-xl font-semibold text-slate-900 mb-2">Поездки не найдены</h3>
        <p class="text-slate-500">Попробуйте изменить направление или дату</p>
      </div>

      <!-- Results grouped by date -->
      <div v-else class="space-y-8">
        <div v-for="(group, dateLabel) in groupedTrips" :key="dateLabel" class="space-y-3">
          <!-- Date header -->
          <div class="flex items-center gap-2 sticky top-0 bg-white/90 backdrop-blur-sm py-2 z-10">
            <span class="w-1 h-5 bg-blue-500 rounded-full"></span>
            <h2 class="text-base font-semibold text-slate-700">{{ dateLabel }}</h2>
          </div>

          <!-- Trip cards -->
          <div class="space-y-4">
            <div
                v-for="trip in group"
                :key="trip.id"
                class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition-all duration-200"
            >
              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <!-- Left side -->
                <div class="flex-1">
                  <!-- Route -->
                  <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">

                    <!-- FROM -->
                    <div class="min-w-0 flex-1">

                      <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 flex-shrink-0"></div>

                        <div
                            class="text-xl sm:text-2xl font-bold text-slate-900 truncate"
                        >
                          {{ trip.from_locality.name }}
                        </div>
                      </div>

                      <div
                          class="text-sm sm:text-base text-slate-500 mt-1 pl-5 break-words"
                      >
                        {{ trip.from_locality.region.name }}
                        {{ trip.from_locality.region.type }}
                      </div>

                    </div>

                    <!-- ARROW -->
                    <div
                        class="flex justify-center items-center text-slate-400 text-2xl sm:text-3xl rotate-90 sm:rotate-0"
                    >
                      →
                    </div>

                    <!-- TO -->
                    <div class="min-w-0 flex-1">

                      <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-rose-500 flex-shrink-0"></div>

                        <div
                            class="text-xl sm:text-2xl font-bold text-slate-900 truncate"
                        >
                          {{ trip.to_locality.name }}
                        </div>
                      </div>

                      <div
                          class="text-sm sm:text-base text-slate-500 mt-1 pl-5 break-words"
                      >
                        {{ trip.to_locality.region.name }}
                        {{ trip.to_locality.region.type }}
                      </div>

                    </div>

                  </div>

                  <!-- Trip meta -->
                  <div class="mt-3 flex flex-wrap gap-4 text-sm text-slate-600">
                    <span class="flex items-center gap-1">🚗 {{ trip.car.brand }} {{ trip.car.model }}</span>
                    <span class="flex items-center gap-1">👥 {{ trip.available_seats }} мест</span>
                    <span class="flex items-center gap-1">⏰ {{ formatTime(trip.departure_time) }}</span>
                  </div>

                  <!-- Driver info -->
                  <div class="mt-4 flex items-center gap-3 pt-2">
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex-shrink-0">
                      <img
                          v-if="trip.driver.profile?.avatar_url"
                          :src="trip.driver.profile.avatar_url"
                          class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center text-slate-500 text-lg">
                        {{ trip.driver.name?.charAt(0) }}
                      </div>
                    </div>
                    <div>
                      <RouterLink
                          :to="`/users/${trip.driver.id}`"
                          class="font-semibold text-slate-900 hover:text-blue-600 transition"
                      >
                        {{ trip.driver.name }}
                      </RouterLink>
                      <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                        <template v-if="getUserRating(trip.driver)">
                          <span class="text-yellow-500 text-sm">{{ renderStars(safeRating(trip.driver)) }}</span>
                          <span class="text-xs text-slate-500">{{ safeRating(trip.driver).toFixed(1) }}</span>
                          <span class="text-xs text-slate-400">•</span>
                          <span class="text-xs text-slate-500">{{ getReviewsCount(trip.driver) }} {{
                              getReviewsCount(trip.driver) === 1 ? 'отзыв' :
                                  getReviewsCount(trip.driver) >= 2 && getReviewsCount(trip.driver) <= 4 ? 'отзыва' : 'отзывов'
                            }}</span>
                        </template>
                        <span v-else class="text-xs text-slate-400">Новый пользователь</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right side: price & button -->
                <div class="lg:text-right flex flex-row lg:flex-col items-center justify-between lg:justify-start gap-4 lg:gap-2">
                  <div>
                    <div class="text-2xl font-bold text-emerald-600">{{ trip.price }} ₽</div>
                    <div class="text-xs text-slate-400">за место</div>
                  </div>
                  <button
                      @click="router.push(`/trips/${trip.id}`)"
                      class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium transition shadow-sm whitespace-nowrap"
                  >
                    Подробнее →
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.whitespace-pre-wrap {
  white-space: pre-wrap;
  word-break: break-word;
}
button, .transition {
  transition: all 0.2s ease;
}
.sticky {
  position: sticky;
  top: 0;
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(4px);
  z-index: 10;
}
</style>