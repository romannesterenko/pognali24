<script setup lang="ts">
import { ref, onMounted, computed, reactive } from 'vue'
import api from '@/api/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { getUserRating, getReviewsCount } from '@/utils/userRating'
import { useToastStore } from '@/stores/toast'
import CityAutocomplete from "@/components/CityAutocomplete.vue"

const toast = useToastStore()

// =========================
// TYPES
// =========================
interface Car {
  id: number
  brand: string
  model: string
}

interface Region {
  id: number
  name: string
  type?: string
}

interface Locality {
  id: string
  name: string
  full_name: string
  type?: string
  region?: Region
}

interface Review {
  id?: number
  rating: number
  comment: string
  type: 'driver_to_passenger' | 'passenger_to_driver'
}

interface Booking {
  id: number
  status: 'pending' | 'approved' | 'rejected' | 'cancelled' | 'completed'
  passenger_confirmed_at: string | null
  passenger: {
    id: number
    name: string
    profile?: { avatar_url?: string }
  }
  reviews?: Review[]
}

interface Trip {
  id: number
  from_locality: Locality
  to_locality: Locality
  departure_time: string
  available_seats: number
  price: number
  comment?: string
  status: 'active' | 'full' | 'started' | 'completed'
  completed_at?: string
  car: Car
  bookings: Booking[]
}

interface TripForm {
  car_id: string | number
  from_locality_id: number | null
  to_locality_id: number | null
  departure_time: string
  available_seats: number
  price: string
  comment: string
}

// =========================
// DATA
// =========================
const trips = ref<Trip[]>([])
const cars = ref<Car[]>([])
const pageLoading = ref(true)
const error = ref<string | null>(null)
const showForm = ref(false)
const creatingTrip = ref(false)
const approvingBookingId = ref<number | null>(null)
const rejectingBookingId = ref<number | null>(null)
const startingTripId = ref<number | null>(null)
const completingTripId = ref<number | null>(null)

// Review forms (driver → passenger)
interface DriverReviewForm {
  rating: number
  comment: string
  open: boolean
}
const reviewForms = reactive<Record<number, DriverReviewForm>>({})
const reviewLoadingId = ref<number | null>(null)

// Editing and deleting reviews
const editingReviewId = ref<number | null>(null)
const editReviewData = reactive<Record<number, { rating: number; comment: string }>>({})
const expandedReviews = ref<Set<number>>(new Set())

// =========================
// FORM
// =========================
const form = ref<TripForm>({
  car_id: '',
  from_locality_id: null,
  to_locality_id: null,
  departure_time: '',
  available_seats: 1,
  price: '',
  comment: '',
})

const isFormValid = computed(() => {
  return (
      form.value.from_locality_id &&
      form.value.to_locality_id &&
      form.value.car_id !== '' &&
      form.value.departure_time !== '' &&
      form.value.available_seats > 0 &&
      form.value.price !== '' &&
      Number(form.value.price) > 0
  )
})

// =========================
// HELPERS
// =========================
const formatDate = (date: string) => {
  if (!date) return ''
  return new Date(date).toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusLabel = (status: string) => {
  const map: Record<string, { text: string; class: string }> = {
    pending: { text: '⏳ Ожидает подтверждения', class: 'bg-amber-100 text-amber-700' },
    approved: { text: '✅ Подтверждено', class: 'bg-emerald-100 text-emerald-700' },
    rejected: { text: '❌ Отклонено', class: 'bg-rose-100 text-rose-700' },
    cancelled: { text: '🚫 Отменено', class: 'bg-slate-100 text-slate-700' },
    completed: { text: '🏁 Завершено', class: 'bg-sky-100 text-sky-700' },
  }
  return map[status] || { text: status, class: 'bg-gray-100 text-gray-700' }
}

const getTripStatusBadge = (status: string) => {
  switch (status) {
    case 'full':
    case 'active':
      return { text: '🟢 Опубликована', class: 'bg-emerald-100 text-emerald-700' }
    case 'started':
      return { text: '🚘 Поездка началась', class: 'bg-blue-100 text-blue-700' }
    case 'completed':
      return { text: '✅ Поездка завершена', class: 'bg-slate-100 text-slate-700' }
    default:
      return { text: status, class: 'bg-gray-100 text-gray-700' }
  }
}

// =========================
// LOAD DATA
// =========================
const loadData = async () => {
  pageLoading.value = true
  error.value = null
  try {
    const [tripsRes, carsRes] = await Promise.all([
      api.get('/driver/trips'),
      api.get('/cars'),
    ])
    trips.value = tripsRes.data.trips || []
    cars.value = carsRes.data.cars || []
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Ошибка загрузки'
  } finally {
    pageLoading.value = false
  }
}

// =========================
// CREATE TRIP
// =========================
const createTrip = async () => {
  if (!isFormValid.value) {
    toast.show({
      type: 'error',
      title: 'Ошибка создания поездки',
      message: 'Заполните все обязательные поля',
    })
    return
  }
  creatingTrip.value = true
  try {
    await api.post('/trips', {
      car_id: form.value.car_id,
      from_fias_id: form.value.from_locality_id,
      to_fias_id: form.value.to_locality_id,
      departure_time: form.value.departure_time,
      available_seats: form.value.available_seats,
      price: form.value.price,
      comment: form.value.comment,
    })
    form.value = {
      car_id: '',
      from_locality_id: null,
      to_locality_id: null,
      departure_time: '',
      available_seats: 1,
      price: '',
      comment: '',
    }
    showForm.value = false
    await loadData()
    toast.show({
      type: 'success',
      title: 'Поездка создана',
      message: 'Поездка была успешно создана',
    })
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка создания поездки',
      message: e.response?.data?.message || 'Ошибка',
    })
  } finally {
    creatingTrip.value = false
  }
}

// =========================
// BOOKINGS ACTIONS
// =========================
const approveBooking = async (bookingId: number) => {
  approvingBookingId.value = bookingId
  try {
    await api.post(`/bookings/${bookingId}/approve`)
    toast.show({ type: 'success', title: 'Успешно', message: 'Заявка одобрена' })
    await loadData()
  } catch (e: any) {
    toast.show({ type: 'error', title: 'Ошибка', message: e.response?.data?.message || 'Ошибка' })
  } finally {
    approvingBookingId.value = null
  }
}

const rejectBooking = async (bookingId: number) => {
  rejectingBookingId.value = bookingId
  try {
    await api.post(`/bookings/${bookingId}/reject`)
    await loadData()
    toast.show({ type: 'success', title: 'Успешно', message: 'Заявка отклонена' })
  } catch (e: any) {
    toast.show({ type: 'error', title: 'Ошибка', message: e.response?.data?.message || 'Ошибка' })
  } finally {
    rejectingBookingId.value = null
  }
}

// =========================
// TRIP STATUS
// =========================
const startTrip = async (tripId: number) => {
  startingTripId.value = tripId
  try {
    await api.post(`/trips/${tripId}/start`)
    toast.show({ type: 'success', title: 'Успешно', message: 'Поездка началась' })
    await loadData()
  } catch (e: any) {
    toast.show({ type: 'error', title: 'Ошибка', message: e.response?.data?.message || 'Ошибка' })
  } finally {
    startingTripId.value = null
  }
}

const completeTrip = async (tripId: number) => {
  if (!confirm('Завершить поездку?')) return
  completingTripId.value = tripId
  try {
    await api.post(`/trips/${tripId}/complete`)
    toast.show({ type: 'success', title: 'Успешно', message: 'Поездка завершена' })
    await loadData()
  } catch (e: any) {
    toast.show({ type: 'error', title: 'Ошибка', message: e.response?.data?.message || 'Ошибка' })
  } finally {
    completingTripId.value = null
  }
}

// =========================
// REVIEWS (Driver to Passenger)
// =========================
const getDriverReview = (booking: Booking): Review | undefined => {
  return booking.reviews?.find(r => r.type === 'driver_to_passenger')
}

const toggleReviewForm = (bookingId: number) => {
  if (!reviewForms[bookingId]) {
    reviewForms[bookingId] = { rating: 5, comment: '', open: true }
  } else {
    reviewForms[bookingId].open = !reviewForms[bookingId].open
  }
}

const submitReview = async (bookingId: number) => {
  const formData = reviewForms[bookingId]
  if (!formData || !formData.rating) {
    toast.show({
      type: 'error',
      title: 'Отзыв не отправлен',
      message: 'Поставьте оценку в отзыве',
    })
    return
  }
  reviewLoadingId.value = bookingId
  try {
    await api.post(`/bookings/${bookingId}/reviews`, {
      rating: formData.rating,
      comment: formData.comment?.trim() || null,
    })
    await loadData()
    delete reviewForms[bookingId]
    toast.show({ type: 'success', title: 'Успешно', message: 'Отзыв отправлен' })
  } catch (e: any) {
    toast.show({ type: 'error', title: 'Ошибка', message: e.response?.data?.message || 'Ошибка' })
  } finally {
    reviewLoadingId.value = null
  }
}

const startEditReview = (bookingId: number, review: Review) => {
  editingReviewId.value = bookingId
  editReviewData[bookingId] = {
    rating: review.rating,
    comment: review.comment || '',
  }
}

const cancelEditReview = () => {
  editingReviewId.value = null
  delete editReviewData[editingReviewId.value as number]
}

const updateReview = async (bookingId: number) => {
  const data = editReviewData[bookingId]
  if (!data) return
  try {
    await api.put(`/bookings/${bookingId}/review`, {
      rating: data.rating,
      comment: data.comment,
    })
    await loadData()
    editingReviewId.value = null
    delete editReviewData[bookingId]
    toast.show({ type: 'success', title: 'Отзыв обновлён', message: '' })
  } catch (e: any) {
    toast.show({ type: 'error', title: 'Ошибка', message: e.response?.data?.message || 'Ошибка' })
  }
}

const deleteReview = async (bookingId: number) => {
  if (!confirm('Удалить отзыв?')) return
  try {
    await api.delete(`/bookings/${bookingId}/review`)
    await loadData()
    toast.show({ type: 'success', title: 'Отзыв удалён', message: '' })
  } catch (e: any) {
    toast.show({ type: 'error', title: 'Ошибка', message: e.response?.data?.message || 'Ошибка' })
  }
}

const toggleExpandReview = (bookingId: number) => {
  if (expandedReviews.value.has(bookingId)) {
    expandedReviews.value.delete(bookingId)
  } else {
    expandedReviews.value.add(bookingId)
  }
}

// =========================
// COMPUTED: group trips
// =========================
const activeTrips = computed(() =>
    trips.value.filter(t => t.status === 'active' || t.status === 'started' || t.status === 'full')
)
const completedTrips = computed(() =>
    trips.value.filter(t => t.status === 'completed')
)

// =========================
// MOUNT
// =========================
onMounted(loadData)
</script>

<template>
  <AuthLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 md:py-8">
      <!-- Loading -->
      <div v-if="pageLoading" class="flex justify-center py-20">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="text-center py-10">
        <div class="text-red-500 text-lg">{{ error }}</div>
        <button @click="loadData" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition">
          Повторить
        </button>
      </div>

      <!-- Content -->
      <template v-else>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent">
              Мои поездки
            </h1>
            <p class="text-slate-500 mt-1">Управляйте поездками и пассажирами</p>
          </div>
          <button
              @click="showForm = true"
              class="bg-blue-600 hover:bg-blue-700 transition-all transform hover:scale-105 text-white px-5 py-2.5 rounded-xl font-medium shadow-md w-full sm:w-auto"
          >
            + Создать поездку
          </button>
        </div>

        <!-- Create Trip Form -->
        <div v-if="showForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-10">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-slate-900">Новая поездка</h2>
            <button @click="showForm = false" class="text-2xl text-slate-400 hover:text-slate-600 transition">✕</button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Откуда *</label>
              <CityAutocomplete
                  v-model="form.from_locality_id"
                  placeholder="Откуда"
                  input-class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Куда *</label>
              <CityAutocomplete
                  v-model="form.to_locality_id"
                  placeholder="Куда"
                  input-class="w-full border border-slate-300 rounded-xl p-3"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Автомобиль *</label>
              <select v-model="form.car_id" class="w-full border border-slate-300 rounded-xl p-3">
                <option value="">Выберите автомобиль</option>
                <option v-for="car in cars" :key="car.id" :value="car.id">{{ car.brand }} {{ car.model }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Дата и время *</label>
              <input v-model="form.departure_time" type="datetime-local" class="w-full border border-slate-300 rounded-xl p-3" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Мест *</label>
              <input v-model="form.available_seats" type="number" min="1" class="w-full border border-slate-300 rounded-xl p-3" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Цена (₽) *</label>
              <input v-model="form.price" type="number" class="w-full border border-slate-300 rounded-xl p-3" />
            </div>
          </div>
          <div class="mt-5">
            <label class="block text-sm font-medium text-slate-700 mb-2">Комментарий</label>
            <textarea v-model="form.comment" rows="3" class="w-full border border-slate-300 rounded-xl p-3 resize-y"></textarea>
          </div>
          <div class="flex flex-col sm:flex-row gap-4 mt-6">
            <button @click="createTrip" :disabled="creatingTrip" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl disabled:opacity-50">
              {{ creatingTrip ? 'Создание...' : 'Опубликовать' }}
            </button>
            <button @click="showForm = false" class="border border-slate-300 hover:bg-slate-50 px-6 py-3 rounded-xl transition">Отмена</button>
          </div>
        </div>

        <!-- No trips -->
        <div v-if="trips.length === 0" class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
          <div class="text-6xl mb-4">🚗</div>
          <h3 class="text-xl font-semibold text-slate-900 mb-2">У вас пока нет поездок</h3>
          <p class="text-slate-500 mb-6">Создайте первую поездку, чтобы начать</p>
          <button @click="showForm = true" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-medium">
            + Создать поездку
          </button>
        </div>

        <!-- Trips List -->
        <div v-else class="space-y-8">
          <!-- Active Trips -->
          <div v-if="activeTrips.length">
            <div class="flex items-center gap-2 mb-4">
              <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
              <h2 class="text-lg font-semibold text-slate-900">Активные поездки</h2>
            </div>
            <div class="space-y-6">
              <div v-for="trip in activeTrips" :key="trip.id" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Trip Header -->
                <div class="p-5 md:p-6 border-b border-slate-100">
                  <div class="flex flex-col gap-5">
                    <!-- Route -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <div class="w-3 h-3 rounded-full bg-emerald-500 flex-shrink-0"></div>
                          <div class="text-lg sm:text-xl font-bold text-slate-900 truncate">
                            {{ trip.from_locality?.type || '' }}. {{ trip.from_locality?.name || '?' }}
                          </div>
                        </div>
                        <div class="text-sm text-slate-500 mt-1 pl-5">
                          {{ trip.from_locality?.region?.name || '' }} {{ trip.from_locality?.region?.type || '' }}
                        </div>
                      </div>

                      <div class="text-slate-400 text-xl sm:text-2xl text-center rotate-90 sm:rotate-0">→</div>

                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <div class="w-3 h-3 rounded-full bg-rose-500 flex-shrink-0"></div>
                          <div class="text-lg sm:text-xl font-bold text-slate-900 truncate">
                            {{ trip.to_locality?.type || '' }}. {{ trip.to_locality?.name || '?' }}
                          </div>
                        </div>
                        <div class="text-sm text-slate-500 mt-1 pl-5">
                          {{ trip.to_locality?.region?.name || '' }} {{ trip.to_locality?.region?.type || '' }}
                        </div>
                      </div>
                    </div>

                    <!-- Trip details grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-slate-600">
                      <div class="flex items-center gap-2">🚗 {{ trip.car.brand }} {{ trip.car.model }}</div>
                      <div class="flex items-center gap-2">🕒 {{ formatDate(trip.departure_time) }}</div>
                      <div class="flex items-center gap-2">💺 {{ trip.available_seats === 0 ? 'Мест нет' : trip.available_seats + ' мест' }}</div>
                      <div class="flex items-center gap-2">💰 <span class="font-semibold text-emerald-600">{{ trip.price }} ₽</span></div>
                    </div>

                    <div v-if="trip.comment" class="bg-slate-50 rounded-xl p-4 text-slate-700 text-sm">
                      {{ trip.comment }}
                    </div>

                    <!-- Status badge and action buttons -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-2">
                      <div :class="['inline-flex self-start items-center gap-1 px-4 py-2 rounded-xl text-sm font-medium', getTripStatusBadge(trip.status).class]">
                        {{ getTripStatusBadge(trip.status).text }}
                      </div>
                      <div class="flex flex-col sm:flex-row gap-3">
                        <button
                            v-if="trip.status === 'active' || trip.status === 'full'"
                            @click="startTrip(trip.id)"
                            :disabled="startingTripId === trip.id"
                            class="bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-5 rounded-xl font-medium disabled:opacity-50 w-full sm:w-auto"
                        >
                          {{ startingTripId === trip.id ? 'Запуск...' : '🚀 Начать поездку' }}
                        </button>
                        <button
                            v-else-if="trip.status === 'started'"
                            @click="completeTrip(trip.id)"
                            :disabled="completingTripId === trip.id"
                            class="bg-black hover:bg-slate-800 text-white py-2.5 px-5 rounded-xl font-medium w-full sm:w-auto"
                        >
                          {{ completingTripId === trip.id ? 'Завершение...' : '🏁 Завершить поездку' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Passengers Section -->
                <div class="p-5 md:p-6">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
                    <h3 class="text-lg md:text-xl font-semibold text-slate-900">Пассажиры</h3>
                    <span class="text-sm text-slate-500">{{ trip.bookings?.length || 0 }} заявок</span>
                  </div>

                  <div v-if="!trip.bookings?.length" class="bg-slate-50 rounded-xl p-8 text-center text-slate-500">
                    Пока нет заявок
                  </div>

                  <div v-else class="space-y-4">
                    <div v-for="booking in trip.bookings" :key="booking.id" class="border border-slate-200 rounded-xl p-4 md:p-5">
                      <div class="flex flex-col lg:flex-row lg:items-center gap-5">
                        <!-- Passenger info -->
                        <div class="flex flex-1 flex-col sm:flex-row sm:items-start gap-4">
                          <div class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex items-center justify-center flex-shrink-0">
                            <img v-if="booking.passenger.profile?.avatar_url" :src="booking.passenger.profile.avatar_url" class="w-full h-full object-cover" />
                            <span v-else class="text-xl text-slate-500">{{ booking.passenger.name?.[0] }}</span>
                          </div>
                          <div>
                            <RouterLink :to="`/users/${booking.passenger.id}`" class="font-semibold hover:text-blue-600 transition">
                              {{ booking.passenger.name }}
                            </RouterLink>
                            <div class="text-sm text-gray-500 mt-0.5">
                              <template v-if="getUserRating(booking.passenger)">
                                ⭐ {{ getUserRating(booking.passenger) }} • {{ getReviewsCount(booking.passenger) }} отзывов
                              </template>
                              <template v-else>Новый пользователь</template>
                            </div>
                            <div :class="['inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium', getStatusLabel(booking.status).class]">
                              {{ getStatusLabel(booking.status).text }}
                            </div>
                            <div v-if="booking.passenger_confirmed_at" class="mt-2 text-xs text-emerald-600">✅ Пассажир подтвердил завершение</div>
                          </div>
                        </div>

                        <!-- Actions & Reviews -->
                        <div class="flex flex-col items-start lg:items-end gap-3">
                          <div v-if="booking.status === 'pending'" class="flex flex-wrap gap-3">
                            <button @click="approveBooking(booking.id)" :disabled="approvingBookingId === booking.id" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-sm font-medium disabled:opacity-50">
                              Принять
                            </button>
                            <button @click="rejectBooking(booking.id)" :disabled="rejectingBookingId === booking.id" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-xl text-sm font-medium">
                              Отклонить
                            </button>
                          </div>

                          <!-- Completed & passenger confirmed => review block -->
                          <div v-if="booking.status === 'completed' && booking.passenger_confirmed_at" class="w-full max-w-sm">
                            <!-- Existing review -->
                            <div v-if="getDriverReview(booking) && editingReviewId !== booking.id" class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200">
                              <div class="flex items-center gap-1">
                                <div class="flex text-yellow-400 text-lg">
                                  {{ '★'.repeat(getDriverReview(booking)!.rating) }}{{ '☆'.repeat(5 - getDriverReview(booking)!.rating) }}
                                </div>
                                <span class="text-sm font-medium ml-2">{{ getDriverReview(booking)!.rating }}/5</span>
                              </div>
                              <div v-if="getDriverReview(booking)!.comment" class="text-sm text-slate-700 mt-2">
                                <p class="whitespace-pre-wrap break-words">
                                  <template v-if="getDriverReview(booking)!.comment.length > 150">
                                    <span v-if="!expandedReviews.has(booking.id)">{{ getDriverReview(booking)!.comment.slice(0, 150) }}...</span>
                                    <span v-else>{{ getDriverReview(booking)!.comment }}</span>
                                    <button @click="toggleExpandReview(booking.id)" class="text-blue-600 text-xs font-medium ml-1">
                                      {{ expandedReviews.has(booking.id) ? 'Свернуть' : 'Читать далее' }}
                                    </button>
                                  </template>
                                  <span v-else>{{ getDriverReview(booking)!.comment }}</span>
                                </p>
                              </div>
                              <div class="flex flex-wrap justify-between items-center gap-2 mt-3">
                                <span class="text-xs text-slate-500">✅ Вы оставили отзыв</span>
                                <div class="flex gap-2">
                                  <button @click="startEditReview(booking.id, getDriverReview(booking)!)" class="text-xs text-blue-600 hover:text-blue-700">✏️ Редактировать</button>
                                  <button @click="deleteReview(booking.id)" class="text-xs text-red-500 hover:text-red-600">🗑️ Удалить</button>
                                </div>
                              </div>
                            </div>

                            <!-- Edit review mode -->
                            <div v-if="editingReviewId === booking.id" class="bg-white rounded-xl p-3 border border-blue-200">
                              <div class="flex gap-1 mb-3">
                                <button v-for="star in 5" :key="star" @click="editReviewData[booking.id].rating = star" class="text-2xl">
                                  <span :class="(editReviewData[booking.id]?.rating || 0) >= star ? 'text-yellow-400' : 'text-slate-300'">★</span>
                                </button>
                              </div>
                              <textarea v-model="editReviewData[booking.id].comment" rows="3" maxlength="1000" class="w-full border rounded-lg p-2 text-sm resize-y" placeholder="Ваш отзыв..."></textarea>
                              <div class="flex justify-between text-xs mt-1">
                                <span class="text-slate-400">до 1000 символов</span>
                                <span :class="(editReviewData[booking.id]?.comment?.length || 0) > 900 ? 'text-amber-600' : 'text-slate-500'">
                                  {{ editReviewData[booking.id]?.comment?.length || 0 }}/1000
                                </span>
                              </div>
                              <div class="flex gap-2 mt-3">
                                <button @click="updateReview(booking.id)" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm">Сохранить</button>
                                <button @click="cancelEditReview" class="border px-3 py-1.5 rounded-lg text-sm">Отмена</button>
                              </div>
                            </div>

                            <!-- No review yet -->
                            <div v-if="!getDriverReview(booking) && !reviewForms[booking.id]?.open">
                              <button @click="toggleReviewForm(booking.id)" class="w-full bg-black hover:bg-slate-800 text-white py-2.5 rounded-xl text-sm font-medium transition">
                                ⭐ Оставить отзыв о пассажире
                              </button>
                            </div>
                            <div v-if="reviewForms[booking.id]?.open && !getDriverReview(booking)" class="bg-slate-50 rounded-xl p-4 border border-slate-200 mt-3">
                              <h4 class="font-semibold text-slate-900 mb-2">Оцените пассажира</h4>
                              <div class="flex gap-1 mb-3">
                                <button v-for="star in 5" :key="star" @click="reviewForms[booking.id].rating = star" class="text-2xl">
                                  <span :class="reviewForms[booking.id].rating >= star ? 'text-yellow-400' : 'text-slate-300'">★</span>
                                </button>
                              </div>
                              <textarea v-model="reviewForms[booking.id].comment" rows="3" maxlength="1000" class="w-full border rounded-lg p-2 text-sm resize-y" placeholder="Как прошла поездка?"></textarea>
                              <div class="flex justify-between text-xs mt-1">
                                <span class="text-slate-400">до 1000 символов</span>
                                <span :class="(reviewForms[booking.id]?.comment?.length || 0) > 900 ? 'text-amber-600' : 'text-slate-500'">
                                  {{ reviewForms[booking.id]?.comment?.length || 0 }}/1000
                                </span>
                              </div>
                              <div class="flex gap-2 mt-3">
                                <button @click="submitReview(booking.id)" :disabled="reviewLoadingId === booking.id" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm">
                                  {{ reviewLoadingId === booking.id ? 'Отправка...' : 'Отправить' }}
                                </button>
                                <button @click="toggleReviewForm(booking.id)" class="border px-3 py-1.5 rounded-lg text-sm">Отмена</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Completed Trips -->
          <div v-if="completedTrips.length">
            <div class="flex items-center gap-2 mb-4 mt-8">
              <span class="w-1 h-6 bg-slate-400 rounded-full"></span>
              <h2 class="text-lg font-semibold text-slate-900">Завершённые поездки</h2>
            </div>
            <div class="space-y-6">
              <div v-for="trip in completedTrips" :key="trip.id" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 md:p-6 border-b border-slate-100">
                  <div class="flex flex-col gap-5">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                          <div class="text-lg sm:text-xl font-bold text-slate-900 truncate">
                            {{ trip.from_locality?.type || '' }}. {{ trip.from_locality?.name || '?' }}
                          </div>
                        </div>
                        <div class="text-sm text-slate-500 pl-5 mt-1">
                          {{ trip.from_locality?.region?.name || '' }} {{ trip.from_locality?.region?.type || '' }}
                        </div>
                      </div>
                      <div class="text-slate-400 text-xl text-center rotate-90 sm:rotate-0">→</div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                          <div class="text-lg sm:text-xl font-bold text-slate-900 truncate">
                            {{ trip.to_locality?.type || '' }}. {{ trip.to_locality?.name || '?' }}
                          </div>
                        </div>
                        <div class="text-sm text-slate-500 pl-5 mt-1">
                          {{ trip.to_locality?.region?.name || '' }} {{ trip.to_locality?.region?.type || '' }}
                        </div>
                      </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-slate-600">
                      <div class="flex items-center gap-2">🚗 {{ trip.car.brand }} {{ trip.car.model }}</div>
                      <div class="flex items-center gap-2">🕒 {{ formatDate(trip.departure_time) }}</div>
                      <div class="flex items-center gap-2">💺 {{ trip.available_seats }} мест</div>
                      <div class="flex items-center gap-2">💰 <span class="font-semibold text-emerald-600">{{ trip.price }} ₽</span></div>
                    </div>

                    <div v-if="trip.comment" class="bg-slate-50 rounded-xl p-4 text-slate-700 text-sm">{{ trip.comment }}</div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                      <div :class="['inline-flex self-start items-center gap-1 px-4 py-2 rounded-xl text-sm font-medium', getTripStatusBadge(trip.status).class]">
                        {{ getTripStatusBadge(trip.status).text }}
                      </div>
                      <div class="text-xs text-slate-400">{{ formatDate(trip.completed_at || '') }}</div>
                    </div>
                  </div>
                </div>

                <!-- Passengers for completed trips (same review logic) -->
                <div class="p-5 md:p-6">
                  <h3 class="text-lg md:text-xl font-semibold text-slate-900 mb-5">Пассажиры</h3>
                  <div class="space-y-4">
                    <div v-for="booking in trip.bookings" :key="booking.id" class="border border-slate-200 rounded-xl p-4 md:p-5">
                      <div class="flex flex-col lg:flex-row lg:items-center gap-5">
                        <div class="flex flex-1 flex-col sm:flex-row sm:items-start gap-4">
                          <div class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex items-center justify-center">
                            <img v-if="booking.passenger.profile?.avatar_url" :src="booking.passenger.profile.avatar_url" class="w-full h-full object-cover" />
                            <span v-else class="text-xl">{{ booking.passenger.name?.[0] }}</span>
                          </div>
                          <div>
                            <RouterLink :to="`/users/${booking.passenger.id}`" class="font-semibold hover:text-blue-600 transition">
                              {{ booking.passenger.name }}
                            </RouterLink>
                            <div class="text-sm text-gray-500 mt-0.5">
                              <template v-if="getUserRating(booking.passenger, 'driver_to_passenger')">
                                ⭐ {{ getUserRating(booking.passenger, 'driver_to_passenger') }} • {{ getReviewsCount(booking.passenger, 'driver_to_passenger') }} отзывов
                              </template>
                              <template v-else>Новый пользователь</template>
                            </div>
                            <div :class="['inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium', getStatusLabel(booking.status).class]">
                              {{ getStatusLabel(booking.status).text }}
                            </div>
                          </div>
                        </div>

                        <div class="w-full max-w-sm">
                          <div v-if="booking.status === 'completed' && booking.passenger_confirmed_at">
                            <div v-if="getDriverReview(booking) && editingReviewId !== booking.id" class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200">
                              <div class="flex items-center gap-1">
                                <div class="flex text-yellow-400">{{ '★'.repeat(getDriverReview(booking)!.rating) }}{{ '☆'.repeat(5 - getDriverReview(booking)!.rating) }}</div>
                                <span class="text-sm font-medium ml-2">{{ getDriverReview(booking)!.rating }}/5</span>
                              </div>
                              <div v-if="getDriverReview(booking)!.comment" class="text-sm text-slate-700 mt-2">
                                <p class="whitespace-pre-wrap break-words">
                                  <template v-if="getDriverReview(booking)!.comment.length > 150">
                                    <span v-if="!expandedReviews.has(booking.id)">{{ getDriverReview(booking)!.comment.slice(0, 150) }}...</span>
                                    <span v-else>{{ getDriverReview(booking)!.comment }}</span>
                                    <button @click="toggleExpandReview(booking.id)" class="text-blue-600 text-xs">Читать далее</button>
                                  </template>
                                  <span v-else>{{ getDriverReview(booking)!.comment }}</span>
                                </p>
                              </div>
                              <div class="flex flex-wrap justify-between gap-2 mt-3">
                                <span class="text-xs text-slate-500">Отзыв оставлен</span>
                                <div class="flex gap-2">
                                  <button @click="startEditReview(booking.id, getDriverReview(booking)!)" class="text-xs text-blue-600">Редактировать</button>
                                  <button @click="deleteReview(booking.id)" class="text-xs text-red-500">Удалить</button>
                                </div>
                              </div>
                            </div>

                            <div v-if="!getDriverReview(booking) && !reviewForms[booking.id]?.open">
                              <button @click="toggleReviewForm(booking.id)" class="w-full bg-black hover:bg-slate-800 text-white py-2 rounded-xl text-sm">Оставить отзыв</button>
                            </div>

                            <div v-if="reviewForms[booking.id]?.open && !getDriverReview(booking)" class="bg-slate-50 p-3 rounded-xl mt-2">
                              <div class="flex gap-1 mb-2">
                                <button v-for="star in 5" :key="star" @click="reviewForms[booking.id].rating = star" class="text-2xl">
                                  {{ reviewForms[booking.id].rating >= star ? '★' : '☆' }}
                                </button>
                              </div>
                              <textarea v-model="reviewForms[booking.id].comment" rows="2" maxlength="1000" class="w-full border rounded-lg p-2 text-sm"></textarea>
                              <div class="flex justify-between text-xs mt-1">
                                <span>до 1000 символов</span>
                                <span>{{ (reviewForms[booking.id]?.comment?.length || 0) }}/1000</span>
                              </div>
                              <div class="flex gap-2 mt-2">
                                <button @click="submitReview(booking.id)" class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm">Отправить</button>
                                <button @click="toggleReviewForm(booking.id)" class="border px-3 py-1 rounded-lg text-sm">Отмена</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AuthLayout>
</template>

<style scoped>
.whitespace-pre-wrap {
  white-space: pre-wrap;
  word-break: break-word;
}
button {
  transition: all 0.2s ease;
}
textarea {
  resize: vertical;
}
</style>