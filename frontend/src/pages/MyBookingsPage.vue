<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue'
import api from '@/api/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'
import {getUserRating, getReviewsCount} from '@/utils/userRating'
import { useRouter } from 'vue-router'
import  { useHead } from '@vueuse/head'

useHead({
  title: 'Мои бронирования — Pognali24',

  meta: [
    {
      name: 'description',
      content:
          'Мои бронирования. Найдите попутную поездку или водителя за пару минут. Удобный сервис совместных поездок Pognali24 — быстро, безопасно и без переплат.',
    },

    {
      name: 'keywords',
      content:
          'Мои бронирования, поиск, поездки, попутчики, попутка, поездки Россия, поездки СНГ, найти водителя',
    },

    // OpenGraph
    {
      property: 'og:title',
      content: 'Мои бронирования. Поиск попутчиков и поездок — Pognali24',
    },
    {
      property: 'og:description',
      content:
          'Мои бронирования. Найдите попутную поездку или пассажира быстро и безопасно',
    },
    {
      property: 'og:type',
      content: 'website',
    },
    {
      property: 'og:url',
      content: 'https://pognali-24.ru/bookings',
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
      href: 'https://pognali-24.ru/bookings',
    },
  ],
})
const router = useRouter()

interface Booking {
  id: number
  status: 'pending' | 'approved' | 'rejected' | 'cancelled' | 'completed'
  passenger_confirmed_at: string | null
  trip: {
    id: number
    from_city: string
    to_city: string
    departure_time: string
    price: number
    status: string
    car: {
      brand: string
      model: string
    }
    driver: {
      name: string
      profile?: {
        avatar_url?: string
      }
    }
  }
  reviews?: Array<{
    rating: number
    comment: string
  }>
}

interface ReviewForm {
  rating: number
  comment: string
}

const bookings = ref<Booking[]>([])
const loading = ref(false)

const confirmingBookingId = ref<number | null>(null)
const reviewLoadingId = ref<number | null>(null)

const reviewForms = reactive<Record<number, ReviewForm>>({})
const openedReviewForms = ref<Set<number>>(new Set())

const expandedReviews = ref<Set<number>>(new Set())

const editingReviews = ref<Set<number>>(new Set())
const editReviewData = reactive<Record<number, ReviewForm>>({})

const activeBookings = computed(() =>
    bookings.value.filter(b => ['pending', 'approved'].includes(b.status))
)

const pastBookings = computed(() =>
    bookings.value.filter(b =>
        ['rejected', 'cancelled', 'completed'].includes(b.status)
    )
)

const getStatusBadge = (status: string) => {
  const map: Record<string, { class: string; text: string }> = {
    pending: {
      class: 'bg-amber-100 text-amber-700',
      text: '⏳ Ожидает подтверждения'
    },
    approved: {
      class: 'bg-emerald-100 text-emerald-700',
      text: '✅ Подтверждено'
    },
    rejected: {
      class: 'bg-rose-100 text-rose-700',
      text: '❌ Отклонено'
    },
    cancelled: {
      class: 'bg-slate-100 text-slate-700',
      text: '🚫 Отменено'
    },
    completed: {
      class: 'bg-sky-100 text-sky-700',
      text: '🏁 Завершено'
    }
  }

  return map[status] || map.pending
}

const toggleReviewForm = (bookingId: number) => {
  if (openedReviewForms.value.has(bookingId)) {
    openedReviewForms.value.delete(bookingId)
    delete reviewForms[bookingId]
  } else {
    openedReviewForms.value.add(bookingId)

    if (!reviewForms[bookingId]) {
      reviewForms[bookingId] = {
        rating: 0,
        comment: ''
      }
    }
  }
}

const setRating = (bookingId: number, rating: number) => {
  if (!reviewForms[bookingId]) {
    reviewForms[bookingId] = {
      rating: 0,
      comment: ''
    }
  }

  reviewForms[bookingId].rating = rating
}

const updateComment = (bookingId: number, comment: string) => {
  if (!reviewForms[bookingId]) {
    reviewForms[bookingId] = {
      rating: 0,
      comment: ''
    }
  }

  reviewForms[bookingId].comment = comment
}

const submitReview = async (bookingId: number) => {
  const form = reviewForms[bookingId]

  if (!form?.rating || form.rating === 0) {
    alert('Пожалуйста, поставьте оценку')
    return
  }

  reviewLoadingId.value = bookingId

  try {
    await api.post(`/bookings/${bookingId}/reviews`, {
      rating: form.rating,
      comment: form.comment?.trim() || null
    })

    await load()

    toggleReviewForm(bookingId)

    alert('Спасибо за отзыв!')
  } catch (e: any) {
    alert(e.response?.data?.message || 'Ошибка при отправке')
  } finally {
    reviewLoadingId.value = null
  }
}

const toggleExpandReview = (bookingId: number) => {
  if (expandedReviews.value.has(bookingId)) {
    expandedReviews.value.delete(bookingId)
  } else {
    expandedReviews.value.add(bookingId)
  }
}

const toggleEditReview = (bookingId: number) => {
  const booking = bookings.value.find(b => b.id === bookingId)

  if (!booking?.reviews?.length) return

  if (editingReviews.value.has(bookingId)) {
    editingReviews.value.delete(bookingId)
    delete editReviewData[bookingId]
  } else {
    editingReviews.value.add(bookingId)

    editReviewData[bookingId] = {
      rating: booking.reviews[0].rating,
      comment: booking.reviews[0].comment || ''
    }
  }
}

const updateReview = async (bookingId: number) => {
  const data = editReviewData[bookingId]

  if (!data) return

  try {
    await api.put(`/bookings/${bookingId}/reviews`, {
      rating: data.rating,
      comment: data.comment
    })

    await load()

    editingReviews.value.delete(bookingId)

    alert('Отзыв обновлён')
  } catch (e: any) {
    alert(e.response?.data?.message || 'Ошибка при обновлении')
  }
}

const deleteReview = async (bookingId: number) => {
  if (!confirm('Удалить отзыв?')) return

  try {
    await api.delete(`/bookings/${bookingId}/reviews`)

    await load()

    alert('Отзыв удалён')
  } catch (e: any) {
    alert(e.response?.data?.message || 'Ошибка при удалении')
  }
}

const cancelBooking = async (bookingId: number) => {
  if (!confirm('Отменить заявку?')) return

  try {
    await api.post(`/bookings/${bookingId}/cancel`)

    await load()

    alert('Заявка отменена')
  } catch (e: any) {
    alert(e.response?.data?.message || 'Ошибка')
  }
}

const confirmBooking = async (bookingId: number) => {
  confirmingBookingId.value = bookingId

  try {
    await api.post(`/bookings/${bookingId}/confirm`)

    await load()

    alert('Поездка подтверждена')
  } catch (e: any) {
    alert(e.response?.data?.message || 'Ошибка')
  } finally {
    confirmingBookingId.value = null
  }
}

const load = async () => {
  loading.value = true

  try {
    const { data } = await api.get('/bookings')

    bookings.value = data.bookings || []
  } catch (e: any) {
    console.error(e)

    alert('Не удалось загрузить бронирования')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <AuthLayout>
    <div class="max-w-6xl mx-auto px-4 py-8">
      <!-- HEADER -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">
          Мои бронирования
        </h1>

        <p class="text-slate-500 mt-2">
          Управляйте бронированиями и оставляйте отзывы
        </p>
      </div>

      <!-- LOADING -->
      <div v-if="loading" class="flex justify-center py-20">
        <div class="text-center">
          <div
              class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"
          ></div>

          <p class="mt-4 text-slate-600">
            Загрузка...
          </p>
        </div>
      </div>

      <!-- EMPTY -->
      <div
          v-else-if="bookings.length === 0"
          class="bg-white rounded-2xl border border-slate-200 p-12 text-center"
      >
        <div class="text-6xl mb-4">
          🚗
        </div>

        <h3 class="text-xl font-semibold mb-2">
          Пока нет бронирований
        </h3>

        <p class="text-slate-500 mb-6">
          Найдите поездку и отправьте заявку водителю
        </p>

        <button
            @click="router.push('/search')"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-medium shadow-md transition"
        >
          🔍 Найти поездку
        </button>
      </div>

      <!-- CONTENT -->
      <div v-else class="space-y-8">

        <!-- ACTIVE -->
        <div v-if="activeBookings.length">
          <div class="flex items-center gap-2 mb-4">
            <span class="w-1 h-6 bg-blue-600 rounded-full"></span>

            <h2 class="text-lg font-semibold text-slate-900">
              Активные поездки
            </h2>
          </div>

          <div class="space-y-4">
            <div
                v-for="booking in activeBookings"
                :key="booking.id"
                class="bg-white rounded-2xl border border-slate-200 p-6 hover:shadow-md transition"
            >
              <div
                  class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-6 items-stretch"
              >
                <!-- LEFT -->
                <div class="flex flex-col">
                  <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">

                    <!-- FROM -->
                    <div class="min-w-0 flex-1">

                      <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 flex-shrink-0"></div>

                        <div
                            class="text-xl sm:text-2xl font-bold text-slate-900 truncate"
                        >
                          {{ booking.trip.from_locality.type }}. {{ booking.trip.from_locality.name }}
                        </div>
                      </div>

                      <div
                          class="text-sm sm:text-base text-slate-500 mt-1 pl-5 break-words"
                      >
                        {{ booking.trip.from_locality.region.name }}
                        {{ booking.trip.from_locality.region.type }}
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
                          {{ booking.trip.to_locality.type }}. {{ booking.trip.to_locality.name }}
                        </div>
                      </div>

                      <div
                          class="text-sm sm:text-base text-slate-500 mt-1 pl-5 break-words"
                      >
                        {{ booking.trip.to_locality.region.name }}
                        {{ booking.trip.to_locality.region.type }}
                      </div>

                    </div>

                  </div>

                  <div
                      class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600"
                  >
                    <div class="flex items-center gap-2">
                      🚗 {{ booking.trip.car.brand }} {{ booking.trip.car.model }}
                    </div>

                    <div class="flex items-center gap-2">
                      📅 {{ new Date(booking.trip.departure_time).toLocaleString() }}
                    </div>

                    <div class="flex items-center gap-2">
                      💰
                      <span class="font-semibold text-emerald-600">
                        {{ booking.trip.price.toLocaleString() }} ₽
                      </span>
                    </div>
                  </div>

                  <div class="mt-auto pt-5">
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
                      <div
                          class="w-11 h-11 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex items-center justify-center"
                      >
                        <img
                            v-if="booking.trip.driver.profile?.avatar_url"
                            :src="booking.trip.driver.profile.avatar_url"
                            class="w-full h-full object-cover"
                        />

                        <span v-else class="text-xl">
                          👨‍✈️
                        </span>
                      </div>

                      <div>
                        <div class="font-semibold text-slate-900">
                          {{ booking.trip.driver.name }}
                        </div>

                        <div class="text-xs text-slate-500">
                          Водитель
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- RIGHT -->
                <div class="flex flex-col h-full">
                  <div
                      :class="[
                      'inline-flex self-start items-center gap-1 px-3 py-1.5 rounded-xl text-sm font-medium',
                      getStatusBadge(booking.status).class
                    ]"
                  >
                    {{ getStatusBadge(booking.status).text }}
                  </div>

                  <div class="flex-1"></div>

                  <button
                      v-if="booking.status === 'approved'
                      && booking.trip.status === 'completed'
                      && !booking.passenger_confirmed_at"
                      @click="confirmBooking(booking.id)"
                      :disabled="confirmingBookingId === booking.id"
                      class="mt-4 w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl font-medium disabled:opacity-50"
                  >
                    {{
                      confirmingBookingId === booking.id
                          ? 'Подтверждение...'
                          : '✅ Подтвердить поездку'
                    }}
                  </button>

                  <button
                      v-if="['pending', 'approved'].includes(booking.status)"
                      @click="cancelBooking(booking.id)"
                      class="mt-3 w-full bg-rose-500 hover:bg-rose-600 text-white py-2.5 rounded-xl font-medium"
                  >
                    Отменить заявку
                  </button>

                  <button
                      @click="router.push(`/trips/${booking.trip.id}`)"
                      class="mt-2  border border-slate-300 hover:bg-slate-50 text-slate-700 py-2.5 rounded-xl text-sm font-medium transition"
                  >
                    Подробнее о поездке
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- HISTORY -->
        <div v-if="pastBookings.length">
          <div class="flex items-center gap-2 mb-4">
            <span class="w-1 h-6 bg-slate-400 rounded-full"></span>

            <h2 class="text-lg font-semibold text-slate-900">
              История поездок
            </h2>
          </div>

          <div class="space-y-4">
            <div
                v-for="booking in pastBookings"
                :key="booking.id"
                class="bg-white rounded-2xl border border-slate-200 p-6 hover:shadow-md transition"
            >
              <div
                  class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-6 items-stretch"
              >
                <!-- LEFT -->
                <div class="flex flex-col">
                  <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">

                    <!-- FROM -->
                    <div class="min-w-0 flex-1">

                      <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 flex-shrink-0"></div>

                        <div
                            class="text-xl sm:text-2xl font-bold text-slate-900 truncate"
                        >
                          {{ booking.trip.from_locality.type }}. {{ booking.trip.from_locality.name }}
                        </div>
                      </div>

                      <div
                          class="text-sm sm:text-base text-slate-500 mt-1 pl-5 break-words"
                      >
                        {{ booking.trip.from_locality.region.name }}
                        {{ booking.trip.from_locality.region.type }}
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
                          {{ booking.trip.to_locality.type }}. {{ booking.trip.to_locality.name }}
                        </div>
                      </div>

                      <div
                          class="text-sm sm:text-base text-slate-500 mt-1 pl-5 break-words"
                      >
                        {{ booking.trip.to_locality.region.name }}
                        {{ booking.trip.to_locality.region.type }}
                      </div>

                    </div>

                  </div>


                  <div
                      class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600"
                  >
                    <div class="flex items-center gap-2">
                      🚗 {{ booking.trip.car.brand }} {{ booking.trip.car.model }}
                    </div>

                    <div class="flex items-center gap-2">
                      📅 {{ new Date(booking.trip.departure_time).toLocaleString() }}
                    </div>

                    <div class="flex items-center gap-2">
                      💰
                      <span class="font-semibold text-emerald-600">
                        {{ booking.trip.price.toLocaleString() }} ₽
                      </span>
                    </div>
                  </div>

                  <div class="mt-auto pt-5">
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
                      <div
                          class="w-11 h-11 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex items-center justify-center"
                      >
                        <img
                            v-if="booking.trip.driver.profile?.avatar_url"
                            :src="booking.trip.driver.profile.avatar_url"
                            class="w-full h-full object-cover"
                        />

                        <span v-else class="text-xl">
                          👨‍✈️
                        </span>
                      </div>

                      <div>
                        <RouterLink
                            :to="`/users/${booking.trip.driver.id}`"
                            class="block"
                        >

                          <div class="font-semibold hover:text-blue-600 transition">
                            {{ booking.trip.driver.name }}
                          </div>

                          <div class="text-sm text-gray-500 mt-1">

                            <template v-if="getUserRating(booking.trip.driver)">

                              ⭐
                              {{ getUserRating(booking.trip.driver) }}

                              •

                              {{ getReviewsCount(booking.trip.driver) }}
                              отзывов

                            </template>

                            <template v-else>

                              Новый пользователь

                            </template>

                          </div>

                        </RouterLink>

                        <div class="text-xs text-slate-500">
                          Водитель
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- RIGHT -->
                <div class="flex flex-col h-full">
                  <div
                      :class="[
                      'inline-flex self-start items-center gap-1 px-3 py-1.5 rounded-xl text-sm font-medium',
                      getStatusBadge(booking.status).class
                    ]"
                  >
                    {{ getStatusBadge(booking.status).text }}
                  </div>

                  <!-- REVIEW -->
                  <div
                      v-if="booking.status === 'completed'"
                      class="mt-4 flex-1 flex flex-col mb-2"
                  >
                    <!-- EXISTING REVIEW -->
                    <div
                        v-if="booking.reviews?.length && !editingReviews.has(booking.id)"
                        class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200 text-left min-h-[170px] flex flex-col"
                    >
                      <div class="flex items-center gap-1 mb-2">
                        <div class="flex text-yellow-400 text-lg">
                          {{ '★'.repeat(booking.reviews[0].rating) }}
                          {{ '☆'.repeat(5 - booking.reviews[0].rating) }}
                        </div>

                        <span class="text-sm font-medium ml-2">
                          {{ booking.reviews[0].rating }}/5
                        </span>
                      </div>

                      <div
                          v-if="booking.reviews[0].comment"
                          class="text-sm text-slate-700 flex-1"
                      >
                        <p class="whitespace-pre-wrap break-words">
                          <template v-if="booking.reviews[0].comment.length > 200">
                            <span v-if="!expandedReviews.has(booking.id)">
                              {{ booking.reviews[0].comment.slice(0, 200) }}...
                            </span>

                            <span v-else>
                              {{ booking.reviews[0].comment }}
                            </span>

                            <button
                                @click="toggleExpandReview(booking.id)"
                                class="text-blue-600 text-xs font-medium ml-1"
                            >
                              {{
                                expandedReviews.has(booking.id)
                                    ? 'Свернуть'
                                    : 'Читать далее'
                              }}
                            </button>
                          </template>

                          <span v-else>
                            {{ booking.reviews[0].comment }}
                          </span>
                        </p>
                      </div>

                      <div class="flex justify-between items-center mt-4 pt-3 border-t border-amber-200">
                        <span class="text-xs text-slate-500">
                          ✅ Отзыв оставлен
                        </span>

                        <div class="flex gap-3">
                          <button
                              @click="toggleEditReview(booking.id)"
                              class="text-xs text-blue-600 hover:text-blue-700"
                          >
                            ✏️ Редактировать
                          </button>

                          <button
                              @click="deleteReview(booking.id)"
                              class="text-xs text-red-500 hover:text-red-600"
                          >
                            🗑️ Удалить
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- EDIT REVIEW -->
                    <div
                        v-if="editingReviews.has(booking.id)"
                        class="bg-white rounded-xl p-4 border border-blue-200 text-left"
                    >
                      <div class="flex gap-1 mb-3">
                        <button
                            v-for="star in 5"
                            :key="star"
                            @click="editReviewData[booking.id].rating = star"
                            class="text-2xl"
                        >
                          <span
                              :class="
                              (editReviewData[booking.id]?.rating || 0) >= star
                                ? 'text-yellow-400'
                                : 'text-slate-300'
                            "
                          >
                            ★
                          </span>
                        </button>
                      </div>

                      <textarea
                          v-model="editReviewData[booking.id].comment"
                          rows="4"
                          maxlength="1000"
                          class="w-full border rounded-lg p-3 text-sm resize-y"
                          placeholder="Ваш отзыв..."
                      ></textarea>

                      <div class="flex justify-between text-xs mt-1">
                        <span class="text-slate-400">
                          до 1000 символов
                        </span>

                        <span>
                          {{ editReviewData[booking.id]?.comment?.length || 0 }}/1000
                        </span>
                      </div>

                      <div class="flex gap-2 mt-3">
                        <button
                            @click="updateReview(booking.id)"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"
                        >
                          Сохранить
                        </button>

                        <button
                            @click="toggleEditReview(booking.id)"
                            class="border px-4 py-2 rounded-lg text-sm"
                        >
                          Отмена
                        </button>
                      </div>
                    </div>

                    <!-- BUTTON -->
                    <div
                        v-if="!booking.reviews?.length && !openedReviewForms.has(booking.id)"
                        class="flex-1 flex items-center"
                    >
                      <button
                          @click="toggleReviewForm(booking.id)"
                          class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white px-4 py-2.5 rounded-xl font-medium"
                      >
                        ⭐ Оставить отзыв
                      </button>
                    </div>

                    <!-- CREATE REVIEW -->
                    <div
                        v-if="openedReviewForms.has(booking.id) && !booking.reviews?.length"
                        class="bg-slate-50 rounded-xl p-4 border border-slate-200 text-left"
                    >
                      <h4 class="font-semibold mb-3">
                        Как прошла поездка?
                      </h4>

                      <div class="flex gap-1 mb-3">
                        <button
                            v-for="star in 5"
                            :key="star"
                            @click="setRating(booking.id, star)"
                            class="text-2xl"
                        >
                          <span
                              :class="
                              (reviewForms[booking.id]?.rating || 0) >= star
                                ? 'text-yellow-400'
                                : 'text-slate-300'
                            "
                          >
                            ★
                          </span>
                        </button>
                      </div>

                      <textarea
                          :value="reviewForms[booking.id]?.comment || ''"
                          @input="updateComment(
                          booking.id,
                          ($event.target as HTMLTextAreaElement).value
                        )"
                          rows="4"
                          maxlength="1000"
                          class="w-full border rounded-lg p-3 text-sm resize-y"
                          placeholder="Расскажите о впечатлениях..."
                      ></textarea>

                      <div class="flex justify-between text-xs mt-1">
                        <span class="text-slate-400">
                          до 1000 символов
                        </span>

                        <span>
                          {{ reviewForms[booking.id]?.comment?.length || 0 }}/1000
                        </span>
                      </div>

                      <div class="flex gap-2 mt-3">
                        <button
                            @click="submitReview(booking.id)"
                            :disabled="reviewLoadingId === booking.id"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50"
                        >
                          {{
                            reviewLoadingId === booking.id
                                ? 'Отправка...'
                                : 'Отправить'
                          }}
                        </button>

                        <button
                            @click="toggleReviewForm(booking.id)"
                            class="border px-4 py-2 rounded-lg text-sm"
                        >
                          Отмена
                        </button>
                      </div>
                    </div>
                  </div>

                  <button
                      @click="router.push(`/trips/${booking.trip.id}`)"
                      class="mt-4 w-full border border-slate-300 hover:bg-slate-50 text-slate-700 py-2.5 rounded-xl text-sm font-medium transition mt-auto"
                  >
                    Подробнее о поездке
                  </button>
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
button {
  transition: all 0.2s ease;
}

.whitespace-pre-wrap {
  white-space: pre-wrap;
  word-wrap: break-word;
  word-break: break-word;
  line-height: 1.55;
}

textarea {
  resize: vertical;
  min-height: 90px;
  max-height: 220px;
}

@media (max-width: 640px) {
  .text-sm {
    font-size: 0.875rem;
    line-height: 1.4;
  }
}
</style>