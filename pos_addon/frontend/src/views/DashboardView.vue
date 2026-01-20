<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Sales Today -->
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Ventas de Hoy</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.ventas_hoy?.total_formato || 'S/ 0.00' }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ stats.ventas_hoy?.cantidad || 0 }} ventas</p>
          </div>
          <div class="w-12 h-12 bg-accent-100 rounded-xl flex items-center justify-center">
            <Icon name="dollar" :size="24" class="text-accent-600" />
          </div>
        </div>
      </div>

      <!-- Active Orders -->
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Comandas Activas</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.comandas?.activas || 0 }}</p>
            <p class="text-sm text-amber-600 mt-1">{{ stats.comandas?.en_cocina || 0 }} en cocina</p>
          </div>
          <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
            <Icon name="clipboard" :size="24" class="text-amber-600" />
          </div>
        </div>
      </div>

      <!-- Tables -->
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Mesas</p>
            <p class="text-2xl font-bold text-gray-900">
              {{ stats.mesas?.ocupadas || 0 }}/{{ stats.mesas?.total || 0 }}
            </p>
            <p class="text-sm text-accent-600 mt-1">{{ stats.mesas?.libres || 0 }} disponibles</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <Icon name="utensils" :size="24" class="text-blue-600" />
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Alertas</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.alertas?.stock_bajo || 0 }}</p>
            <p class="text-sm text-red-600 mt-1">Stock bajo</p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
            <Icon name="alert" :size="24" class="text-red-600" />
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <router-link to="/pos" class="card-hover text-center p-6 group">
        <div class="w-14 h-14 bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-primary-200 transition-colors">
          <Icon name="cart" :size="28" class="text-primary-600" />
        </div>
        <p class="font-medium text-gray-800">Nueva Venta</p>
      </router-link>

      <router-link to="/mesas" class="card-hover text-center p-6 group">
        <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-blue-200 transition-colors">
          <Icon name="utensils" :size="28" class="text-blue-600" />
        </div>
        <p class="font-medium text-gray-800">Ver Mesas</p>
      </router-link>

      <router-link to="/cocina" class="card-hover text-center p-6 group">
        <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-amber-200 transition-colors">
          <Icon name="chef" :size="28" class="text-amber-600" />
        </div>
        <p class="font-medium text-gray-800">Cocina</p>
      </router-link>

      <router-link to="/caja" class="card-hover text-center p-6 group">
        <div class="w-14 h-14 bg-accent-100 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-accent-200 transition-colors">
          <Icon name="wallet" :size="28" class="text-accent-600" />
        </div>
        <p class="font-medium text-gray-800">Caja</p>
      </router-link>
    </div>

    <!-- Recent Activity & Top Products -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Top Products -->
      <div class="card">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
          <Icon name="trending" :size="20" class="text-primary-500" />
          Productos Más Vendidos
        </h3>
        <div class="space-y-3">
          <div 
            v-for="(product, index) in topProducts" 
            :key="product.id"
            class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg"
          >
            <span class="text-lg font-bold text-gray-400 w-6">{{ index + 1 }}</span>
            <div class="flex-1">
              <p class="font-medium text-gray-800">{{ product.nombre }}</p>
              <p class="text-sm text-gray-500">{{ product.cantidad_vendida }} vendidos</p>
            </div>
            <span class="font-semibold text-primary-600">
              S/ {{ parseFloat(product.total_vendido).toFixed(2) }}
            </span>
          </div>
          <p v-if="topProducts.length === 0" class="text-center text-gray-500 py-4">
            No hay datos disponibles
          </p>
        </div>
      </div>

      <!-- Kitchen Orders -->
      <div class="card">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
          <Icon name="chef" :size="20" class="text-amber-500" />
          Pedidos en Cocina
        </h3>
        <div class="space-y-3">
          <div 
            v-if="stats.comandas?.en_cocina > 0"
            class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-center"
          >
            <p class="text-3xl font-bold text-amber-600">{{ stats.comandas?.en_cocina }}</p>
            <p class="text-amber-700">pedidos en preparación</p>
          </div>
          <div 
            v-if="stats.comandas?.listas > 0"
            class="p-4 bg-accent-50 border border-accent-200 rounded-lg text-center"
          >
            <p class="text-3xl font-bold text-accent-600">{{ stats.comandas?.listas }}</p>
            <p class="text-accent-700">listos para entregar</p>
          </div>
          <div 
            v-if="!stats.comandas?.en_cocina && !stats.comandas?.listas"
            class="p-8 text-center text-gray-500"
          >
            <Icon name="sparkles" :size="40" class="mx-auto mb-2 text-gray-300" />
            <p class="mt-2">Sin pedidos pendientes</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { dashboardApi } from '@/services/api'
import Icon from '@/components/ui/Icon.vue'

const stats = ref({})
const topProducts = ref([])
const loading = ref(true)

async function fetchData() {
  try {
    const [resumenRes, productosRes] = await Promise.all([
      dashboardApi.getResumen(),
      dashboardApi.getProductosTop()
    ])
    
    if (resumenRes.data.success) {
      stats.value = resumenRes.data.data
    }
    
    if (productosRes.data.success) {
      topProducts.value = productosRes.data.data.slice(0, 5)
    }
  } catch (error) {
    console.error('Error fetching dashboard data:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>
