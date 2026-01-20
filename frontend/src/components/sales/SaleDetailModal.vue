<template>
  <BaseModal
    :show="show"
    title="Detalle de Venta"
    :subtitle="venta?.numero_comprobante"
    icon="receipt"
    variant="primary"
    size="2xl"
    scrollable
    max-height="max-h-[70vh]"
    @close="$emit('close')"
  >
    <!-- Content -->
    <div v-if="ventaDetalle" class="space-y-4">
      <!-- Sale info -->
      <div class="bg-primary-50 rounded-xl p-4">
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
      <div class="bg-gray-50 rounded-xl p-4">
        <h3 class="font-semibold mb-2">Cliente</h3>
        <div class="text-sm space-y-1">
          <p><span class="text-gray-600">Nombre:</span> {{ ventaDetalle.cliente_nombres || 'Cliente genérico' }}</p>
          <p v-if="ventaDetalle.cliente_documento">
            <span class="text-gray-600">Documento:</span> {{ ventaDetalle.cliente_documento }}
          </p>
        </div>
      </div>

      <!-- Items -->
      <div>
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
      <div class="bg-primary-50 rounded-xl p-4 space-y-2">
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
      <div v-if="ventaDetalle.pagos?.length" class="bg-gray-50 rounded-xl p-4">
        <h3 class="font-semibold mb-2">Pagos</h3>
        <div class="space-y-2">
          <div v-for="pago in ventaDetalle.pagos" :key="pago.id" class="text-sm">
            <span class="font-medium">{{ pago.metodo_pago }}:</span>
            <span class="ml-2">S/ {{ parseFloat(pago.monto).toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-else class="text-center py-12">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="text-gray-500 mt-4">Cargando detalle...</p>
    </div>

    <!-- Footer -->
    <template #footer>
      <button @click="$emit('close')" class="btn-secondary w-full">
        Cerrar
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { ventasApi } from '@/services/api'
import BaseModal from '@/components/ui/BaseModal.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  venta: {
    type: Object,
    default: null
  }
})

defineEmits(['close'])

const ventaDetalle = ref(null)

// Watch for venta changes and fetch details
watch(() => props.venta, async (newVenta) => {
  if (newVenta?.id) {
    ventaDetalle.value = null // Reset
    try {
      const { data } = await ventasApi.getOne(newVenta.id)
      if (data.success) {
        ventaDetalle.value = data.data
      }
    } catch (error) {
      console.error('Error fetching sale detail:', error)
    }
  }
}, { immediate: true })

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
</script>
