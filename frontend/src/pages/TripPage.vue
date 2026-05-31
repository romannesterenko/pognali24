<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const trip = ref<any>(null)
const loading = ref(false)
const bookingLoading = ref(false)
const currentBooking = ref<any>(null) // существующее бронирование пользователя

// Форматирование даты
const formatDateTime = (dateStr: string) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Рейтинг водителя звёздами
const renderStars = (rating: number) => {
  const num = Number(rating) || 0
  const full = '★'.repeat(Math.floor(num))
  const half = num % 1 >= 0.5 ? '½' : ''
  const empty = '☆'.repeat(5 - Math.ceil(num))
  return (full + half + empty).slice(0, 5)
}

const safeRating = (driver: any) => {
  const rating = driver?.driver_profile?.rating || 0
  return Number(rating) || 0
}

// Загрузка поездки и проверка существующего бронирования
const loadTrip = async () => {
  loading.value = true
  try {
    const res = await api.get(`/trips/${route.params.id}`)
    trip.value = res.data.trip

    // Проверяем, есть ли у текущего пользователя бронирование на эту поездку
    if (auth.user) {
      const existing = trip.value.bookings?.find((b: any) => b.passenger_id === auth.user.id)
      currentBooking.value = existing || null
    }
  } catch (e: any) {
    console.error(e)
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка загрузки поездки',
    })
  } finally {
    loading.value = false
  }
}

// Бронирование места
const bookTrip = async () => {
  if (!auth.user) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: 'Войдите в аккаунт, чтобы забронировать',
    })
    await router.push('/login')
    return
  }
  bookingLoading.value = true
  try {
    await api.post(`/trips/${trip.value.id}/book`)
    toast.show({
      type: 'success',
      title: 'Успешно',
      message: 'Заявка отправлена водителю',
    })
    await loadTrip() // обновим статус бронирования
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка при бронировании',
    })
  } finally {
    bookingLoading.value = false
  }
}

// Отмена бронирования
const cancelBooking = async () => {
  if (!currentBooking.value) return
  if (!confirm('Отменить заявку?')) return
  try {
    await api.post(`/bookings/${currentBooking.value.id}/cancel`)
    toast.show({
      type: 'success',
      title: 'Успешно',
      message: 'Заявка отменена',
    })
    await loadTrip()
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка',
    })
  }
}

// Начать диалог с водителем
const startChat = async () => {
  if (!auth.user) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: 'Войдите, чтобы написать водителю',
    })
    await router.push('/login')
    return
  }
  try {
    // Создаём или получаем conversation с этим водителем
    const res = await api.post('/conversations', {
      driver_id: trip.value.driver.id,
      user_id: auth.user.id,
      trip_id: trip.value.id
    })

    await router.push(`/conversations/${res.data.conversation.id}`)
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Не удалось открыть чат',
    })
  }
}

// Статус бронирования (текст и цвет)
const bookingStatus = computed(() => {
  if (!currentBooking.value) return null
  const status = currentBooking.value.status
  const map: Record<string, { text: string; class: string }> = {
    pending: { text: '⏳ Ожидает подтверждения', class: 'bg-amber-100 text-amber-700' },
    approved: { text: '✅ Подтверждено', class: 'bg-emerald-100 text-emerald-700' },
    rejected: { text: '❌ Отклонено', class: 'bg-rose-100 text-rose-700' },
    cancelled: { text: '🚫 Отменено', class: 'bg-slate-100 text-slate-700' }
  }
  return map[status] || null
})

const hasMyBooking = (trip: object) => {
    return trip.bookings?.find(
        (b: any) => b.passenger_id === auth.user.id
    )
}
onMounted(loadTrip)
</script>

<template>
  <AuthLayout>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Лоадер -->
      <div v-if="loading" class="flex justify-center py-20">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <div v-else-if="trip" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Левая колонка: детали поездки -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Карточка маршрута -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-6">
              <div>
                <div class="text-sm text-slate-500">Дата и время</div>
                <div class="font-medium text-slate-900 mt-1">{{ formatDateTime(trip.departure_time) }}</div>
              </div>
              <div>
                <div class="text-sm text-slate-500">Свободных мест</div>
                <div class="font-medium text-slate-900 mt-1">{{ trip.available_seats }}</div>
              </div>
              <div>
                <div class="text-sm text-slate-500">Цена за место</div>
                <div class="font-medium text-emerald-600 mt-1 text-lg">{{ trip.price }} ₽</div>
              </div>
              <div>
                <div class="text-sm text-slate-500">Статус</div>
                <div class="mt-1">
                  <span
                      v-if="trip.status === 'active'"
                      class="inline-block bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-xl text-sm font-medium"
                  >🟢 Активна</span>
                  <span
                      v-else-if="trip.status === 'full'"
                      class="inline-block bg-amber-100 text-amber-700 px-3 py-1.5 rounded-xl text-sm font-medium"
                  >🟡 Нет мест</span>
                  <span
                      v-else
                      class="inline-block bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl text-sm font-medium"
                  >⚪ Завершена</span>
                </div>
              </div>
            </div>

            <div v-if="trip.comment" class="mt-6 pt-4 border-t border-slate-100">
              <div class="text-sm font-medium text-slate-500 mb-2">Комментарий водителя</div>
              <div class="text-slate-700 whitespace-pre-wrap">{{ trip.comment }}</div>
            </div>
          </div>

          <!-- Карточка водителя -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-xl font-bold text-slate-900 mb-5">Водитель</h2>
            <div class="flex flex-col sm:flex-row gap-5">
              <div class="w-20 h-20 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 flex-shrink-0 mx-auto sm:mx-0">
                <img
                    v-if="trip.driver.profile?.avatar_url"
                    :src="trip.driver.profile.avatar_url"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-2xl font-medium text-slate-500">
                  {{ trip.driver.name?.charAt(0) || '?' }}
                </div>
              </div>
              <div class="flex-1 text-center sm:text-left">
                <RouterLink
                    :to="`/users/${trip.driver.id}`"
                    class="text-xl font-bold text-slate-900 hover:text-blue-600 transition"
                >
                  {{ trip.driver.name }}
                </RouterLink>
                <div class="flex flex-wrap items-center gap-2 mt-1 justify-center sm:justify-start">
                  <div class="flex items-center gap-1">
                    <span class="text-yellow-500 text-sm">{{ renderStars(safeRating(trip.driver)) }}</span>
                    <span class="text-xs text-slate-500">{{ safeRating(trip.driver).toFixed(1) }}</span>
                  </div>
                  <span class="text-xs text-slate-400">•</span>
                  <div class="text-sm text-slate-500">
                    Стаж: {{ trip.driver.driver_profile?.experience || 0 }} лет
                  </div>
                </div>
                <div v-if="trip.driver.driver_profile?.about" class="mt-3 text-slate-600 text-sm">
                  {{ trip.driver.driver_profile.about }}
                </div>
              </div>
            </div>
          </div>

          <!-- Карточка автомобиля -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-xl font-bold text-slate-900 mb-5">Автомобиль</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <div class="text-sm text-slate-500">Марка</div>
                <div class="font-medium text-slate-900 mt-1">{{ trip.car.brand || '—' }}</div>
              </div>
              <div>
                <div class="text-sm text-slate-500">Модель</div>
                <div class="font-medium text-slate-900 mt-1">{{ trip.car.model || '—' }}</div>
              </div>
              <div>
                <div class="text-sm text-slate-500">Цвет</div>
                <div class="font-medium text-slate-900 mt-1">{{ trip.car.color || '—' }}</div>
              </div>
              <div>
                <div class="text-sm text-slate-500">Гос. номер</div>
                <div class="font-medium text-slate-900 mt-1">{{ trip.car.plate_number || '—' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Правая колонка: бронирование и действия -->
        <div>
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sticky top-24 space-y-5">
            <!-- Цена -->
            <div>
              <div class="text-3xl font-bold text-emerald-600">{{ trip.price }} ₽</div>
              <div class="text-sm text-slate-500">за место</div>
            </div>

            <!-- Состояние существующего бронирования -->
            <div v-if="currentBooking && bookingStatus">
              <div :class="['rounded-xl p-3 text-center font-medium', bookingStatus.class]">
                {{ bookingStatus.text }}
              </div>
              <button
                  v-if="currentBooking.status === 'pending' || currentBooking.status === 'approved'"
                  @click="cancelBooking"
                  class="w-full mt-3 bg-rose-500 hover:bg-rose-600 text-white py-2.5 rounded-xl transition"
              >
                Отменить заявку
              </button>
            </div>

            <!-- Кнопка бронирования (если нет активной заявки и есть места) -->
            <button
                v-else-if="trip.status === 'active' && trip.available_seats > 0"
                @click="bookTrip"
                :disabled="bookingLoading"
                class="w-full bg-blue-600 hover:bg-blue-700 transition text-white py-3 rounded-xl font-medium disabled:opacity-50 shadow-sm"
            >
              {{ bookingLoading ? 'Отправка...' : 'Забронировать место' }}
            </button>

            <div v-else-if="trip.status === 'full'" class="bg-amber-100 text-amber-700 py-3 rounded-xl text-center font-medium">
              Свободных мест нет
            </div>

            <!-- Кнопка "Написать водителю" -->
            <button
                v-if="hasMyBooking(trip)"
                @click="startChat"
                class="w-full border border-slate-300 hover:bg-slate-50 transition py-3 rounded-xl font-medium text-slate-700"
            >
              💬 Написать водителю
            </button>
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
.sticky {
  position: sticky;
  top: 2rem;
}
@media (max-width: 640px) {
  .sticky {
    position: static;
  }
}
</style>