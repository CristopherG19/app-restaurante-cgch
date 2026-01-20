<template>
  <BaseModal
    :show="true"
    :title="mesa.nombre"
    variant="primary"
    size="md"
    @close="$emit('close')"
  >
    <!-- Custom header content for status badge -->
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-blue-900">{{ mesa.nombre }}</h2>
          <span 
            class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold"
            :class="getStatusBadgeColorClass(mesa.estado)"
          >
            {{ getStatusLabel(mesa.estado) }}
          </span>
        </div>
        <button 
          @click="$emit('close')" 
          class="btn-icon text-blue-900/60 hover:text-blue-900 hover:bg-black/5 transition-all"
        >
          <Icon name="x" :size="20" />
        </button>
      </div>
    </template>

    <!-- Content -->
    <div class="space-y-6">
      <!-- Order info if exists -->
      <div v-if="mesa.comanda_actual">
        <h3 class="font-semibold mb-3">Cuenta Actual</h3>
        <div class="bg-gray-50 rounded-xl p-4 space-y-2">
          <div class="flex justify-between">
            <span class="text-gray-600">Comanda:</span>
            <span class="font-medium">{{ mesa.comanda_actual.numero }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Items:</span>
            <span>{{ mesa.comanda_actual.items_count }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Comensales:</span>
            <span>{{ mesa.comanda_actual.comensales }}</span>
          </div>
          <div class="flex justify-between pt-2 border-t">
            <span class="font-semibold">Total:</span>
            <span class="font-bold text-primary-600 text-lg">
              S/ {{ parseFloat(mesa.comanda_actual.total).toFixed(2) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="space-y-3">
        <button 
          v-if="mesa.estado === 'ocupada'"
          @click="goToPOS"
          class="btn-primary w-full"
        >
          <Icon name="plus" :size="18" />
          Agregar Items
        </button>
        
        <button 
          v-if="mesa.comanda_actual"
          @click="cobrarMesa"
          class="btn-success w-full"
        >
          <Icon name="credit-card" :size="18" />
          Cobrar Cuenta
        </button>

        <button 
          v-if="mesa.estado !== 'libre'"
          @click="liberarMesa"
          class="btn-secondary w-full"
        >
          <Icon name="unlock" :size="18" />
          Liberar Mesa
        </button>

        <button 
          v-if="mesa.estado === 'libre'"
          @click="ocuparMesa"
          class="btn-primary w-full"
        >
          <Icon name="clipboard" :size="18" />
          Nueva Comanda
        </button>
      </div>
    </div>
  </BaseModal>

  <!-- Payment Modal -->
  <PaymentModal 
    v-if="showPaymentModal"
    @close="handlePaymentCancel"
    @success="handlePaymentSuccess"
  />
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useTablesStore } from '@/stores/tables'
import { comandasApi } from '@/services/api'
import BaseModal from '@/components/ui/BaseModal.vue'
import Icon from '@/components/ui/Icon.vue'
import PaymentModal from '@/components/pos/PaymentModal.vue'

const props = defineProps({
  mesa: { type: Object, required: true }
})

const emit = defineEmits(['close', 'update'])

const router = useRouter()
const cartStore = useCartStore()
const tablesStore = useTablesStore()

const showPaymentModal = ref(false)

function getStatusBadgeColorClass(estado) {
  const classes = {
    libre: 'bg-green-500 text-white',
    ocupada: 'bg-red-500 text-white',
    cuenta: 'bg-amber-500 text-white',
    reservada: 'bg-blue-500 text-white'
  }
  return classes[estado] || 'bg-gray-500 text-white'
}

function getStatusLabel(estado) {
  const labels = {
    libre: 'Disponible',
    ocupada: 'Ocupada',
    cuenta: 'Pidiendo cuenta',
    reservada: 'Reservada'
  }
  return labels[estado] || estado
}

function goToPOS() {
  cartStore.setMesa(props.mesa)
  if (props.mesa.comanda_actual) {
    cartStore.setComanda(props.mesa.comanda_actual)
  }
  router.push('/pos')
  emit('close')
}

async function ocuparMesa() {
  cartStore.setMesa(props.mesa)
  router.push('/pos')
  emit('close')
}

async function cobrarMesa() {
  // Change table state to 'cuenta' (requesting bill)
  await tablesStore.updateEstado(props.mesa.id, 'cuenta')
  emit('update')
  
  // Load table's order into cart
  cartStore.setMesa(props.mesa)
  if (props.mesa.comanda_actual) {
    cartStore.setComanda(props.mesa.comanda_actual)
    
    // Load items from the order
    try {
      const { data } = await comandasApi.getItems(props.mesa.comanda_actual.id)
      if (data.success && data.data) {
        // Clear cart first
        cartStore.clearCart()
        
        // Add items from the order
        data.data.forEach(item => {
          cartStore.addItem({
            id: item.id_producto,
            nombre: item.producto_nombre,
            precio: parseFloat(item.precio_unitario),
            imagen: item.producto_imagen
          }, item.cantidad)
        })
      }
    } catch (error) {
      console.error('Error loading order items:', error)
      alert('Error al cargar los items de la comanda')
      return
    }
  }
  
  // Show payment modal directly
  showPaymentModal.value = true
}

async function handlePaymentSuccess() {
  showPaymentModal.value = false
  // Liberar la mesa después del pago exitoso
  await tablesStore.updateEstado(props.mesa.id, 'libre')
  emit('update')
  emit('close')
}

async function handlePaymentCancel() {
  showPaymentModal.value = false
  // If payment was cancelled and table still has active orders, restore to 'ocupada'
  if (props.mesa.comanda_actual) {
    await tablesStore.updateEstado(props.mesa.id, 'ocupada')
    emit('update')
  }
}

async function liberarMesa() {
  if (confirm('¿Seguro que desea liberar esta mesa?')) {
    await tablesStore.updateEstado(props.mesa.id, 'libre')
    emit('update')
    emit('close')
  }
}
</script>
