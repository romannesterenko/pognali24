import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'reverb',

    key: 'pognali24key',

    wsHost: window.location.hostname,
    wsPort: window.location.protocol === 'https:' ? 443 : 80,
    wssPort: window.location.protocol === 'https:' ? 443 : 80,

    forceTLS: window.location.protocol === 'https:',
    disableStats: true,

    authEndpoint: '/broadcasting/auth',

    auth: {
        headers: {
            get Authorization() {
                return `Bearer ${localStorage.getItem('token')}`
            },
        },
    },
})

export default echo