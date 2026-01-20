import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Request interceptor - add token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout()
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

// API methods
export const authApi = {
  login: (credentials) => api.post('/auth/login', credentials),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  refresh: () => api.post('/auth/refresh')
}

export const productosApi = {
  getAll: (params) => api.get('/productos', { params }),
  getOne: (id) => api.get(`/productos/${id}`),
  create: (data) => api.post('/productos', data),
  update: (id, data) => api.put(`/productos/${id}`, data),
  delete: (id) => api.delete(`/productos/${id}`)
}

export const categoriasApi = {
  getAll: () => api.get('/categorias'),
  getOne: (id) => api.get(`/categorias/${id}`)
}

export const clientesApi = {
  getAll: (params) => api.get('/clientes', { params }),
  buscar: (documento) => api.get(`/clientes/buscar/${documento}`),
  create: (data) => api.post('/clientes', data)
}

export const mesasApi = {
  getAll: (params) => api.get('/mesas', { params }),
  getOne: (id) => api.get(`/mesas/${id}`),
  updateEstado: (id, estado) => api.put(`/mesas/${id}/estado`, { estado })
}

export const zonasApi = {
  getAll: () => api.get('/zonas')
}

export const comandasApi = {
  getAll: (params) => api.get('/comandas', { params }),
  getOne: (id) => api.get(`/comandas/${id}`),
  getItems: (id) => api.get(`/comandas/${id}/items`),
  getCocina: () => api.get('/comandas/cocina'),
  create: (data) => api.post('/comandas', data),
  addItem: (id, data) => api.post(`/comandas/${id}/items`, data),
  enviarCocina: (id) => api.put(`/comandas/${id}/enviar-cocina`),
  updateEstado: (id, estado) => api.put(`/comandas/${id}/estado`, { estado }),
  updateItemEstado: (itemId, estado) => api.put(`/comandas/${itemId}/item-estado`, { estado })
}

export const ventasApi = {
  getAll: (params) => api.get('/ventas', { params }),
  getOne: (id) => api.get(`/ventas/${id}`),
  create: (data) => api.post('/ventas', data),
  crearDesdeComanda: (data) => api.post('/ventas/desde-comanda', data),
  getTicket: (id) => api.get(`/ventas/${id}/ticket`),
  getPDF: (id) => `${API_URL}/ventas/${id}/pdf`, // Return URL for PDF
  anular: (id) => api.put(`/ventas/${id}/anular`)
}


export const cajaApi = {
  getActual: () => api.get('/caja/actual'),
  getResumen: (id) => api.get(`/caja/${id}/resumen`),
  abrir: (data) => api.post('/caja/abrir', data),
  cerrar: (data) => api.put('/caja/cerrar', data)
}

export const dashboardApi = {
  getResumen: () => api.get('/dashboard/resumen'),
  getVentasHoy: () => api.get('/dashboard/ventas-hoy'),
  getProductosTop: () => api.get('/dashboard/productos-top')
}

export const configApi = {
  getAll: () => api.get('/configuracion'),
  getByGroup: (grupo) => api.get(`/configuracion/${grupo}`),
  update: (data) => api.put('/configuracion', data)
}
