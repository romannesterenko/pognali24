import axios from 'axios'

const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    withXSRFToken: true,
})

api.interceptors.response.use(

    response => response,

    async error => {

        return Promise.reject(error)
    }
)
export default api