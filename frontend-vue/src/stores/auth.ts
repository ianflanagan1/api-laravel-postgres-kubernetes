import { defineStore } from 'pinia'

const TOKEN_KEY = 'token'

let storedToken: string | null = null

try {
  storedToken = localStorage.getItem(TOKEN_KEY)
} catch (error) {
  console.warn('localStorage unavailable:', error)
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: storedToken as string | null,
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
  },

  actions: {
    getToken(): string | null {
      return this.token
    },

    setToken(token: string): void {
      this.token = token
      try {
        localStorage.setItem(TOKEN_KEY, token)
      } catch (error) {
        console.error('Failed to persist token:', error)
        // fallback to in-memory only
      }
    },

    clearToken(): void {
      this.token = null
      try {
        localStorage.removeItem(TOKEN_KEY)
      } catch (error) {
        console.error('Failed to remove token:', error)
      }
    },
  },
})
