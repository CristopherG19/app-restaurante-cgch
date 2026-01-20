import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.rol === 'admin')

  async function login(credentials) {
    loading.value = true
    error.value = null
    
    try {
      const { data } = await authApi.login(credentials)
      
      if (data.success) {
        token.value = data.data.token
        user.value = data.data.user
        localStorage.setItem('token', data.data.token)
        return true
      } else {
        error.value = data.message
        return false
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error de conexión'
      return false
    } finally {
      loading.value = false
    }
  }

  async function checkAuth() {
    if (!token.value) return false
    
    try {
      const { data } = await authApi.me()
      if (data.success) {
        user.value = data.data
        return true
      }
    } catch {
      logout()
    }
    return false
  }

  function logout() {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    login,
    checkAuth,
    logout
  }
})
