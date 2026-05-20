<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import api from '@/api/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'
import MainLayout from "@/layouts/MainLayout.vue";

const route = useRoute()

const loading = ref(true)
const user = ref<any>(null)
const expandedReviews = ref<Set<number>>(new Set())

interface Review {
  id: number
  rating: number
  comment: string
  type: 'passenger_to_driver' | 'driver_to_passenger'
  from_user: {
    id: number
    name: string
    profile?: { avatar_url?: string }
  }
}

const groupedReviews = computed(() => {
  if (!user.value?.reviews) {
    return { asDriver: [], asPassenger: [] }
  }
  return {
    asDriver: user.value.reviews.filter((r: Review) => r.type === 'passenger_to_driver'),
    asPassenger: user.value.reviews.filter((r: Review) => r.type === 'driver_to_passenger')
  }
})

const toggleExpand = (reviewId: number) => {
  if (expandedReviews.value.has(reviewId)) {
    expandedReviews.value.delete(reviewId)
  } else {
    expandedReviews.value.add(reviewId)
  }
}

const renderStars = (rating: number) => {
  const full = '★'.repeat(rating)
  const empty = '☆'.repeat(5 - rating)
  return full + empty
}

const load = async () => {
  loading.value = true
  user.value = null

  try {
    const res = await api.get(`/users/${route.params.id}`)
    user.value = res.data.user
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => route.params.id, () => load())
</script>

<template>
  <MainLayout>
    <div class="max-w-6xl mx-auto px-4 py-8">
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-20">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <div v-else-if="user" class="space-y-10">
        <!-- Hero Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
          <div class="p-6 sm:p-8">
            <div class="flex flex-col lg:flex-row gap-8 items-center lg:items-start">
              <!-- Avatar -->
              <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex-shrink-0 shadow-md">
                <img
                    v-if="user.avatar"
                    :src="user.avatar"
                    class="w-full h-full object-cover"
                    :alt="user.name"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-4xl font-bold text-slate-400">
                  {{ user.name?.charAt(0) || '?' }}
                </div>
              </div>

              <!-- Info -->
              <div class="flex-1 text-center lg:text-left">
                <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent">
                  {{ user.name }}
                </h1>

                <!-- Ratings & Stats -->
                <div class="mt-5 space-y-4">
                  <!-- Driver & Passenger ratings -->
                  <div class="flex flex-wrap gap-3 justify-center lg:justify-start">
                    <!-- Driver rating -->
                    <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 px-4 py-2 rounded-xl font-semibold text-sm sm:text-base">
                      <span>🚘</span>
                      <template v-if="user.ratings?.driver">
                        <span class="text-yellow-600">{{ renderStars(Math.round(user.ratings.driver)) }}</span>
                        <span>{{ user.ratings.driver.toFixed(1) }}</span>
                      </template>
                      <template v-else>
                        <span>Новый водитель</span>
                      </template>
                    </div>
                    <!-- Passenger rating -->
                    <div class="inline-flex items-center gap-2 bg-blue-100 text-blue-800 px-4 py-2 rounded-xl font-semibold text-sm sm:text-base">
                      <span>👤</span>
                      <template v-if="user.ratings?.passenger">
                        <span class="text-yellow-600">{{ renderStars(Math.round(user.ratings.passenger)) }}</span>
                        <span>{{ user.ratings.passenger.toFixed(1) }}</span>
                      </template>
                      <template v-else>
                        <span>Новый пассажир</span>
                      </template>
                    </div>
                  </div>

                  <!-- Trip stats -->
                  <div class="flex flex-wrap gap-3 justify-center lg:justify-start">
                    <div class="bg-slate-100 text-slate-700 px-4 py-2 rounded-xl text-sm sm:text-base">
                      🚘 {{ user.stats?.driver_trips || 0 }} поездок водителем
                    </div>
                    <div class="bg-slate-100 text-slate-700 px-4 py-2 rounded-xl text-sm sm:text-base">
                      ✅ {{ user.stats?.completed_driver_trips || 0 }} завершено
                    </div>
                    <div class="bg-slate-100 text-slate-700 px-4 py-2 rounded-xl text-sm sm:text-base">
                      👤 {{ user.stats?.passenger_trips || 0 }} поездок пассажиром
                    </div>
                  </div>

                  <!-- Reviews count -->
                  <div class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 px-4 py-2 rounded-xl text-sm">
                    📝 {{ user.reviews_count || 0 }}
                    {{
                      user.reviews_count === 1 ? 'отзыв' :
                          user.reviews_count >= 2 && user.reviews_count <= 4 ? 'отзыва' : 'отзывов'
                    }}
                  </div>
                </div>

                <div class="mt-6 text-slate-500 text-sm">
                  На платформе с {{ new Date(user.created_at).toLocaleDateString('ru-RU') }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reviews as Driver -->
        <div v-if="user.has_driver_profile">
          <div class="flex items-center gap-2 mb-5">
            <span class="w-1 h-6 bg-emerald-500 rounded-full"></span>
            <h2 class="text-xl sm:text-2xl font-semibold text-slate-900">Отзывы как водителю</h2>
          </div>
          <div v-if="groupedReviews.asDriver.length === 0" class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-500">
            🚗 Пока нет отзывов от пассажиров
          </div>
          <div v-else class="space-y-4">
            <div v-for="review in groupedReviews.asDriver" :key="review.id" class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
              <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                <!-- Avatar -->
                <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex-shrink-0">
                  <img
                      v-if="review.from_user?.profile?.avatar_url"
                      :src="review.from_user.profile.avatar_url"
                      class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-base font-medium text-slate-500">
                    {{ review.from_user.name?.charAt(0) }}
                  </div>
                </div>
                <div class="flex-1">
                  <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                      <RouterLink
                          :to="`/users/${review.from_user.id}`"
                          class="font-bold text-slate-900 hover:text-blue-600 transition"
                      >
                        {{ review.from_user.name }}
                      </RouterLink>
                      <div class="text-xs text-slate-500">Пассажир</div>
                    </div>
                    <div class="text-yellow-500 text-base font-medium whitespace-nowrap">
                      {{ renderStars(review.rating) }}
                    </div>
                  </div>
                  <div v-if="review.comment" class="mt-3 text-slate-700 leading-relaxed text-sm sm:text-base">
                    <p class="whitespace-pre-wrap break-words">
                      <template v-if="review.comment.length > 200">
                        <span v-if="!expandedReviews.has(review.id)">{{ review.comment.slice(0, 200) }}…</span>
                        <span v-else>{{ review.comment }}</span>
                        <button @click="toggleExpand(review.id)" class="text-blue-600 text-sm font-medium ml-1 hover:text-blue-700">
                          {{ expandedReviews.has(review.id) ? 'Свернуть' : 'Читать далее' }}
                        </button>
                      </template>
                      <span v-else>{{ review.comment }}</span>
                    </p>
                  </div>
                  <div v-else class="mt-2 text-slate-400 text-sm italic">Без комментария</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reviews as Passenger (always shown) -->
        <div class="mb-10">
          <div class="flex items-center gap-2 mb-5">
            <span class="w-1 h-6 bg-sky-500 rounded-full"></span>
            <h2 class="text-xl sm:text-2xl font-semibold text-slate-900">Отзывы как пассажиру</h2>
          </div>
          <div v-if="groupedReviews.asPassenger.length === 0" class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-500">
            👤 Пока нет отзывов от водителей
          </div>
          <div v-else class="space-y-4">
            <div v-for="review in groupedReviews.asPassenger" :key="review.id" class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
              <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                <!-- Avatar -->
                <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex-shrink-0">
                  <img
                      v-if="review.from_user?.profile?.avatar_url"
                      :src="review.from_user.profile.avatar_url"
                      class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-base font-medium text-slate-500">
                    {{ review.from_user.name?.charAt(0) }}
                  </div>
                </div>
                <div class="flex-1">
                  <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                      <RouterLink
                          :to="`/users/${review.from_user.id}`"
                          class="font-bold text-slate-900 hover:text-blue-600 transition"
                      >
                        {{ review.from_user.name }}
                      </RouterLink>
                      <div class="text-xs text-slate-500">Водитель</div>
                    </div>
                    <div class="text-yellow-500 text-base font-medium whitespace-nowrap">
                      {{ renderStars(review.rating) }}
                    </div>
                  </div>
                  <div v-if="review.comment" class="mt-3 text-slate-700 leading-relaxed text-sm sm:text-base">
                    <p class="whitespace-pre-wrap break-words">
                      <template v-if="review.comment.length > 200">
                        <span v-if="!expandedReviews.has(review.id)">{{ review.comment.slice(0, 200) }}…</span>
                        <span v-else>{{ review.comment }}</span>
                        <button @click="toggleExpand(review.id)" class="text-blue-600 text-sm font-medium ml-1 hover:text-blue-700">
                          {{ expandedReviews.has(review.id) ? 'Свернуть' : 'Читать далее' }}
                        </button>
                      </template>
                      <span v-else>{{ review.comment }}</span>
                    </p>
                  </div>
                  <div v-else class="mt-2 text-slate-400 text-sm italic">Без комментария</div>
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
button {
  transition: all 0.2s ease;
}
/* Мобильные отступы */
@media (max-width: 640px) {
  .p-6 {
    padding: 1.25rem;
  }
}
</style>