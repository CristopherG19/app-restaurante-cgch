<template>
  <div class="h-[calc(100vh-7rem)]">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
          <Icon name="chef" :size="28" class="text-amber-500" />
          Pantalla de Cocina (KDS)
        </h2>
        <p class="text-gray-500">
          Última actualización: {{ lastUpdate }}
          <span class="ml-2 inline-flex items-center gap-1">
            <span class="w-2 h-2 bg-accent-500 rounded-full animate-pulse"></span>
            Auto-refresh cada 5s
          </span>
        </p>
      </div>
      
      <button @click="fetchKitchen" class="btn-secondary">
        <Icon name="refresh" :size="18" />
        Actualizar
      </button>
    </div>

    <!-- Kanban Board -->
    <div class="grid grid-cols-3 gap-6 h-full">
      <!-- Pending Column -->
      <div class="flex flex-col">
        <div class="flex items-center gap-2 mb-4 px-4 py-2 bg-amber-100 rounded-xl">
          <Icon name="clock" :size="20" class="text-amber-700" />
          <h3 class="font-bold text-amber-800">Pendientes</h3>
          <span class="ml-auto bg-amber-500 text-white text-sm font-bold px-2 py-0.5 rounded-full">
            {{ ordersStore.kitchenGrouped.pendientes.length }}
          </span>
        </div>
        
        <div class="flex-1 overflow-y-auto space-y-3 scrollbar-thin">
          <OrderCard 
            v-for="item in ordersStore.kitchenGrouped.pendientes"
            :key="item.item_id"
            :item="item"
            status="pendiente"
            @action="moveToPreparando"
          />
          
          <p v-if="ordersStore.kitchenGrouped.pendientes.length === 0" class="text-center text-gray-400 py-8">
            Sin pedidos pendientes
          </p>
        </div>
      </div>

      <!-- Preparing Column -->
      <div class="flex flex-col">
        <div class="flex items-center gap-2 mb-4 px-4 py-2 bg-blue-100 rounded-xl">
          <Icon name="flame" :size="20" class="text-blue-700" />
          <h3 class="font-bold text-blue-800">Preparando</h3>
          <span class="ml-auto bg-blue-500 text-white text-sm font-bold px-2 py-0.5 rounded-full">
            {{ ordersStore.kitchenGrouped.preparando.length }}
          </span>
        </div>
        
        <div class="flex-1 overflow-y-auto space-y-3 scrollbar-thin">
          <OrderCard 
            v-for="item in ordersStore.kitchenGrouped.preparando"
            :key="item.item_id"
            :item="item"
            status="preparando"
            @action="moveToListo"
          />
          
          <p v-if="ordersStore.kitchenGrouped.preparando.length === 0" class="text-center text-gray-400 py-8">
            Nada preparándose
          </p>
        </div>
      </div>

      <!-- Ready Column -->
      <div class="flex flex-col">
        <div class="flex items-center gap-2 mb-4 px-4 py-2 bg-accent-100 rounded-xl">
          <Icon name="check-circle" :size="20" class="text-accent-700" />
          <h3 class="font-bold text-accent-800">Listos</h3>
          <span class="ml-auto bg-accent-500 text-white text-sm font-bold px-2 py-0.5 rounded-full">
            {{ ordersStore.kitchenGrouped.listos.length }}
          </span>
        </div>
        
        <div class="flex-1 overflow-y-auto space-y-3 scrollbar-thin">
          <OrderCard 
            v-for="item in ordersStore.kitchenGrouped.listos"
            :key="item.item_id"
            :item="item"
            status="listo"
            @action="moveToEntregado"
          />
          
          <p v-if="ordersStore.kitchenGrouped.listos.length === 0" class="text-center text-gray-400 py-8">
            Sin platos listos
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useOrdersStore } from '@/stores/orders'
import OrderCard from '@/components/kitchen/OrderCard.vue'
import Icon from '@/components/ui/Icon.vue'

const ordersStore = useOrdersStore()

const lastUpdate = ref('')
let pollInterval = null

async function fetchKitchen() {
  await ordersStore.fetchKitchen()
  lastUpdate.value = new Date().toLocaleTimeString('es-PE')
}

async function moveToPreparando(itemId) {
  await ordersStore.updateItemStatus(itemId, 'preparando')
}

async function moveToListo(itemId) {
  await ordersStore.updateItemStatus(itemId, 'listo')
}

async function moveToEntregado(itemId) {
  await ordersStore.updateItemStatus(itemId, 'entregado')
}

onMounted(() => {
  fetchKitchen()
  // Auto-refresh every 5 seconds
  pollInterval = setInterval(fetchKitchen, 5000)
})

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }
})
</script>
