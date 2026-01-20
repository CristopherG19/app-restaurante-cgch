<template>
  <BaseModal
    :show="true"
    title="Cobrar Venta"
    icon="credit-card"
    variant="success"
    size="lg"
    scrollable
    max-height="max-h-[70vh]"
    @close="$emit('close')"
  >
    <!-- Custom header content for total -->
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3 flex-1">
          <div class="bg-green-100/80 p-2.5 rounded-xl">
            <Icon name="credit-card" :size="24" class="text-green-600" />
          </div>
          <div>
            <h2 class="text-xl font-bold text-green-900">Cobrar Venta</h2>
            <p class="text-2xl font-bold text-green-700 mt-1">
              S/ {{ total.toFixed(2) }}
            </p>
          </div>
        </div>
        <button 
          @click="$emit('close')" 
          class="btn-icon text-green-900/60 hover:text-green-900 hover:bg-black/5 transition-all"
        >
          <Icon name="x" :size="20" />
        </button>
      </div>
    </template>

    <!-- Content -->
    <div class="space-y-6">
      <!-- Document Type -->
      <div>
        <label class="label">Tipo de Comprobante</label>
        <div class="grid grid-cols-3 gap-2">
          <button 
            v-for="tipo in tiposComprobante"
            :key="tipo.value"
            @click="form.tipo_comprobante = tipo.value"
            class="py-3 rounded-lg text-sm font-medium transition-colors"
            :class="form.tipo_comprobante === tipo.value 
              ? 'bg-primary-500 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          >
            {{ tipo.label }}
          </button>
        </div>
      </div>

      <!-- Client info (for Boleta and Factura) -->
      <div v-if="form.tipo_comprobante !== 'NOTA_VENTA'" class="space-y-3">
        <label class="label">
          <span v-if="form.tipo_comprobante === 'FACTURA'">Datos del Cliente (RUC)</span>
          <span v-else>Datos del Cliente (DNI)</span>
          <span v-if="form.tipo_comprobante === 'BOLETA'" class="text-xs text-gray-500 ml-2">
            {{ total >= 700 ? '- Obligatorio' : '- Opcional' }}
          </span>
        </label>
        <div class="flex gap-2">
          <input 
            v-model="clienteDoc" 
            type="text" 
            class="input" 
            :placeholder="form.tipo_comprobante === 'FACTURA' ? 'RUC (11 dígitos)' : 'DNI (8 dígitos)'"
            :maxlength="form.tipo_comprobante === 'FACTURA' ? 11 : 8"
          />
          <button @click="buscarCliente" class="btn-secondary">Buscar</button>
        </div>
        
        <!-- Info message for high-value boletas -->
        <p v-if="form.tipo_comprobante === 'BOLETA' && total >= 700" class="text-xs text-amber-600 flex items-center gap-1">
          <Icon name="alert-circle" :size="14" />
          Montos ≥ S/ 700 requieren DNI del cliente según SUNAT
        </p>
        
        <div v-if="cliente" class="p-3 bg-accent-50 rounded-lg">
          <p class="font-medium">{{ cliente.razon_social || cliente.nombres }}</p>
          <p class="text-sm text-gray-600">{{ cliente.numero_documento }}</p>
        </div>
      </div>

      <!-- Payment Methods -->
      <div>
        <label class="label">Métodos de Pago</label>
        <div class="space-y-3">
          <div 
            v-for="(pago, index) in pagos" 
            :key="index"
            class="flex gap-3 items-start"
          >
            <select v-model="pago.metodo" class="input flex-1">
              <option value="efectivo">Efectivo</option>
              <option value="yape">Yape</option>
              <option value="plin">Plin</option>
              <option value="visa">Visa</option>
              <option value="mastercard">Mastercard</option>
              <option value="transferencia">Transferencia</option>
            </select>
            <input 
              v-model.number="pago.monto" 
              type="number" 
              step="0.01"
              class="input w-32"
              placeholder="Monto"
            />
            <button 
              v-if="pagos.length > 1"
              @click="removePago(index)"
              class="btn-icon text-red-400"
            >
              <Icon name="x" :size="18" />
            </button>
          </div>
        </div>
        
        <button 
          @click="addPago"
          class="mt-3 text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
        >
          <Icon name="plus" :size="16" />
          Agregar otro método (Split Payment)
        </button>
      </div>

      <!-- Cash calculation -->
      <div v-if="pagos.some(p => p.metodo === 'efectivo')" class="bg-gray-50 rounded-xl p-4">
        <div class="flex justify-between mb-2">
          <span class="text-gray-600">Monto recibido:</span>
          <input 
            v-model.number="montoRecibido" 
            type="number" 
            step="0.01"
            class="input w-32 text-right"
          />
        </div>
        <div class="flex justify-between text-lg font-bold" :class="vuelto >= 0 ? 'text-accent-600' : 'text-red-600'">
          <span>Vuelto:</span>
          <span>S/ {{ vuelto.toFixed(2) }}</span>
        </div>
      </div>

      <!-- Summary -->
      <div class="bg-gray-50 rounded-xl p-4 space-y-2">
        <div class="flex justify-between">
          <span class="text-gray-600">Total a pagar:</span>
          <span class="font-bold">S/ {{ total.toFixed(2) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Total pagado:</span>
          <span class="font-bold" :class="totalPagado >= total ? 'text-accent-600' : 'text-red-600'">
            S/ {{ totalPagado.toFixed(2) }}
          </span>
        </div>
        <div v-if="restante > 0" class="flex justify-between text-red-600">
          <span>Falta:</span>
          <span class="font-bold">S/ {{ restante.toFixed(2) }}</span>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <button @click="$emit('close')" class="btn-secondary flex-1">
        Cancelar
      </button>
      <button 
        @click="procesarPago"
        :disabled="!canProcess || processing"
        class="btn-success flex-1"
      >
        <span v-if="processing">Procesando...</span>
        <span v-else class="flex items-center gap-2">
          <Icon name="check-circle" :size="18" />
          Confirmar Pago
        </span>
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useCartStore } from '@/stores/cart'
import { ventasApi, clientesApi } from '@/services/api'
import BaseModal from '@/components/ui/BaseModal.vue'
import Icon from '@/components/ui/Icon.vue'

const emit = defineEmits(['close', 'success'])

const cartStore = useCartStore()

const tiposComprobante = [
  { value: 'NOTA_VENTA', label: 'Boleta Simple' },
  { value: 'BOLETA', label: 'Boleta' },
  { value: 'FACTURA', label: 'Factura' }
]

const form = reactive({
  tipo_comprobante: 'NOTA_VENTA'
})

const pagos = ref([{ metodo: 'efectivo', monto: 0 }])
const montoRecibido = ref(0)
const clienteDoc = ref('')
const cliente = ref(null)
const processing = ref(false)

const total = computed(() => cartStore.total)

const totalPagado = computed(() => 
  pagos.value.reduce((sum, p) => sum + (p.monto || 0), 0)
)

const restante = computed(() => 
  Math.max(0, total.value - totalPagado.value)
)

const vuelto = computed(() => 
  montoRecibido.value - total.value
)

const canProcess = computed(() => {
  const requiereCliente = 
    form.tipo_comprobante === 'FACTURA' || 
    (form.tipo_comprobante === 'BOLETA' && total.value >= 700)
  
  return totalPagado.value >= total.value && 
         (!requiereCliente || cliente.value)
})

function addPago() {
  pagos.value.push({ metodo: 'efectivo', monto: restante.value })
}

function removePago(index) {
  pagos.value.splice(index, 1)
}

async function buscarCliente() {
  if (clienteDoc.value.length !== 11) return
  
  try {
    const { data } = await clientesApi.buscar(clienteDoc.value)
    if (data.success && data.data) {
      cliente.value = data.data
    } else {
      alert('Cliente no encontrado')
    }
  } catch (error) {
    console.error('Error buscando cliente:', error)
  }
}

async function procesarPago() {
  if (!canProcess.value || processing.value) return
  
  processing.value = true
  
  try {
    const ventaData = {
      tipo_comprobante: form.tipo_comprobante,
      id_cliente: cliente.value?.id || null,
      id_mesa: cartStore.mesa?.id || null,
      tipo_servicio: cartStore.tipoServicio,
      descuento: cartStore.descuento,
      items: cartStore.getItemsForApi(),
      pagos: pagos.value.filter(p => p.monto > 0).map(p => ({
        metodo: p.metodo,
        monto: p.monto,
        monto_recibido: p.metodo === 'efectivo' ? montoRecibido.value : null,
        vuelto: p.metodo === 'efectivo' && vuelto.value > 0 ? vuelto.value : null
      }))
    }
    
    const { data } = await ventasApi.create(ventaData)
    
    if (data.success) {
      alert(`Venta registrada: ${data.data.comprobante}`)
      emit('success', data.data)
    } else {
      alert('Error: ' + (data.message || 'No se pudo procesar la venta'))
    }
  } catch (error) {
    console.error('Error procesando venta:', error)
    alert('Error al procesar la venta')
  } finally {
    processing.value = false
  }
}

// Set initial amount
pagos.value[0].monto = total.value
montoRecibido.value = total.value
</script>
