<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
          <Icon name="receipt" :size="28" class="text-primary-500" />
          Historial de Ventas
        </h2>
        <p class="text-gray-500">{{ totalVentas }} ventas registradas</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Date range -->
        <div>
          <label class="label">Fecha Desde</label>
          <input v-model="filtros.fecha_desde" type="date" class="input" @change="fetchVentas" />
        </div>
        <div>
          <label class="label">Fecha Hasta</label>
          <input v-model="filtros.fecha_hasta" type="date" class="input" @change="fetchVentas" />
        </div>
        
        <!-- Quick filters -->
        <div class="md:col-span-2 flex gap-2 items-end">
          <button @click="setQuickFilter('hoy')" class="btn-secondary">Hoy</button>
          <button @click="setQuickFilter('semana')" class="btn-secondary">Esta Semana</button>
          <button @click="setQuickFilter('mes')" class="btn-secondary">Este Mes</button>
          <button @click="limpiarFiltros" class="btn-secondary">Limpiar</button>
        </div>

        <!-- Type filter -->
        <div>
          <label class="label">Tipo Comprobante</label>
          <select v-model="filtros.tipo" class="input" @change="fetchVentas">
            <option :value="null">Todos</option>
            <option value="NOTA_VENTA">Boleta Simple</option>
            <option value="BOLETA">Boleta</option>
            <option value="FACTURA">Factura</option>
          </select>
        </div>

        <!-- Status filter -->
        <div>
          <label class="label">Estado</label>
          <select v-model="filtros.estado" class="input" @change="fetchVentas">
            <option :value="null">Todos</option>
            <option value="pagada">Pagada</option>
            <option value="anulada">Anulada</option>
          </select>
        </div>

        <!-- Search -->
        <div class="md:col-span-2">
          <label class="label">Buscar</label>
          <div class="relative">
            <Icon name="search" :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Número de venta o cliente..."
              class="input pl-10"
              @input="debouncedSearch"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Sales Table -->
    <div class="card overflow-hidden p-0">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Número</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Fecha</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Cliente</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tipo</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Estado</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr 
            v-for="venta in ventas" 
            :key="venta.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-4 py-3">
              <span class="font-mono font-medium text-gray-900">{{ venta.numero_comprobante }}</span>
            </td>
            <td class="px-4 py-3">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ formatDate(venta.fecha_emision) }}</p>
                <p class="text-xs text-gray-500">{{ formatTime(venta.fecha_emision) }}</p>
              </div>
            </td>
            <td class="px-4 py-3">
              <div v-if="venta.cliente_nombres">
                <p class="text-sm font-medium text-gray-900">{{ venta.cliente_nombres }}</p>
                <p class="text-xs text-gray-500">{{ venta.cliente_documento }}</p>
              </div>
              <span v-else class="text-gray-400">Cliente genérico</span>
            </td>
            <td class="px-4 py-3">
              <span 
                class="badge"
                :class="{
                  'badge-info': venta.tipo_comprobante === 'NOTA_VENTA',
                  'badge-success': venta.tipo_comprobante === 'BOLETA',
                  'badge-primary': venta.tipo_comprobante === 'FACTURA'
                }"
              >
                {{ venta.tipo_comprobante === 'NOTA_VENTA' ? 'Boleta Simple' : venta.tipo_comprobante }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <span class="font-bold text-primary-600">{{ venta.total_formato }}</span>
            </td>
            <td class="px-4 py-3 text-center">
              <span 
                class="badge"
                :class="venta.estado === 'pagada' ? 'badge-success' : 'badge-danger'"
              >
                {{ venta.estado === 'pagada' ? 'Pagada' : 'Anulada' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex gap-1 justify-end">
                <button 
                  @click="verDetalle(venta)" 
                  class="btn-icon text-gray-500 hover:text-primary-600"
                  title="Ver detalle"
                >
                  <Icon name="eye" :size="18" />
                </button>
                <button 
                  @click="imprimirTicket(venta.id)" 
                  class="btn-icon text-gray-500 hover:text-blue-600"
                  title="Imprimir ticket"
                >
                  <Icon name="printer" :size="18" />
                </button>
                <button 
                  v-if="venta.estado === 'pagada' && userStore.user.rol === 'admin'"
                  @click="confirmarAnular(venta)" 
                  class="btn-icon text-gray-500 hover:text-red-600"
                  title="Anular venta"
                >
                  <Icon name="x-circle" :size="18" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty state -->
      <div v-if="ventas.length === 0 && !loading" class="text-center py-12">
        <Icon name="receipt" :size="48" class="mx-auto text-gray-300" />
        <p class="text-gray-500 mt-4">No se encontraron ventas</p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-gray-500 mt-4">Cargando ventas...</p>
      </div>
    </div>

    <!-- Detail Modal -->
    <Teleport v-if="showDetailModal" to="body">
      <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
          <!-- Modal Header -->
          <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-xl font-bold text-gray-900">Detalle de Venta</h2>
                <p class="text-sm text-gray-500">{{ selectedVenta?.numero_comprobante }}</p>
              </div>
              <button @click="closeDetailModal" class="btn-icon text-gray-400">
                <Icon name="x" :size="20" />
              </button>
            </div>
          </div>

          <!-- Modal Content -->
          <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
            <!-- Sale info (NUEVO) -->
            <div v-if="ventaDetalle" class="bg-primary-50 rounded-xl p-4">
              <h3 class="font-semibold mb-2">Información del Comprobante</h3>
              <div class="text-sm space-y-1">
                <p>
                  <span class="text-gray-600">Tipo:</span> 
                  <span class="font-medium">{{ ventaDetalle.tipo_comprobante === 'NOTA_VENTA' ? 'Boleta Simple' : ventaDetalle.tipo_comprobante }}</span>
                </p>
                <p>
                  <span class="text-gray-600">Número:</span> 
                  <span class="font-medium">{{ ventaDetalle.numero_comprobante }}</span>
                </p>
                <p>
                  <span class="text-gray-600">Fecha:</span> 
                  {{ formatDate(ventaDetalle.fecha_emision) }} {{ formatTime(ventaDetalle.fecha_emision) }}
                </p>
                <p>
                  <span class="text-gray-600">Atendido por:</span> 
                  <span class="font-medium">{{ ventaDetalle.usuario_nombre }}</span>
                </p>
              </div>
            </div>

            <!-- Client info -->
            <div v-if="ventaDetalle" class="bg-gray-50 rounded-xl p-4">
              <h3 class="font-semibold mb-2">Cliente</h3>
              <div class="text-sm space-y-1">
                <p><span class="text-gray-600">Nombre:</span> {{ ventaDetalle.cliente_nombres || 'Cliente genérico' }}</p>
                <p v-if="ventaDetalle.cliente_documento">
                  <span class="text-gray-600">Documento:</span> {{ ventaDetalle.cliente_documento }}
                </p>
              </div>
            </div>

            <!-- Items -->
            <div v-if="ventaDetalle">
              <h3 class="font-semibold mb-2">Items</h3>
              <div class="space-y-2">
                <div 
                  v-for="item in ventaDetalle.detalles" 
                  :key="item.id"
                  class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
                >
                  <div>
                    <p class="font-medium">{{ item.producto_nombre }}</p>
                    <p class="text-sm text-gray-500">{{ item.cantidad }} x S/ {{ parseFloat(item.precio_unitario).toFixed(2) }}</p>
                  </div>
                  <p class="font-bold text-primary-600">S/ {{ parseFloat(item.subtotal).toFixed(2) }}</p>
                </div>
              </div>
            </div>

            <!-- Totals -->
            <div v-if="ventaDetalle" class="bg-primary-50 rounded-xl p-4 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal:</span>
                <span>S/ {{ parseFloat(ventaDetalle.subtotal).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">IGV (18%):</span>
                <span>S/ {{ parseFloat(ventaDetalle.igv).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold pt-2 border-t border-primary-200">
                <span>Total:</span>
                <span class="text-primary-600">S/ {{ parseFloat(ventaDetalle.total).toFixed(2) }}</span>
              </div>
            </div>

            <!-- Payment info -->
            <div v-if="ventaDetalle && ventaDetalle.pagos?.length" class="bg-gray-50 rounded-xl p-4">
              <h3 class="font-semibold mb-2">Pagos</h3>
              <div class="space-y-2">
                <div v-for="pago in ventaDetalle.pagos" :key="pago.id" class="text-sm">
                  <span class="font-medium">{{ pago.metodo_pago }}:</span>
                  <span class="ml-2">S/ {{ parseFloat(pago.monto).toFixed(2) }}</span>
                </div>
              </div>
            </div>

            <!-- User and date -->
            <div v-if="ventaDetalle" class="text-xs text-gray-500 pt-2 border-t">
              <p>Registrado por: {{ ventaDetalle.usuario_nombre }}</p>
              <p>Fecha: {{ formatDate(ventaDetalle.fecha_emision) }} {{ formatTime(ventaDetalle.fecha_emision) }}</p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="p-6 border-t border-gray-100">
            <button @click="closeDetailModal" class="btn-secondary w-full">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ventasApi } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import Icon from '@/components/ui/Icon.vue'

const userStore = useAuthStore()

// State
const ventas = ref([])
const ventaDetalle = ref(null)
const loading = ref(false)
const showDetailModal = ref(false)
const selectedVenta = ref(null)
const totalVentas = ref(0)

// Filters
const filtros = ref({
  fecha_desde: new Date().toISOString().split('T')[0],
  fecha_hasta: new Date().toISOString().split('T')[0],
  tipo: null,
  estado: null
})
const searchQuery = ref('')

// Debounce search
let searchTimeout = null
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchVentas()
  }, 300)
}

// Quick filters
function setQuickFilter(tipo) {
  const hoy = new Date()
  
  switch (tipo) {
    case 'hoy':
      filtros.value.fecha_desde = hoy.toISOString().split('T')[0]
      filtros.value.fecha_hasta = hoy.toISOString().split('T')[0]
      break
    case 'semana':
      const inicioSemana = new Date(hoy)
      inicioSemana.setDate(hoy.getDate() - hoy.getDay())
      filtros.value.fecha_desde = inicioSemana.toISOString().split('T')[0]
      filtros.value.fecha_hasta = hoy.toISOString().split('T')[0]
      break
    case 'mes':
      const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
      filtros.value.fecha_desde = inicioMes.toISOString().split('T')[0]
      filtros.value.fecha_hasta = hoy.toISOString().split('T')[0]
      break
  }
  
  fetchVentas()
}

function limpiarFiltros() {
  filtros.value = {
    fecha_desde: new Date().toISOString().split('T')[0],
    fecha_hasta: new Date().toISOString().split('T')[0],
    tipo: null,
    estado: null
  }
  searchQuery.value = ''
  fetchVentas()
}

// Fetch sales
async function fetchVentas() {
  loading.value = true
  try {
    const params = {}
    
    if (filtros.value.fecha_desde) params.fecha_desde = filtros.value.fecha_desde
    if (filtros.value.fecha_hasta) params.fecha_hasta = filtros.value.fecha_hasta
    if (filtros.value.tipo) params.tipo = filtros.value.tipo
    if (filtros.value.estado) params.estado = filtros.value.estado
    if (searchQuery.value) params.buscar = searchQuery.value
    
    const { data } = await ventasApi.getAll(params)
    
    if (data.success) {
      ventas.value = data.data
      totalVentas.value = data.data.length
    }
  } catch (error) {
    console.error('Error fetching sales:', error)
  } finally {
    loading.value = false
  }
}

// View detail
async function verDetalle(venta) {
  selectedVenta.value = venta
  showDetailModal.value = true
  
  try {
    const { data } = await ventasApi.getOne(venta.id)
    if (data.success) {
      ventaDetalle.value = data.data
    }
  } catch (error) {
    console.error('Error fetching sale detail:', error)
  }
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedVenta.value = null
  ventaDetalle.value = null
}

// Print ticket
async function imprimirTicket(ventaId) {
  try {
    const { data } = await ventasApi.getTicket(ventaId)
    if (data.success) {
      // Open in new window or download
      const blob = new Blob([data.data], { type: 'text/plain' })
      const url = window.URL.createObjectURL(blob)
      window.open(url, '_blank')
    }
  } catch (error) {
    console.error('Error printing ticket:', error)
    alert('Error al obtener el ticket')
  }
}

// Anular venta
function confirmarAnular(venta) {
  if (confirm(`¿Está seguro de anular la venta ${venta.numero_comprobante}?`)) {
    anularVenta(venta.id)
  }
}

async function anularVenta(ventaId) {
  try {
    await ventasApi.anular(ventaId)
    alert('Venta anulada exitosamente')
    fetchVentas()
  } catch (error) {
    console.error('Error anulando venta:', error)
    alert('Error al anular la venta')
  }
}

// Format helpers
function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

function formatTime(dateStr) {
  return new Date(dateStr).toLocaleTimeString('es-PE', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Init
onMounted(() => {
  fetchVentas()
})
</script>
