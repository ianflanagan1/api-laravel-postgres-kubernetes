import axios from 'axios'
import type { AxiosError, AxiosInstance, InternalAxiosRequestConfig } from 'axios'
import { useAuthStore } from '@/stores/auth'

const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Add token to request (if present)
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const method: string = config.method?.toUpperCase() || ''
    const token: string | null = useAuthStore().getToken()

    // Add authorisation token if it exists
    if (token) {
      config.headers = config.headers || {}
      config.headers.Authorization = `Bearer ${token}`
    }

    // Set Content-Type header for methods with body
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      config.headers['Content-Type'] = 'application/json'
    }

    return config
  },
  (error: AxiosError) => {
    return Promise.reject(error)
  },
)

export default api
