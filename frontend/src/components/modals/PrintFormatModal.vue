<template>
  <BaseModal
    :show="show"
    title="Seleccionar Formato de Impresión"
    subtitle="Elige el tamaño para tu comprobante"
    icon="printer"
    variant="primary"
    size="2xl"
    @close="$emit('close')"
  >
    <!-- Content -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Ticket 80mm -->
      <button
        @click="selectFormat('ticket')"
        class="group relative p-6 rounded-xl border-2 transition-all duration-200 hover:scale-105"
        :class="selectedFormat === 'ticket' 
          ? 'border-blue-500 bg-blue-50 shadow-lg' 
          : 'border-gray-200 hover:border-blue-300 hover:shadow-md'"
      >
        <div class="flex flex-col items-center gap-3">
          <div class="w-16 h-20 bg-gradient-to-b from-gray-100 to-gray-200 rounded-lg shadow-inner flex items-center justify-center relative overflow-hidden">
            <div class="absolute inset-0 flex flex-col gap-0.5 p-1.5">
              <div class="h-2 bg-gray-400 rounded"></div>
              <div class="h-1 bg-gray-300 rounded w-3/4"></div>
              <div class="h-1 bg-gray-300 rounded w-1/2"></div>
              <div class="flex-1"></div>
              <div class="h-1.5 bg-gray-400 rounded"></div>
            </div>
          </div>
          <div class="text-center">
            <h3 class="font-bold text-gray-900 mb-1">Ticket 80mm</h3>
            <p class="text-xs text-gray-500">Impresora térmica</p>
            <p class="text-xs text-gray-400 mt-1">80 x variable mm</p>
          </div>
          <div v-if="selectedFormat === 'ticket'" class="absolute top-2 right-2">
            <Icon name="check-circle" :size="20" class="text-blue-500" />
          </div>
        </div>
      </button>

      <!-- A4 -->
      <button
        @click="selectFormat('a4')"
        class="group relative p-6 rounded-xl border-2 transition-all duration-200 hover:scale-105"
        :class="selectedFormat === 'a4' 
          ? 'border-green-500 bg-green-50 shadow-lg' 
          : 'border-gray-200 hover:border-green-300 hover:shadow-md'"
      >
        <div class="flex flex-col items-center gap-3">
          <div class="w-16 h-20 bg-gradient-to-b from-white to-gray-100 rounded shadow-md flex items-center justify-center relative overflow-hidden border border-gray-300">
            <div class="absolute inset-0 flex flex-col gap-1 p-2">
              <div class="h-2 bg-gray-400 rounded"></div>
              <div class="h-1 bg-gray-300 rounded"></div>
              <div class="h-1 bg-gray-300 rounded w-4/5"></div>
              <div class="flex-1 grid grid-cols-2 gap-1 mt-1">
                <div class="bg-gray-200 rounded"></div>
                <div class="bg-gray-200 rounded"></div>
              </div>
              <div class="h-1.5 bg-gray-400 rounded"></div>
            </div>
          </div>
          <div class="text-center">
            <h3 class="font-bold text-gray-900 mb-1">A4 Estándar</h3>
            <p class="text-xs text-gray-500">Hoja completa</p>
            <p class="text-xs text-gray-400 mt-1">210 x 297 mm</p>
          </div>
          <div v-if="selectedFormat === 'a4'" class="absolute top-2 right-2">
            <Icon name="check-circle" :size="20" class="text-green-500" />
          </div>
        </div>
      </button>

      <!-- A3 -->
      <button
        @click="selectFormat('a3')"
        class="group relative p-6 rounded-xl border-2 transition-all duration-200 hover:scale-105"
        :class="selectedFormat === 'a3' 
          ? 'border-purple-500 bg-purple-50 shadow-lg' 
          : 'border-gray-200 hover:border-purple-300 hover:shadow-md'"
      >
        <div class="flex flex-col items-center gap-3">
          <div class="w-20 h-20 bg-gradient-to-b from-white to-gray-100 rounded shadow-md flex items-center justify-center relative overflow-hidden border border-gray-300">
            <div class="absolute inset-0 flex flex-col gap-1 p-2">
              <div class="h-2 bg-gray-400 rounded"></div>
              <div class="h-1 bg-gray-300 rounded"></div>
              <div class="h-1 bg-gray-300 rounded w-4/5"></div>
              <div class="flex-1 grid grid-cols-3 gap-1 mt-1">
                <div class="bg-gray-200 rounded"></div>
                <div class="bg-gray-200 rounded"></div>
                <div class="bg-gray-200 rounded"></div>
              </div>
              <div class="h-1.5 bg-gray-400 rounded"></div>
            </div>
          </div>
          <div class="text-center">
            <h3 class="font-bold text-gray-900 mb-1">A3 Grande</h3>
            <p class="text-xs text-gray-500">Hoja amplia</p>
            <p class="text-xs text-gray-400 mt-1">297 x 420 mm</p>
          </div>
          <div v-if="selectedFormat === 'a3'" class="absolute top-2 right-2">
            <Icon name="check-circle" :size="20" class="text-purple-500" />
          </div>
        </div>
      </button>
    </div>

    <!-- Footer -->
    <template #footer>
      <button
        @click="$emit('close')"
        class="px-6 py-2.5 rounded-lg font-medium text-gray-700 hover:bg-gray-200 transition-colors"
      >
        Cancelar
      </button>
      <button
        @click="confirm"
        :disabled="!selectedFormat"
        class="px-6 py-2.5 rounded-lg font-medium text-white transition-all"
        :class="selectedFormat 
          ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg' 
          : 'bg-gray-300 cursor-not-allowed'"
      >
        <span class="flex items-center gap-2">
          <Icon name="printer" :size="18" />
          Imprimir
        </span>
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref } from 'vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import Icon from '@/components/ui/Icon.vue'

defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'select'])

const selectedFormat = ref('a4') // Default: A4

function selectFormat(format) {
  selectedFormat.value = format
}

function confirm() {
  if (selectedFormat.value) {
    emit('select', selectedFormat.value)
  }
}
</script>
