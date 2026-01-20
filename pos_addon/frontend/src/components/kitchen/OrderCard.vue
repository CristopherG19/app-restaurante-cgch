<template>
  <div 
    class="card cursor-pointer transition-all duration-200 hover:shadow-lg"
    :class="{ 'animate-pulse-alert': item.alerta }"
    @click="$emit('action', item.item_id)"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <span class="font-bold text-gray-800">{{ item.comanda_numero }}</span>
        <span 
          v-if="item.mesa_nombre"
          class="badge-info"
        >
          {{ item.mesa_nombre }}
        </span>
        <span 
          v-else
          class="badge-gray flex items-center gap-1"
        >
          <Icon :name="item.tipo_servicio === 'llevar' ? 'package' : 'package'" :size="14" />
          {{ item.tipo_servicio === 'llevar' ? 'Llevar' : 'Delivery' }}
        </span>
      </div>
      
      <!-- Timer -->
      <div 
        class="flex items-center gap-1 px-2 py-1 rounded-lg text-sm font-medium"
        :class="item.alerta ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
      >
        <Icon name="clock" :size="14" />
        <span>{{ item.minutos_transcurridos || 0 }} min</span>
      </div>
    </div>

    <!-- Product info -->
    <div class="space-y-2">
      <div class="flex items-center gap-3">
        <span class="text-2xl font-bold text-primary-600">
          {{ item.cantidad }}x
        </span>
        <div>
          <h4 class="font-semibold text-gray-900">{{ item.producto_nombre }}</h4>
          <p v-if="item.item_notas" class="text-sm text-amber-600 italic flex items-center gap-1">
            <Icon name="clipboard" :size="12" />
            {{ item.item_notas }}
          </p>
        </div>
      </div>
    </div>

    <!-- Action hint -->
    <div class="mt-4 pt-3 border-t border-gray-100 text-center">
      <span class="text-sm text-gray-500 flex items-center justify-center gap-1">
        <Icon name="chevron-right" :size="14" />
        {{ getActionLabel }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Icon from '@/components/ui/Icon.vue'

const props = defineProps({
  item: { type: Object, required: true },
  status: { type: String, default: 'pendiente' }
})

defineEmits(['action'])

const getActionLabel = computed(() => {
  switch (props.status) {
    case 'pendiente':
      return 'Click para empezar a preparar'
    case 'preparando':
      return 'Click cuando esté listo'
    case 'listo':
      return 'Click cuando se entregue'
    default:
      return ''
  }
})
</script>
