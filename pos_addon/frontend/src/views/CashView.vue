<template>
  <div class="space-y-6">
    <!-- Cash Session Status -->
    <div v-if="!cashSession" class="card bg-amber-50 border-2 border-amber-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-amber-100 rounded-xl flex items-center justify-center">
            <Icon name="wallet" :size="28" class="text-amber-600" />
          </div>
          <div>
            <h3 class="font-bold text-amber-800">Caja Cerrada</h3>
            <p class="text-amber-700">Debe abrir caja para realizar ventas</p>
          </div>
        </div>
        <button @click="showOpenModal = true" class="btn-primary">
          <Icon name="unlock" :size="18" />
          Abrir Caja
        </button>
      </div>
    </div>

    <!-- Open Cash Session -->
    <div v-else class="card border-2 border-accent-200 bg-accent-50">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-accent-100 rounded-xl flex items-center justify-center">
            <Icon name="check-circle" :size="28" class="text-accent-600" />
          </div>
          <div>
            <h3 class="font-bold text-accent-800">Caja Abierta</h3>
            <p class="text-accent-700">
              Desde: {{ formatTime(cashSession.fecha_apertura) }}
            </p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-600">Monto inicial:</p>
          <p class="text-xl font-bold text-accent-700">
            S/ {{ parseFloat(cashSession.monto_inicial).toFixed(2) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Session Stats -->
    <div v-if="cashSession" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="card text-center">
        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mx-auto">
          <Icon name="clipboard" :size="24" class="text-gray-600" />
        </div>
        <p class="text-2xl font-bold text-gray-900 mt-2">{{ cashSession.ventas_count || 0 }}</p>
        <p class="text-sm text-gray-500">Ventas</p>
      </div>
      <div class="card text-center">
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto">
          <Icon name="banknote" :size="24" class="text-green-600" />
        </div>
        <p class="text-2xl font-bold text-primary-600 mt-2">
          S/ {{ parseFloat(cashSession.total_efectivo || 0).toFixed(2) }}
        </p>
        <p class="text-sm text-gray-500">Efectivo</p>
      </div>
      <div class="card text-center">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto">
          <Icon name="credit-card" :size="24" class="text-blue-600" />
        </div>
        <p class="text-2xl font-bold text-blue-600 mt-2">
          S/ {{ parseFloat(cashSession.total_tarjeta || 0).toFixed(2) }}
        </p>
        <p class="text-sm text-gray-500">Tarjetas</p>
      </div>
      <div class="card text-center">
        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mx-auto">
          <Icon name="smartphone" :size="24" class="text-purple-600" />
        </div>
        <p class="text-2xl font-bold text-purple-600 mt-2">
          S/ {{ (parseFloat(cashSession.total_yape || 0) + parseFloat(cashSession.total_plin || 0)).toFixed(2) }}
        </p>
        <p class="text-sm text-gray-500">Yape/Plin</p>
      </div>
    </div>

    <!-- Close Cash Button -->
    <div v-if="cashSession" class="flex justify-end">
      <button @click="showCloseModal = true" class="btn-danger">
        <Icon name="lock" :size="18" />
        Cerrar Caja
      </button>
    </div>

    <!-- Open Cash Modal -->
    <Teleport v-if="showOpenModal" to="body">
      <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
          <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
            <Icon name="wallet" :size="24" class="text-primary-500" />
            Abrir Caja
          </h2>
          
          <div class="space-y-4">
            <div>
              <label class="label">Monto Inicial (S/)</label>
              <input 
                v-model.number="openForm.monto_inicial" 
                type="number" 
                step="0.01"
                class="input"
                placeholder="0.00"
              />
            </div>
            <div>
              <label class="label">Observaciones (opcional)</label>
              <textarea 
                v-model="openForm.observaciones" 
                class="input"
                rows="2"
              ></textarea>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button @click="showOpenModal = false" class="btn-secondary flex-1">
              Cancelar
            </button>
            <button @click="abrirCaja" class="btn-success flex-1">
              Abrir Caja
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Close Cash Modal -->
    <Teleport v-if="showCloseModal" to="body">
      <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
          <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
            <Icon name="lock" :size="24" class="text-red-500" />
            Cerrar Caja
          </h2>
          
          <div class="space-y-4">
            <div class="bg-gray-50 rounded-xl p-4 space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">Monto inicial:</span>
                <span>S/ {{ parseFloat(cashSession?.monto_inicial || 0).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">+ Efectivo:</span>
                <span>S/ {{ parseFloat(cashSession?.total_efectivo || 0).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between font-bold pt-2 border-t">
                <span>Esperado en caja:</span>
                <span class="text-primary-600">
                  S/ {{ (parseFloat(cashSession?.monto_inicial || 0) + parseFloat(cashSession?.total_efectivo || 0)).toFixed(2) }}
                </span>
              </div>
            </div>

            <div>
              <label class="label">Conteo Real en Caja (S/)</label>
              <input 
                v-model.number="closeForm.monto_real" 
                type="number" 
                step="0.01"
                class="input"
                placeholder="0.00"
              />
            </div>

            <div v-if="diferencia !== 0" class="p-3 rounded-lg" :class="diferencia >= 0 ? 'bg-accent-50' : 'bg-red-50'">
              <span :class="diferencia >= 0 ? 'text-accent-700' : 'text-red-700'">
                {{ diferencia >= 0 ? 'Sobrante' : 'Faltante' }}: S/ {{ Math.abs(diferencia).toFixed(2) }}
              </span>
            </div>

            <div>
              <label class="label">Observaciones (opcional)</label>
              <textarea 
                v-model="closeForm.observaciones" 
                class="input"
                rows="2"
              ></textarea>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button @click="showCloseModal = false" class="btn-secondary flex-1">
              Cancelar
            </button>
            <button @click="cerrarCaja" class="btn-danger flex-1">
              Cerrar Caja
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { cajaApi } from '@/services/api'
import Icon from '@/components/ui/Icon.vue'

const cashSession = ref(null)
const showOpenModal = ref(false)
const showCloseModal = ref(false)

const openForm = reactive({
  monto_inicial: 0,
  observaciones: ''
})

const closeForm = reactive({
  monto_real: 0,
  observaciones: ''
})

const diferencia = computed(() => {
  if (!cashSession.value) return 0
  const esperado = parseFloat(cashSession.value.monto_inicial || 0) + 
                   parseFloat(cashSession.value.total_efectivo || 0)
  return closeForm.monto_real - esperado
})

function formatTime(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function fetchCashSession() {
  try {
    const { data } = await cajaApi.getActual()
    if (data.success) {
      cashSession.value = data.data
      if (cashSession.value) {
        closeForm.monto_real = parseFloat(cashSession.value.monto_inicial || 0) + 
                               parseFloat(cashSession.value.total_efectivo || 0)
      }
    }
  } catch (error) {
    console.error('Error fetching cash session:', error)
  }
}

async function abrirCaja() {
  try {
    const { data } = await cajaApi.abrir(openForm)
    if (data.success) {
      showOpenModal.value = false
      fetchCashSession()
      Object.assign(openForm, { monto_inicial: 0, observaciones: '' })
    }
  } catch (error) {
    console.error('Error opening cash:', error)
    alert('Error al abrir caja')
  }
}

async function cerrarCaja() {
  if (!confirm('¿Está seguro de cerrar la caja?')) return
  
  try {
    const { data } = await cajaApi.cerrar(closeForm)
    if (data.success) {
      showCloseModal.value = false
      cashSession.value = null
      alert('Caja cerrada exitosamente')
    }
  } catch (error) {
    console.error('Error closing cash:', error)
    alert(error.response?.data?.message || 'Error al cerrar caja')
  }
}

onMounted(() => {
  fetchCashSession()
})
</script>
