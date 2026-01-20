<template>
  <div class="min-h-screen flex overflow-x-hidden">
    <!-- Sidebar -->
    <aside 
      class="fixed left-0 top-0 h-full bg-white shadow-lg z-40 transition-all duration-300"
      :class="sidebarOpen ? 'w-64' : 'w-20'"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center justify-center border-b border-gray-100">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
            <Icon name="chef" :size="24" class="text-primary-600" />
          </div>
          <span v-if="sidebarOpen" class="font-bold text-xl text-gray-800">Over Chef</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="p-4 space-y-2">
        <router-link 
          v-for="item in menuItems" 
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
          :class="{ 'bg-primary-50 text-primary-600': $route.path === item.path }"
        >
          <Icon :name="item.icon" :size="20" />
          <span v-if="sidebarOpen" class="font-medium">{{ item.label }}</span>
        </router-link>
      </nav>

      <!-- Toggle button -->
      <button 
        @click="sidebarOpen = !sidebarOpen"
        class="absolute -right-3 top-20 w-6 h-6 bg-white shadow rounded-full flex items-center justify-center text-gray-400 hover:text-gray-600"
      >
        <Icon :name="sidebarOpen ? 'chevron-left' : 'chevron-right'" :size="14" />
      </button>
    </aside>

    <!-- Main content -->
    <div 
      class="flex-1 min-w-0 transition-all duration-300" 
      :class="sidebarOpen ? 'ml-64' : 'ml-20'"
      :style="{ maxWidth: sidebarOpen ? 'calc(100vw - 256px)' : 'calc(100vw - 80px)' }"
    >
      <!-- Header -->
      <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 sticky top-0 z-30">
        <div class="flex items-center gap-4">
          <h1 class="text-lg font-semibold text-gray-800">{{ currentPageTitle }}</h1>
        </div>

        <div class="flex items-center gap-4">
          <!-- Cash session indicator -->
          <div 
            v-if="cashSession" 
            class="flex items-center gap-2 px-3 py-1.5 bg-accent-50 text-accent-700 rounded-lg text-sm"
          >
            <Icon name="check-circle" :size="16" />
            <span>Caja abierta</span>
          </div>
          <div 
            v-else 
            class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-sm cursor-pointer hover:bg-amber-100"
            @click="$router.push('/caja')"
          >
            <Icon name="alert" :size="16" />
            <span>Sin caja</span>
          </div>

          <!-- User menu -->
          <div class="relative">
            <button 
              @click="userMenuOpen = !userMenuOpen"
              class="flex items-center gap-3 hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors"
            >
              <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                <span class="text-primary-600 font-semibold">{{ userInitials }}</span>
              </div>
              <div v-if="sidebarOpen" class="text-left">
                <p class="text-sm font-medium text-gray-800">{{ authStore.user?.nombre }}</p>
                <p class="text-xs text-gray-500 capitalize">{{ authStore.user?.rol }}</p>
              </div>
            </button>

            <!-- Dropdown -->
            <div 
              v-if="userMenuOpen" 
              class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2"
            >
              <button 
                @click="handleLogout"
                class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2"
              >
                <Icon name="logout" :size="18" />
                Cerrar sesión
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6 overflow-x-hidden">
        <router-view />
      </main>
    </div>

    <!-- Click outside to close menu -->
    <div 
      v-if="userMenuOpen" 
      class="fixed inset-0 z-20"
      @click="userMenuOpen = false"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { cajaApi } from '@/services/api'
import Icon from '@/components/ui/Icon.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const sidebarOpen = ref(true)
const userMenuOpen = ref(false)
const cashSession = ref(null)

const menuItems = computed(() => {
  const items = [
    { path: '/', icon: 'dashboard', label: 'Dashboard' },
    { path: '/pos', icon: 'cart', label: 'Punto de Venta' },
    { path: '/mesas', icon: 'utensils', label: 'Mesas' },
    { path: '/cocina', icon: 'chef', label: 'Cocina' },
    { path: '/caja', icon: 'wallet', label: 'Caja' },
    { path: '/ventas', icon: 'receipt', label: 'Ventas' }
  ]
  
  if (authStore.isAdmin) {
    items.push(
      { path: '/productos', icon: 'package', label: 'Productos' },
      { path: '/configuracion', icon: 'settings', label: 'Configuración' }
    )
  }
  
  return items
})

const currentPageTitle = computed(() => {
  const item = menuItems.value.find(i => i.path === route.path)
  return item?.label || 'Over Chef POS'
})

const userInitials = computed(() => {
  const name = authStore.user?.nombre || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

async function checkCashSession() {
  try {
    const { data } = await cajaApi.getActual()
    if (data.success) {
      cashSession.value = data.data
    }
  } catch (error) {
    console.error('Error checking cash session:', error)
  }
}

function handleLogout() {
  authStore.logout()
  router.push('/login')
}

onMounted(() => {
  checkCashSession()
})
</script>
