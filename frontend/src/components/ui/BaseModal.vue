<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="show" 
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4" 
        @click.self="handleBackdropClick"
        @keydown.esc="handleEscKey"
        tabindex="-1"
      >
        <div 
          class="bg-white rounded-2xl shadow-2xl overflow-hidden animate-scale-in"
          :class="sizeClasses"
          role="dialog"
          aria-modal="true"
        >
          <!-- Header -->
          <div 
            v-if="$slots.header || title"
            class="p-6"
            :class="[headerClasses, headerTextClasses]"
          >
            <slot name="header">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                  <div v-if="icon" :class="iconBgClasses" class="p-2.5 rounded-xl">
                    <Icon :name="icon" :size="24" :class="iconClasses" />
                  </div>
                  <div>
                    <h2 class="text-xl font-bold">{{ title }}</h2>
                    <p v-if="subtitle" class="opacity-70 text-sm mt-1">{{ subtitle }}</p>
                  </div>
                </div>
                <button 
                  v-if="closable"
                  @click="$emit('close')" 
                  class="btn-icon opacity-60 hover:opacity-100 hover:bg-black/5 transition-all"
                  aria-label="Cerrar modal"
                >
                  <Icon name="x" :size="20" />
                </button>
              </div>
            </slot>
          </div>

          <!-- Content -->
          <div 
            class="p-6"
            :class="contentClasses"
          >
            <slot></slot>
          </div>

          <!-- Footer -->
          <div 
            v-if="$slots.footer"
            class="bg-gray-50 px-6 py-4 flex justify-end gap-3"
          >
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import Icon from '@/components/ui/Icon.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: ''
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'success', 'warning', 'danger', 'info'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl', '2xl'].includes(value)
  },
  closable: {
    type: Boolean,
    default: true
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  closeOnEsc: {
    type: Boolean,
    default: true
  },
  scrollable: {
    type: Boolean,
    default: false
  },
  maxHeight: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close'])

const headerClasses = computed(() => {
  const variants = {
    primary: 'bg-gradient-to-br from-blue-50/90 to-indigo-50/90 backdrop-blur-sm border-b border-blue-100/50',
    success: 'bg-gradient-to-br from-green-50/90 to-emerald-50/90 backdrop-blur-sm border-b border-green-100/50',
    warning: 'bg-gradient-to-br from-amber-50/90 to-orange-50/90 backdrop-blur-sm border-b border-amber-100/50',
    danger: 'bg-gradient-to-br from-red-50/90 to-rose-50/90 backdrop-blur-sm border-b border-red-100/50',
    info: 'bg-gradient-to-br from-gray-50/90 to-slate-50/90 backdrop-blur-sm border-b border-gray-100/50'
  }
  return variants[props.variant]
})

const headerTextClasses = computed(() => {
  const textVariants = {
    primary: 'text-blue-900',
    success: 'text-green-900',
    warning: 'text-amber-900',
    danger: 'text-red-900',
    info: 'text-gray-900'
  }
  return textVariants[props.variant]
})

const iconClasses = computed(() => {
  const iconVariants = {
    primary: 'text-blue-600',
    success: 'text-green-600',
    warning: 'text-amber-600',
    danger: 'text-red-600',
    info: 'text-gray-600'
  }
  return iconVariants[props.variant]
})

const iconBgClasses = computed(() => {
  const bgVariants = {
    primary: 'bg-blue-100/80',
    success: 'bg-green-100/80',
    warning: 'bg-amber-100/80',
    danger: 'bg-red-100/80',
    info: 'bg-gray-100/80'
  }
  return bgVariants[props.variant]
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'max-w-sm w-full',
    md: 'max-w-md w-full',
    lg: 'max-w-lg w-full',
    xl: 'max-w-xl w-full',
    '2xl': 'max-w-2xl w-full'
  }
  return sizes[props.size]
})

const contentClasses = computed(() => {
  const classes = []
  if (props.scrollable) {
    classes.push('overflow-y-auto')
  }
  if (props.maxHeight) {
    classes.push(props.maxHeight)
  }
  return classes.join(' ')
})

function handleBackdropClick() {
  if (props.closeOnBackdrop) {
    emit('close')
  }
}

function handleEscKey(event) {
  if (props.closeOnEsc && event.key === 'Escape') {
    emit('close')
  }
}

// Handle ESC key globally when modal is open
function globalEscHandler(event) {
  if (props.show && props.closeOnEsc && event.key === 'Escape') {
    emit('close')
  }
}

onMounted(() => {
  document.addEventListener('keydown', globalEscHandler)
})

onUnmounted(() => {
  document.removeEventListener('keydown', globalEscHandler)
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white {
  animation: scale-in 0.3s ease;
}

@keyframes scale-in {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
