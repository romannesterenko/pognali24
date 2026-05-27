import { createRouter, createWebHistory } from 'vue-router'

import LoginPage from '@/pages/auth/LoginPage.vue'
import RegisterPage from '@/pages/auth/RegisterPage.vue'
import DashboardPage from '@/pages/dashboard/DashboardPage.vue'
import ProfilePage from '@/pages/profile/ProfilePage.vue'
import DriverTripsPage from '@/pages/driver/TripsPage.vue'
import SearchTripsPage from '@/pages/SearchTripsPage.vue'
import TripPage from '@/pages/TripPage.vue'
import MyBookingsPage from '@/pages/MyBookingsPage.vue'
import ConversationsPage from '@/pages/ConversationsPage.vue'
import ConversationPage from '@/pages/ConversationPage.vue'
import UserPage from '@/pages/users/Show.vue'


import { useAuthStore } from '@/stores/auth'
import Main from "@/pages/Main.vue";

const router = createRouter({
    history: createWebHistory(),

    routes: [
        {
            path: '/',
            component: Main,
        },

        {
            path: '/login',
            component: LoginPage,
            meta: {
                guest: true,
            }
        },

        {
            path: '/register',
            component: RegisterPage,
            meta: {
                guest: true,
            }
        },

        {
            path: '/profile',
            component: ProfilePage,
            meta: {
                requiresAuth: true,
            }
        },

        {
            path: '/driver/trips',
            component: DriverTripsPage,
            meta: {
                requiresAuth: true,
            }
        },

        {
            path: '/search',
            component: SearchTripsPage,
        },

        {
            path: '/trips/:id',
            component: TripPage,
        },

        {
            path: '/bookings',
            component: MyBookingsPage,
            meta: {
                requiresAuth: true,
            }
        },

        {
            path: '/conversations',
            component: ConversationsPage,
            meta: {
                requiresAuth: true,
            }
        },

        {
            path: '/conversations/:id',
            component: ConversationPage,
            meta: {
                requiresAuth: true,
            }
        },

        {
            path: '/users/:id',
            component: UserPage,
        },

        {
            path: '/notifications',
            component: () => import('@/pages/NotificationsPage.vue'),
            meta: {
                requiresAuth: true,
            },
        },

        {
            path: '/:pathMatch(.*)*',
            name: 'NotFound',
            component: () => import('@/pages/NotFoundPage.vue'),
        }

    ]
})

router.beforeEach(async (to) => {

    const auth = useAuthStore()

    const token = localStorage.getItem('token')

    /*
    |--------------------------------------------------------------------------
    | Нет токена
    |--------------------------------------------------------------------------
    */

    if (!token) {

        auth.user = null

        if (to.meta.requiresAuth) {
            return '/login'
        }

        return true
    }

    /*
    |--------------------------------------------------------------------------
    | Загружаем пользователя
    |--------------------------------------------------------------------------
    */

    if (!auth.user) {

        try {

            await auth.fetchUser()

        } catch (e) {

            localStorage.removeItem('token')

            auth.user = null

            if (to.meta.requiresAuth) {
                return '/login'
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guest pages
    |--------------------------------------------------------------------------
    */

    if (to.meta.guest && auth.user) {
        return '/'
    }

    /*
    |--------------------------------------------------------------------------
    | Protected pages
    |--------------------------------------------------------------------------
    */

    if (to.meta.requiresAuth && !auth.user) {
        return '/login'
    }

    return true
})
export default router