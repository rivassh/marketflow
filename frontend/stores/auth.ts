import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  mobile: string
  email?: string
}

interface AuthState {
  user: User | null
  token: string | null
  loading: boolean
  error: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    loading: false,
    error: null,
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    currentUser: (state) => state.user,
  },
  
  actions: {
    async register(credentials: { name: string; mobile: string; password: string; password_confirmation: string }) {
      this.loading = true
      this.error = null
      
      try {
        const config = useRuntimeConfig()
        const response = await $fetch<{ user: User; message: string }>('/auth/register', {
          baseURL: config.public.apiBase,
          method: 'POST',
          body: credentials,
        })
        
        return response
      } catch (err: any) {
        this.error = err.data?.message || 'خطا در ایجاد حساب کاربری'
        throw err
      } finally {
        this.loading = false
      }
    },
    
    async login(credentials: { mobile: string; password: string }) {
      this.loading = true
      this.error = null
      
      try {
        const config = useRuntimeConfig()
        const response = await $fetch<{ user: User; access_token: string }>('/auth/login', {
          baseURL: config.public.apiBase,
          method: 'POST',
          body: credentials,
        })
        
        this.user = response.user
        this.token = response.access_token
        
        // Store token in localStorage for persistence
        if (import.meta.client) {
          localStorage.setItem('auth_token', response.access_token)
        }
        
        return response
      } catch (err: any) {
        this.error = err.data?.message || 'خطا در ورود به حساب'
        throw err
      } finally {
        this.loading = false
      }
    },
    
    async logout() {
      if (this.token) {
        try {
          const config = useRuntimeConfig()
          await $fetch('/auth/logout', {
            baseURL: config.public.apiBase,
            method: 'POST',
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          })
        } catch {
          // Ignore logout errors
        }
      }
      
      this.user = null
      this.token = null
      
      if (import.meta.client) {
        localStorage.removeItem('auth_token')
      }
    },
    
    async fetchUser() {
      if (!this.token) return null
      
      try {
        const config = useRuntimeConfig()
        const response = await $fetch<User>('/auth/user', {
          baseURL: config.public.apiBase,
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        })
        
        this.user = response
        return response
      } catch {
        this.logout()
        return null
      }
    },
    
    initAuth() {
      if (import.meta.client) {
        const token = localStorage.getItem('auth_token')
        if (token) {
          this.token = token
          this.fetchUser()
        }
      }
    },
  },
})
