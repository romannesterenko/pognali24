import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'reverb',

    key: 'pognali24key',

    wsHost: 'localhost',
    wsPort: 8080,

    forceTLS: false,
    disableStats: true,

    authEndpoint: import.meta.env.VITE_API_URL + '/broadcasting/auth',

    auth: {
        headers: {
            get Authorization() {
                return `Bearer ${localStorage.getItem('token')}`
            },
        },
    },
})

export default echo