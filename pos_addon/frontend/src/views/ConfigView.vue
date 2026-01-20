<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
          <Icon name="settings" :size="28" class="text-primary-500" />
          Configuración
        </h2>
        <p class="text-gray-500">Ajustes del sistema y del negocio</p>
      </div>
      
      <button 
        @click="saveConfig" 
        :disabled="saving || !hasChanges"
        class="btn-success"
      >
        <span v-if="saving">Guardando...</span>
        <span v-else class="flex items-center gap-2">
          <Icon name="save" :size="18" />
          Guardar Cambios
        </span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card text-center py-12">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="text-gray-500 mt-4">Cargando configuración...</p>
    </div>

    <!-- Config Sections -->
    <div v-else class="space-y-6">
      <!-- Negocio -->
      <div class="card">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
            <Icon name="building" :size="20" class="text-primary-600" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900">Datos del Negocio</h3>
            <p class="text-sm text-gray-500">Información de tu empresa</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Nombre del Negocio</label>
            <input 
              v-model="config.negocio.negocio_nombre" 
              type="text" 
              class="input"
              placeholder="Mi Restaurante"
            />
          </div>
          <div>
            <label class="label">RUC</label>
            <input 
              v-model="config.negocio.negocio_ruc" 
              type="text" 
              class="input"
              placeholder="20123456789"
              maxlength="11"
            />
          </div>
          <div class="md:col-span-2">
            <label class="label">Dirección</label>
            <input 
              v-model="config.negocio.negocio_direccion" 
              type="text" 
              class="input"
              placeholder="Av. Principal 123, Lima"
            />
          </div>
          <div>
            <label class="label">Teléfono</label>
            <input 
              v-model="config.negocio.negocio_telefono" 
              type="text" 
              class="input"
              placeholder="01-1234567"
            />
          </div>
          <div>
            <label class="label">Email</label>
            <input 
              v-model="config.negocio.negocio_email" 
              type="email" 
              class="input"
              placeholder="contacto@mirestaurante.com"
            />
          </div>
        </div>
      </div>

      <!-- Facturación -->
      <div class="card">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <Icon name="file-text" :size="20" class="text-green-600" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900">Facturación</h3>
            <p class="text-sm text-gray-500">Configuración de comprobantes</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="label">IGV (%)</label>
            <input 
              v-model.number="config.facturacion.igv_porcentaje" 
              type="number" 
              step="0.01"
              class="input"
              placeholder="18"
            />
          </div>
          <div>
            <label class="label">Símbolo de Moneda</label>
            <input 
              v-model="config.facturacion.moneda_simbolo" 
              type="text" 
              class="input"
              placeholder="S/"
            />
          </div>
          <div>
            <label class="label">Código de Moneda</label>
            <input 
              v-model="config.facturacion.moneda_codigo" 
              type="text" 
              class="input"
              placeholder="PEN"
            />
          </div>
        </div>
      </div>

      <!-- Impresión -->
      <div class="card">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
            <Icon name="printer" :size="20" class="text-blue-600" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900">Impresión</h3>
            <p class="text-sm text-gray-500">Configuración de tickets</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Ancho de Ticket (mm)</label>
            <select v-model="config.impresion.ticket_ancho" class="input">
              <option value="58">58mm (Mini)</option>
              <option value="80">80mm (Estándar)</option>
            </select>
          </div>
          <div>
            <label class="label">Mensaje de Pie</label>
            <input 
              v-model="config.impresion.ticket_footer" 
              type="text" 
              class="input"
              placeholder="¡Gracias por su compra!"
            />
          </div>
        </div>

        <div class="mt-4 flex gap-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              v-model="config.impresion.imprimir_auto" 
              type="checkbox" 
              class="w-4 h-4 accent-primary-500" 
            />
            <span>Imprimir automáticamente al cobrar</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              v-model="config.impresion.mostrar_qr" 
              type="checkbox" 
              class="w-4 h-4 accent-primary-500" 
            />
            <span>Mostrar QR en ticket</span>
          </label>
        </div>
      </div>

      <!-- Sistema -->
      <div class="card">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
            <Icon name="cog" :size="20" class="text-purple-600" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900">Sistema</h3>
            <p class="text-sm text-gray-500">Opciones generales</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Zona Horaria</label>
            <select v-model="config.sistema.timezone" class="input">
              <option value="America/Lima">Lima (GMT-5)</option>
              <option value="America/Bogota">Bogotá (GMT-5)</option>
              <option value="America/Mexico_City">Ciudad de México (GMT-6)</option>
              <option value="America/Argentina/Buenos_Aires">Buenos Aires (GMT-3)</option>
            </select>
          </div>
          <div>
            <label class="label">Tema de Color</label>
            <select v-model="config.sistema.tema" class="input">
              <option value="naranja">Naranja (Predeterminado)</option>
              <option value="azul">Azul</option>
              <option value="verde">Verde</option>
              <option value="rojo">Rojo</option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex gap-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              v-model="config.sistema.sonidos_activos" 
              type="checkbox" 
              class="w-4 h-4 accent-primary-500" 
            />
            <span>Sonidos de notificación</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              v-model="config.sistema.confirmar_cierre" 
              type="checkbox" 
              class="w-4 h-4 accent-primary-500" 
            />
            <span>Confirmar antes de cerrar caja</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Unsaved changes indicator -->
    <div 
      v-if="hasChanges && !loading" 
      class="fixed bottom-4 left-1/2 -translate-x-1/2 bg-amber-500 text-white px-6 py-3 rounded-full shadow-lg flex items-center gap-3"
    >
      <Icon name="alert-circle" :size="20" />
      <span>Tienes cambios sin guardar</span>
      <button @click="saveConfig" class="bg-white text-amber-600 px-4 py-1 rounded-full font-medium">
        Guardar
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { configApi } from '@/services/api'
import Icon from '@/components/ui/Icon.vue'

// State
const loading = ref(true)
const saving = ref(false)
const originalConfig = ref(null)

// Config structure
const config = reactive({
  negocio: {
    negocio_nombre: '',
    negocio_ruc: '',
    negocio_direccion: '',
    negocio_telefono: '',
    negocio_email: ''
  },
  facturacion: {
    igv_porcentaje: 18,
    moneda_simbolo: 'S/',
    moneda_codigo: 'PEN'
  },
  impresion: {
    ticket_ancho: '80',
    ticket_footer: '¡Gracias por su compra!',
    imprimir_auto: false,
    mostrar_qr: true
  },
  sistema: {
    timezone: 'America/Lima',
    tema: 'naranja',
    sonidos_activos: true,
    confirmar_cierre: true
  }
})

// Check for changes
const hasChanges = computed(() => {
  if (!originalConfig.value) return false
  return JSON.stringify(config) !== JSON.stringify(originalConfig.value)
})

// Fetch config
async function fetchConfig() {
  loading.value = true
  try {
    const { data } = await configApi.getAll()
    
    if (data.success) {
      // Map API values to our structure
      const apiConfig = data.data
      
      // Negocio
      if (apiConfig.negocio) {
        Object.keys(config.negocio).forEach(key => {
          if (apiConfig.negocio[key] !== undefined) {
            config.negocio[key] = apiConfig.negocio[key]
          }
        })
      }
      
      // Facturación
      if (apiConfig.facturacion) {
        Object.keys(config.facturacion).forEach(key => {
          if (apiConfig.facturacion[key] !== undefined) {
            config.facturacion[key] = apiConfig.facturacion[key]
          }
        })
      }
      
      // Impresión
      if (apiConfig.impresion) {
        Object.keys(config.impresion).forEach(key => {
          if (apiConfig.impresion[key] !== undefined) {
            config.impresion[key] = apiConfig.impresion[key]
          }
        })
      }
      
      // Sistema
      if (apiConfig.sistema) {
        Object.keys(config.sistema).forEach(key => {
          if (apiConfig.sistema[key] !== undefined) {
            config.sistema[key] = apiConfig.sistema[key]
          }
        })
      }
      
      // Store original for comparison
      originalConfig.value = JSON.parse(JSON.stringify(config))
    }
  } catch (error) {
    console.error('Error fetching config:', error)
  } finally {
    loading.value = false
  }
}

// Save config
async function saveConfig() {
  if (!hasChanges.value) return
  
  saving.value = true
  try {
    // Flatten config to key-value pairs
    const flatConfig = {}
    
    Object.entries(config).forEach(([grupo, values]) => {
      Object.entries(values).forEach(([key, value]) => {
        flatConfig[key] = value
      })
    })
    
    await configApi.update(flatConfig)
    
    // Update original
    originalConfig.value = JSON.parse(JSON.stringify(config))
    
    alert('Configuración guardada exitosamente')
  } catch (error) {
    console.error('Error saving config:', error)
    alert(error.response?.data?.message || 'Error al guardar configuración')
  } finally {
    saving.value = false
  }
}

// Warn before leaving with unsaved changes
function handleBeforeUnload(e) {
  if (hasChanges.value) {
    e.preventDefault()
    e.returnValue = ''
  }
}

onMounted(() => {
  fetchConfig()
  window.addEventListener('beforeunload', handleBeforeUnload)
})
</script>
