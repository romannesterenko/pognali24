import axios from 'axios'
const url = window.location.hostname==='localhost'?'http://localhost':'';
const api = axios.create({
    baseURL: url + '/api/v1',
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