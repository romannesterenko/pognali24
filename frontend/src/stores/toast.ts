import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore(
    'toast',
    () => {

        const toasts = ref<any[]>([])

        const show = (
            toast: any
        ) => {

            const id = Date.now()

            toasts.value.push({
                id,
                ...toast,
            })

            setTimeout(() => {

                remove(id)

            }, 5000)
        }

        const remove = (
            id: number
        ) => {

            toasts.value =
                toasts.value.filter(
                    t => t.id !== id
                )
        }

        return {
            toasts,
            show,
            remove,
        }
    }
)