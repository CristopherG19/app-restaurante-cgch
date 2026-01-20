<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-primary-500 rounded-2xl shadow-lg mb-4">
          <Icon name="chef" :size="40" class="text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900">Over Chef</h1>
        <p class="text-gray-600 mt-1">Sistema de Punto de Venta</p>
      </div>

      <!-- Login Card -->
      <div class="card p-8">
        <h2 class="text-xl font-semibold text-center mb-6">Iniciar Sesión</h2>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <!-- Email -->
          <div>
            <label class="label">Correo electrónico</label>
            <input 
              v-model="form.email" 
              type="email" 
              class="input"
              placeholder="correo@ejemplo.com"
              required
              autocomplete="email"
            />
          </div>

          <!-- Password -->
          <div>
            <label class="label">Contraseña</label>
            <div class="relative">
              <input 
                v-model="form.password" 
                :type="showPassword ? 'text' : 'password'" 
                class="input pr-10"
                placeholder="••••••••"
                required
                autocomplete="current-password"
              />
              <button 
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <Icon :name="showPassword ? 'eye-off' : 'eye'" :size="20" />
              </button>
            </div>
          </div>

          <!-- Error message -->
          <div v-if="authStore.error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
            <Icon name="alert" :size="16" />
            {{ authStore.error }}
          </div>

          <!-- Submit -->
          <button 
            type="submit" 
            class="btn-primary w-full py-3"
            :disabled="authStore.loading"
          >
            <Icon v-if="authStore.loading" name="loader" :size="20" class="animate-spin" />
            <span v-else>Ingresar</span>
          </button>
        </form>

        <!-- Demo credentials -->
        <div class="mt-6 pt-6 border-t border-gray-100">
          <p class="text-xs text-gray-500 text-center mb-3">Credenciales de demostración:</p>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <button 
              @click="setDemoCredentials('admin')"
              class="px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 transition-colors flex items-center justify-center gap-2"
            >
              <Icon name="user-cog" :size="14" />
              Admin
            </button>
            <button 
              @click="setDemoCredentials('cajero')"
              class="px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 transition-colors flex items-center justify-center gap-2"
            >
              <Icon name="wallet" :size="14" />
              Cajero
            </button>
            <button 
              @click="setDemoCredentials('mesero')"
              class="px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 transition-colors flex items-center justify-center gap-2"
            >
              <Icon name="utensils" :size="14" />
              Mesero
            </button>
            <button 
              @click="setDemoCredentials('cocina')"
              class="px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 transition-colors flex items-center justify-center gap-2"
            >
              <Icon name="chef" :size="14" />
              Cocina
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-gray-500 text-sm mt-6">
        Over Chef POS v1.0 • Perú
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Icon from '@/components/ui/Icon.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: ''
})

const showPassword = ref(false)

const demoCredentials = {
  admin: { email: 'admin@overchef.pe', password: 'password' },
  cajero: { email: 'cajero@overchef.pe', password: 'password' },
  mesero: { email: 'mesero@overchef.pe', password: 'password' },
  cocina: { email: 'cocina@overchef.pe', password: 'password' }
}

function setDemoCredentials(role) {
  form.email = demoCredentials[role].email
  form.password = demoCredentials[role].password
}

async function handleLogin() {
  const success = await authStore.login(form)
  if (success) {
    router.push('/')
  }
}
</script>
