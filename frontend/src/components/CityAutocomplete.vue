<template>
  <div class="autocomplete relative">
    <input
        type="text"
        v-model="search"
        @input="onInput"
        @keydown.down="onArrowDown"
        @keydown.up="onArrowUp"
        @keydown.enter.prevent="onEnter"
        :placeholder="placeholder"
        class="w-full border rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
        :class="inputClass"
        autocomplete="off"
    />

    <ul
        v-if="isOpen && suggestions.length"
        class="absolute z-50 w-full bg-white border border-gray-300 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
    >
      <li
          v-for="(suggestion, index) in suggestions"
          :key="suggestion.data.fias_id"
          @click="selectSuggestion(suggestion)"
          @mouseenter="currentIndex = index"
          class="px-4 py-2 cursor-pointer transition-colors"
          :class="{
          'bg-blue-100': currentIndex === index,
          'hover:bg-gray-100': currentIndex !== index,
        }"
      >
        {{ reverseByCommas(suggestion.unrestricted_value) }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/api/axios.js'
import { debounce } from 'lodash'

const props = defineProps({
  modelValue: {
    type: [String, Number, null],
    default: null,
  },

  placeholder: {
    type: String,
    default: '',
  },

  inputClass: {
    type: [String, Object, Array],
    default: '',
  },

  /**
   * Если нужно передать стартовый текст
   */
  initialText: {
    type: String,
    default: '',
  },
})

const emit = defineEmits([
  'update:modelValue',
  'selected',
])

/**
 * Текст в input
 */
const search = ref(props.initialText)

/**
 * Подсказки
 */
const suggestions = ref([])

const isOpen = ref(false)
const systemLocationId = ref(false)

const currentIndex = ref(-1)

/**
 * Загрузка подсказок
 */
const fetchSuggestions = async (query) => {
  if (!query || query.length < 2) {
    suggestions.value = []
    isOpen.value = false
    return
  }

  try {
    const response = await api.post('/cities/suggest', {
      query,
    })

    suggestions.value = response.data?.items || []

    isOpen.value = suggestions.value.length > 0

    currentIndex.value = -1
  } catch (error) {
    console.error('Ошибка при загрузке подсказок:', error)

    suggestions.value = []

    isOpen.value = false
  }
}

/**
 * Сохраняем локацию
 */
const addLocation = async (suggestion) => {
  try {
    await api.post('/cities', {
      value: suggestion.value,
      data: suggestion.data,
    })
  } catch (error) {
    console.error('Ошибка при добавлении локации:', error)
  }
}

const debouncedFetch = debounce(fetchSuggestions, 300)

/**
 * Ввод текста
 */
const onInput = (event) => {
  const value = event.target.value

  search.value = value

  /**
   * Пока пользователь печатает —
   * выбранной локации нет
   */
  emit('update:modelValue', null)

  debouncedFetch(value)
}

/**
 * Выбор подсказки
 */
const selectSuggestion = (suggestion) => {
  /**
   * Показываем текст
   */
  search.value = reverseByCommas(
      suggestion.unrestricted_value
  )
  addLocation(suggestion);

  /**
   * Наружу отправляем fias_id
   */
  emit(
      'update:modelValue',
      suggestion.data.fias_id
  )

  /**
   * Дополнительно можем отдать
   * весь объект
   */
  emit('selected', suggestion)

  suggestions.value = []

  isOpen.value = false


}

/**
 * Красивый формат адреса
 */
const reverseByCommas = (str) => {
  return str
      .split(',')
      .map((s) => s.trim())
      .reverse()
      .join(', ')
}

/**
 * Навигация вниз
 */
const onArrowDown = () => {
  if (!suggestions.value.length) return

  currentIndex.value =
      (currentIndex.value + 1) %
      suggestions.value.length
}

/**
 * Навигация вверх
 */
const onArrowUp = () => {
  if (!suggestions.value.length) return

  currentIndex.value =
      (currentIndex.value - 1 + suggestions.value.length) %
      suggestions.value.length
}

/**
 * Enter
 */
const onEnter = () => {
  if (
      currentIndex.value >= 0 &&
      suggestions.value[currentIndex.value]
  ) {
    selectSuggestion(
        suggestions.value[currentIndex.value]
    )
  }
}
</script>

<style scoped>
.autocomplete {
  width: 100%;
}
</style>