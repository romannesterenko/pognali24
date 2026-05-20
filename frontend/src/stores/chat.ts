import { defineStore } from 'pinia'
import api from '@/api/axios'
import echo from '@/lib/echo'
import { useAuthStore } from '@/stores/auth'

export const useChatStore = defineStore('chat', {

    state: () => ({
        unreadConversations: 0,
        listening: false,
    }),

    actions: {

        async fetchUnread() {

            console.log('fetchUnread');

            try {

                const res = await api.get('/conversations/unread-count')

                this.unreadConversations = res.data.count

            } catch (e) {

                console.error('Ошибка загрузки unread dialogs', e)

            }

        },

        setUnread(count: number) {

            this.unreadConversations = count

        },

        listen() {

            // защита от повторной подписки
            if (this.listening) {
                return
            }
            this.listening = true

            const auth = useAuthStore()

            const userId = auth.user?.id

            if (!userId) {
                return
            }

            echo
                .private(`user.${userId}`)
                .listen('.message.sent', () => {
                    console.log('listen', userId);
                    this.fetchUnread()

                })

        },

    }

})