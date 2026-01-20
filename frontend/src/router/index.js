import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/DashboardView.vue')
      },
      {
        path: 'pos',
        name: 'POS',
        component: () => import('@/views/POSView.vue')
      },
      {
        path: 'mesas',
        name: 'Mesas',
        component: () => import('@/views/TablesView.vue')
      },
      {
        path: 'cocina',
        name: 'Cocina',
        component: () => import('@/views/KitchenView.vue'),
        meta: { roles: ['admin', 'cocina'] }
      },
      {
        path: 'caja',
        name: 'Caja',
        component: () => import('@/views/CashView.vue'),
        meta: { roles: ['admin', 'cajero'] }
      },
      {
        path: 'productos',
        name: 'Productos',
        component: () => import('@/views/ProductsView.vue'),
        meta: { roles: ['admin'] }
      },
      {
        path: 'configuracion',
        name: 'Configuracion',
        component: () => import('@/views/ConfigView.vue'),
        meta: { roles: ['admin'] }
      },
      {
        path: 'ventas',
        name: 'Ventas',
        component: () => import('@/views/SalesView.vue'),
        meta: { roles: ['admin', 'cajero'] }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next('/')
  } else if (to.meta.roles && !to.meta.roles.includes(authStore.user?.rol)) {
    next('/')
  } else {
    next()
  }
})

export default router
