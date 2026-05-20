import { defineStore } from 'pinia'
import * as authApi from '@/api/auth'
import router from "@/router";

export const useAuthStore = defineStore('auth', {

    state: () => ({
        user: null as any,
        checked: false
    }),

    actions: {
        async fetchUser() {

            try {

                const res = await authApi.me()

                this.user = res.data.user

            } catch (e) {

                this.user = null

            } finally {

                this.checked = true
            }
        },

        async login(data: any) {
            const res = await authApi.login(data)

            localStorage.setItem('token', res.data.token)

            await this.fetchUser()

            router.push('/')
        },

        async register(data: any) {
            const res = await authApi.register(data)
            localStorage.setItem('token', res.data.token)

            await this.fetchUser()

            router.push('/')
        },

        async logout() {

            await authApi.logout()

            localStorage.removeItem('token')

            this.user = null

        }
    }
})