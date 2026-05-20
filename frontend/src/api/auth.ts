import api from './axios'

export const csrf = async () => {
    await api.get('/sanctum/csrf-cookie')
}

export const register = async (data: any) => {
    await csrf()

    return api.post('/register', data)
}

export const login = async (data: any) => {
    await csrf()

    return api.post('/login', data)
}

export const me = async () => {
    return api.get('/me')
}

export const logout = async () => {
    return api.post('/logout')
}