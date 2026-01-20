<template>
  <div class="space-y-6">
    <!-- Header with zones filter -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestión de Mesas</h2>
        <p class="text-gray-500">{{ tablesStore.mesasLibres }} disponibles de {{ tablesStore.mesas.length }}</p>
      </div>
      
      <div class="flex gap-2">
        <button 
          @click="tablesStore.filtroZona = null"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
          :class="!tablesStore.filtroZona 
            ? 'bg-primary-500 text-white' 
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
        >
          Todas
        </button>
        <button 
          v-for="zona in tablesStore.zonas"
          :key="zona.id"
          @click="tablesStore.filtroZona = zona.id"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
          :class="tablesStore.filtroZona === zona.id 
            ? 'text-white' 
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          :style="tablesStore.filtroZona === zona.id ? { backgroundColor: zona.color } : {}"
        >
          {{ zona.nombre }}
        </button>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex gap-6 text-sm">
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-accent-500"></div>
        <span>Libre</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-red-500"></div>
        <span>Ocupada</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-amber-500"></div>
        <span>Cuenta</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-blue-500"></div>
        <span>Reservada</span>
      </div>
    </div>

    <!-- Tables Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
      <div 
        v-for="mesa in tablesStore.mesasFiltradas"
        :key="mesa.id"
        @click="openMesa(mesa)"
        class="card-hover cursor-pointer relative overflow-hidden"
        :class="getTableClass(mesa.estado)"
      >
        <!-- Status indicator -->
        <div 
          class="absolute top-0 right-0 w-16 h-16 -mr-8 -mt-8 rounded-full"
          :class="getStatusColor(mesa.estado)"
        ></div>

        <div class="relative">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-bold">{{ mesa.nombre }}</h3>
            <div class="w-8 h-8 flex items-center justify-center text-gray-400">
              <Icon :name="getTableShape(mesa.forma)" :size="24" />
            </div>
          </div>

          <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2 text-gray-600">
              <Icon name="users" :size="16" />
              <span>{{ mesa.capacidad }} personas</span>
            </div>
            
            <div v-if="mesa.zona_nombre" class="flex items-center gap-2">
              <span 
                class="w-3 h-3 rounded-full"
                :style="{ backgroundColor: mesa.zona_color }"
              ></span>
              <span class="text-gray-600">{{ mesa.zona_nombre }}</span>
            </div>

            <!-- Current order info -->
            <div v-if="mesa.comanda_actual" class="mt-3 pt-3 border-t border-gray-200">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">{{ mesa.comanda_actual.items_count }} items</span>
                <span class="font-bold text-primary-600">
                  S/ {{ parseFloat(mesa.comanda_actual.total).toFixed(2) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Status badge -->
          <div class="mt-4">
            <span 
              class="badge"
              :class="getStatusBadgeClass(mesa.estado)"
            >
              {{ getStatusLabel(mesa.estado) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="tablesStore.mesasFiltradas.length === 0" class="text-center py-12">
      <Icon name="utensils" :size="64" class="mx-auto text-gray-300" />
      <p class="text-gray-500 mt-4">No hay mesas en esta zona</p>
    </div>

    <!-- Table Modal -->
    <TableModal 
      v-if="selectedMesa"
      :mesa="selectedMesa"
      @close="selectedMesa = null"
      @update="fetchData"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTablesStore } from '@/stores/tables'
import { useCartStore } from '@/stores/cart'
import TableModal from '@/components/tables/TableModal.vue'
import Icon from '@/components/ui/Icon.vue'

const router = useRouter()
const tablesStore = useTablesStore()
const cartStore = useCartStore()

const selectedMesa = ref(null)

function getTableClass(estado) {
  return {
    'border-2 border-accent-500': estado === 'libre',
    'border-2 border-red-500 bg-red-50': estado === 'ocupada',
    'border-2 border-amber-500 bg-amber-50': estado === 'cuenta',
    'border-2 border-blue-500 bg-blue-50': estado === 'reservada',
    'border-2 border-gray-300 bg-gray-100 opacity-50': estado === 'mantenimiento'
  }
}

function getStatusColor(estado) {
  return {
    'bg-accent-500': estado === 'libre',
    'bg-red-500': estado === 'ocupada',
    'bg-amber-500': estado === 'cuenta',
    'bg-blue-500': estado === 'reservada',
    'bg-gray-400': estado === 'mantenimiento'
  }
}

function getStatusBadgeClass(estado) {
  return {
    'badge-success': estado === 'libre',
    'badge-danger': estado === 'ocupada',
    'badge-warning': estado === 'cuenta',
    'badge-info': estado === 'reservada',
    'badge-gray': estado === 'mantenimiento'
  }
}

function getStatusLabel(estado) {
  const labels = {
    libre: 'Disponible',
    ocupada: 'Ocupada',
    cuenta: 'Pidiendo cuenta',
    reservada: 'Reservada',
    mantenimiento: 'Mantenimiento'
  }
  return labels[estado] || estado
}

function getTableShape(forma) {
  const shapes = {
    redonda: 'circle',
    rectangular: 'rectangle',
    cuadrada: 'square'
  }
  return shapes[forma] || 'square'
}

function openMesa(mesa) {
  if (mesa.estado === 'libre') {
    // Go to POS and set table
    cartStore.setMesa(mesa)
    router.push('/pos')
  } else {
    // Open modal for occupied table
    selectedMesa.value = mesa
  }
}

async function fetchData() {
  await Promise.all([
    tablesStore.fetchMesas(),
    tablesStore.fetchZonas()
  ])
}

onMounted(() => {
  fetchData()
})
</script>
