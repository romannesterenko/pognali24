<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/api/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useToastStore } from '@/stores/toast'
import { useRouter } from 'vue-router'

const toast = useToastStore()
// Данные профиля
const profile = ref<any>(null)
const driverProfile = ref<any>(null)
const user = ref<any>({})
const cars = ref<any[]>([])

// Формы
const form = ref({
  birth_date: '',
  gender: '',
  about: '',
  city: '',
})

const driverForm = ref({
  about: '',
  experience: '',
})

const carForm = ref({
  brand: '',
  model: '',
  color: '',
  year: '',
  plate_number: '',
  seats: 4,
})

// UI состояния
const showCarForm = ref(false)
const editingCarId = ref<number | null>(null)
const avatarFile = ref<File | null>(null)

// Флаги загрузки
const profileLoading = ref(true)
const profileSaving = ref(false)
const driverProfileSaving = ref(false)
const avatarUploading = ref(false)
const becomingDriver = ref(false)
const carSaving = ref(false)
const carDeleting = ref<number | null>(null)

// Ошибки
const error = ref<string | null>(null)

// computed
const canBecomeDriver = computed(() => !driverProfile.value && !becomingDriver.value)
const isCarFormValid = computed(() => carForm.value.brand.trim() && carForm.value.model.trim() && carForm.value.plate_number.trim())


const router = useRouter()

const logoutLoading = ref(false)

const logout = async () => {
  logoutLoading.value = true

  try {

    await api.post('/logout')

    localStorage.removeItem('token')

    await router.push('/')

    window.location.reload()

  } catch (e) {

    console.error(e)

  } finally {

    logoutLoading.value = false

  }
}


// Загрузка всех данных профиля
const loadProfile = async () => {
  profileLoading.value = true
  error.value = null
  try {
    const res = await api.get('/profile')
    user.value = res.data.user
    profile.value = res.data.user.profile
    driverProfile.value = res.data.user.driver_profile
    cars.value = res.data.user.cars || []

    form.value = {
      birth_date: profile.value?.birth_date || '',
      gender: profile.value?.gender || '',
      about: profile.value?.about || '',
      city: profile.value?.city || '',
    }

    if (driverProfile.value) {
      driverForm.value = {
        about: driverProfile.value?.about || '',
        experience: driverProfile.value?.experience || '',
      }
    }
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Не удалось загрузить профиль',
    })
    error.value = e.response?.data?.message || 'Не удалось загрузить профиль'
    console.error(e)
  } finally {
    profileLoading.value = false
  }
}

// Сохранение личных данных
const saveProfile = async () => {
  profileSaving.value = true
  try {
    await api.post('/profile', form.value)
    await loadProfile()
    toast.show({
      type: 'success',
      title: 'Успешно',
      message: 'Профиль обновлён',
    })
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка при сохранении профиля',
    })
  } finally {
    profileSaving.value = false
  }
}

// Сохранение профиля водителя
const saveDriverProfile = async () => {
  driverProfileSaving.value = true
  try {
    await api.post('/driver-profile', driverForm.value)
    await loadProfile()
    toast.show({
      type: 'success',
      title: 'Успешно',
      message: 'Профиль водителя обновлён',
    })
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка при сохранении профиля водителя',
    })
  } finally {
    driverProfileSaving.value = false
  }
}

// Стать водителем
const becomeDriver = async () => {
  becomingDriver.value = true
  try {
    await api.post('/driver-profile/create')
    await loadProfile()
    toast.show({
      type: 'success',
      title: 'Теперь вы водитель!',
      message: 'Заполните дополнительную информацию.',
    })
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка при активации водителя',
    })
  } finally {
    becomingDriver.value = false
  }
}

// Загрузка аватара
const uploadAvatar = async () => {
  if (!avatarFile.value) return
  avatarUploading.value = true
  const formData = new FormData()
  formData.append('avatar', avatarFile.value)
  try {
    await api.post('/profile/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    await loadProfile()
    toast.show({
      type: 'success',
      title: 'Успешно',
      message: 'Аватар обновлён',
    })
    const fileInput = document.getElementById('avatar-input') as HTMLInputElement
    if (fileInput) fileInput.value = ''
    avatarFile.value = null
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка при загрузке аватара',
    })
  } finally {
    avatarUploading.value = false
  }
}

// Сохранение автомобиля
const saveCar = async () => {
  if (!isCarFormValid.value) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: 'Заполните марку, модель и номер автомобиля',
    })
    return
  }
  carSaving.value = true
  try {
    if (editingCarId.value) {
      await api.post(`/cars/${editingCarId.value}`, carForm.value)
    } else {
      await api.post('/cars', carForm.value)
    }
    carForm.value = {
      brand: '',
      model: '',
      color: '',
      year: '',
      plate_number: '',
      seats: 4,
    }
    editingCarId.value = null
    showCarForm.value = false
    await loadProfile()
    toast.show({
      type: 'success',
      title: 'Успешно',
      message: 'Автомобиль сохранён',
    })
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка при сохранении автомобиля',
    })
  } finally {
    carSaving.value = false
  }
}

// Удаление автомобиля
const deleteCar = async (id: number) => {
  if (!confirm('Удалить автомобиль?')) return
  carDeleting.value = id
  try {
    await api.delete(`/cars/${id}`)
    await loadProfile()
    toast.show({
      type: 'success',
      title: 'Успешно',
      message: 'Автомобиль удалён',
    })
  } catch (e: any) {
    toast.show({
      type: 'error',
      title: 'Ошибка',
      message: e.response?.data?.message || 'Ошибка при удалении',
    })
  } finally {
    carDeleting.value = null
  }
}

// Редактирование автомобиля
const editCar = (car: any) => {
  carForm.value = { ...car }
  editingCarId.value = car.id
  showCarForm.value = true
}

// Отмена формы авто
const cancelCarForm = () => {
  showCarForm.value = false
  editingCarId.value = null
  carForm.value = {
    brand: '',
    model: '',
    color: '',
    year: '',
    plate_number: '',
    seats: 4,
  }
}

onMounted(loadProfile)
</script>

<template>
  <AuthLayout>
    <div class="max-w-6xl mx-auto px-4 py-8">
      <!-- Глобальная загрузка -->
      <div v-if="profileLoading" class="flex justify-center py-20">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <!-- Ошибка загрузки -->
      <div v-else-if="error" class="text-center py-10">
        <div class="text-red-500 text-lg">{{ error }}</div>
        <button @click="loadProfile" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition">
          Повторить
        </button>
      </div>

      <!-- Данные профиля -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Левая колонка: аватар и автомобили -->
        <div class="space-y-6">
          <!-- Карточка аватара -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col items-center">
              <div class="w-36 h-36 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-slate-100 border-4 border-white shadow-md mb-4">
                <img
                    v-if="profile?.avatar_url"
                    :src="profile.avatar_url"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-5xl font-bold text-slate-400">
                  {{ user?.name?.[0] || '?' }}
                </div>
              </div>
              <h1 class="text-2xl font-bold text-slate-900">{{ user.name }}</h1>
              <p class="text-slate-500 mt-1 text-sm">Пользователь Pognali24</p>
              <div v-if="driverProfile" class="mt-3 inline-flex items-center gap-1 bg-emerald-100 text-emerald-700 text-sm px-4 py-2 rounded-full">
                🚗 Водитель активирован
              </div>
            </div>

            <div class="mt-6">
              <input
                  id="avatar-input"
                  type="file"
                  accept="image/*"
                  class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                  @change="e => avatarFile = (e.target as HTMLInputElement).files?.[0] || null"
              />
              <button
                  @click="uploadAvatar"
                  :disabled="avatarUploading || !avatarFile"
                  class="mt-3 w-full bg-slate-800 hover:bg-slate-900 transition text-white py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed font-medium"
              >
                {{ avatarUploading ? 'Загрузка...' : 'Загрузить аватар' }}
              </button>
              <button
                  @click="logout"
                  :disabled="logoutLoading"
                  class="mt-3 w-full border border-red-200 text-red-600 hover:bg-red-50 transition py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed font-medium"
              >
                {{ logoutLoading ? 'Выход...' : 'Выйти из аккаунта' }}
              </button>
            </div>
          </div>

          <!-- Блок "Стать водителем" -->
          <div
              v-if="!driverProfile"
              class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl p-6 shadow-md"
          >
            <h2 class="text-2xl font-bold">Станьте водителем</h2>
            <p class="mt-2 text-blue-100 leading-relaxed">
              Публикуйте поездки, находите пассажиров и зарабатывайте на свободных местах в автомобиле.
            </p>
            <button
                @click="becomeDriver"
                :disabled="becomingDriver"
                class="mt-6 bg-white text-blue-700 font-medium px-5 py-3 rounded-xl hover:bg-gray-100 transition disabled:opacity-50 w-full"
            >
              {{ becomingDriver ? 'Активация...' : 'Стать водителем' }}
            </button>
          </div>

          <!-- Список автомобилей (только для водителей) -->
          <div v-if="driverProfile" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
              <h2 class="text-xl font-bold text-slate-900">Мои автомобили</h2>
              <button
                  @click="showCarForm = true"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition"
              >
                + Добавить
              </button>
            </div>

            <div v-if="cars.length === 0" class="text-slate-500 text-center py-6">
              У вас пока нет автомобилей
            </div>

            <div class="space-y-3">
              <div
                  v-for="car in cars"
                  :key="car.id"
                  class="border border-slate-200 rounded-xl p-4 hover:shadow-sm transition"
              >
                <div class="flex flex-wrap justify-between items-start gap-2">
                  <div>
                    <div class="font-semibold text-slate-900">{{ car.brand }} {{ car.model }}</div>
                    <div class="text-sm text-slate-500 mt-1">
                      {{ car.year || 'Год не указан' }} • {{ car.color || 'Цвет не указан' }} • {{ car.seats }} мест
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Номер: {{ car.plate_number }}</div>
                  </div>
                  <div class="flex gap-3">
                    <button @click="editCar(car)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Изменить</button>
                    <button
                        @click="deleteCar(car.id)"
                        :disabled="carDeleting === car.id"
                        class="text-red-500 hover:text-red-700 text-sm font-medium disabled:opacity-50"
                    >
                      {{ carDeleting === car.id ? '...' : 'Удалить' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Форма добавления / редактирования авто -->
            <div v-if="showCarForm" class="mt-6 pt-4 border-t border-slate-100 space-y-3">
              <input v-model="carForm.brand" placeholder="Марка *" class="w-full border border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-blue-500" />
              <input v-model="carForm.model" placeholder="Модель *" class="w-full border border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-blue-500" />
              <input v-model="carForm.color" placeholder="Цвет" class="w-full border border-slate-200 rounded-xl p-3" />
              <input v-model="carForm.year" type="number" placeholder="Год выпуска" class="w-full border border-slate-200 rounded-xl p-3" />
              <input v-model="carForm.plate_number" placeholder="Номер *" class="w-full border border-slate-200 rounded-xl p-3" />
              <input v-model="carForm.seats" type="number" placeholder="Количество мест" class="w-full border border-slate-200 rounded-xl p-3" />
              <div class="flex gap-3 pt-2">
                <button
                    @click="saveCar"
                    :disabled="carSaving || !isCarFormValid"
                    class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl disabled:opacity-50 font-medium"
                >
                  {{ carSaving ? 'Сохранение...' : 'Сохранить' }}
                </button>
                <button @click="cancelCarForm" class="border border-slate-300 hover:bg-slate-50 px-5 py-2.5 rounded-xl transition">Отмена</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Правая колонка: основные настройки -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Личные данные -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="mb-6">
              <div class="flex items-center gap-2 mb-1">
                <span class="w-1 h-6 bg-blue-500 rounded-full"></span>
                <h2 class="text-2xl font-bold text-slate-900">Личные данные</h2>
              </div>
              <p class="text-slate-500 text-sm">Информация вашего профиля</p>
            </div>

            <div class="space-y-5">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Город</label>
                <input
                    v-model="form.city"
                    placeholder="Например, Москва"
                    class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Пол</label>
                <select
                    v-model="form.gender"
                    class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Не указан</option>
                  <option value="male">Мужской</option>
                  <option value="female">Женский</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Дата рождения</label>
                <input
                    v-model="form.birth_date"
                    type="date"
                    class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">О себе</label>
                <textarea
                    v-model="form.about"
                    rows="4"
                    placeholder="Расскажите немного о себе"
                    class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"
                />
              </div>

              <button
                  @click="saveProfile"
                  :disabled="profileSaving"
                  class="bg-blue-600 hover:bg-blue-700 transition text-white px-6 py-3 rounded-xl font-medium disabled:opacity-50"
              >
                {{ profileSaving ? 'Сохранение...' : 'Сохранить профиль' }}
              </button>
            </div>
          </div>

          <!-- Профиль водителя -->
          <div v-if="driverProfile" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="mb-6">
              <div class="flex items-center gap-2 mb-1">
                <span class="w-1 h-6 bg-emerald-500 rounded-full"></span>
                <h2 class="text-2xl font-bold text-slate-900">Профиль водителя</h2>
              </div>
              <p class="text-slate-500 text-sm">Информация для пассажиров</p>
            </div>

            <div class="space-y-5">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">О себе как о водителе</label>
                <textarea
                    v-model="driverForm.about"
                    rows="4"
                    placeholder="Стиль вождения, правила в салоне, дополнительные удобства..."
                    class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Стаж вождения (лет)</label>
                <input
                    v-model="driverForm.experience"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="например, 10"
                    class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <button
                  @click="saveDriverProfile"
                  :disabled="driverProfileSaving"
                  class="bg-slate-800 hover:bg-slate-900 transition text-white px-6 py-3 rounded-xl font-medium disabled:opacity-50"
              >
                {{ driverProfileSaving ? 'Сохранение...' : 'Сохранить профиль водителя' }}
              </button>
            </div>
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
textarea {
  resize: vertical;
}
</style>