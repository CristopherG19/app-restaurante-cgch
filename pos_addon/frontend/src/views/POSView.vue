<template>
  <div class="h-[calc(100vh-7rem)] flex gap-4 overflow-hidden">
    <!-- Products Section (Left) -->
    <div class="flex-1 min-w-0 flex flex-col">
      <!-- Search and Categories -->
      <div class="mb-4">
        <div class="flex gap-4 mb-4">
          <div class="relative flex-1">
            <input 
              v-model="searchQuery"
              type="text" 
              class="input pl-10"
              placeholder="Buscar productos..."
            />
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
              <Icon name="search" :size="18" />
            </span>
          </div>
        </div>
        
        <!-- Category tabs -->
        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-thin">
          <button 
            @click="selectedCategory = null"
            class="px-4 py-2 rounded-lg whitespace-nowrap transition-colors font-medium"
            :class="selectedCategory === null 
              ? 'bg-primary-500 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          >
            Todos
          </button>
          <button 
            v-for="cat in categories"
            :key="cat.id"
            @click="selectedCategory = cat.id"
            class="px-4 py-2 rounded-lg whitespace-nowrap transition-colors font-medium flex items-center gap-2"
            :class="selectedCategory === cat.id 
              ? 'text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            :style="selectedCategory === cat.id ? { backgroundColor: cat.color } : {}"
          >
            <Icon :name="getCategoryIcon(cat.nombre)" :size="16" />
            <span>{{ cat.nombre }}</span>
          </button>
        </div>
      </div>

      <!-- Products Grid -->
      <div class="flex-1 overflow-y-auto scrollbar-thin">
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
          <div 
            v-for="product in filteredProducts"
            :key="product.id"
            @click="addToCart(product)"
            class="card-hover cursor-pointer group"
          >
            <div class="aspect-square bg-gray-100 rounded-lg mb-3 overflow-hidden flex items-center justify-center">
              <img 
                v-if="product.imagen" 
                :src="product.imagen" 
                :alt="product.nombre"
                class="w-full h-full object-cover"
              />
              <Icon v-else name="image-off" :size="40" class="text-gray-300" />
            </div>
            <h3 class="font-medium text-gray-800 text-sm line-clamp-2 mb-1">{{ product.nombre }}</h3>
            <div class="flex items-center justify-between">
              <p class="text-primary-600 font-bold">S/ {{ parseFloat(product.precio).toFixed(2) }}</p>
              <span 
                v-if="product.stock <= product.stock_minimo && product.stock_minimo > 0"
                class="badge-warning text-xs"
              >
                Stock bajo
              </span>
            </div>
          </div>
        </div>
        
        <p v-if="filteredProducts.length === 0" class="text-center text-gray-500 py-12">
          No se encontraron productos
        </p>
      </div>
    </div>

    <!-- Cart Section (Right) -->
    <div class="w-80 min-w-[280px] flex-shrink-0 flex flex-col bg-white rounded-2xl shadow-card">
      <!-- Cart Header -->
      <div class="p-4 border-b border-gray-100">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-lg flex items-center gap-2">
            <Icon name="cart" :size="20" class="text-primary-500" />
            Carrito 
            <span v-if="cartStore.itemCount > 0" class="text-primary-500">({{ cartStore.itemCount }})</span>
          </h2>
          <button 
            v-if="!cartStore.isEmpty"
            @click="cartStore.clearCart()"
            class="text-sm text-red-500 hover:text-red-600"
          >
            Vaciar
          </button>
        </div>
        
        <!-- Service type -->
        <div class="flex gap-2 mt-3">
          <button 
            @click="cartStore.tipoServicio = 'mesa'"
            class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2"
            :class="cartStore.tipoServicio === 'mesa' 
              ? 'bg-primary-500 text-white' 
              : 'bg-gray-100 text-gray-700'"
          >
            <Icon name="utensils" :size="16" />
            Mesa
          </button>
          <button 
            @click="cartStore.tipoServicio = 'llevar'"
            class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2"
            :class="cartStore.tipoServicio === 'llevar' 
              ? 'bg-primary-500 text-white' 
              : 'bg-gray-100 text-gray-700'"
          >
            <Icon name="package" :size="16" />
            Llevar
          </button>
        </div>
      </div>

      <!-- Cart Items -->
      <div class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-thin">
        <div v-if="cartStore.isEmpty" class="text-center text-gray-400 py-12">
          <Icon name="cart" :size="48" class="mx-auto text-gray-300" />
          <p class="mt-3">Carrito vacío</p>
          <p class="text-sm">Agrega productos para comenzar</p>
        </div>

        <div 
          v-for="item in cartStore.items" 
          :key="item.id"
          class="bg-gray-50 rounded-xl p-3"
        >
          <div class="flex gap-3">
            <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
              <img v-if="item.imagen" :src="item.imagen" class="w-full h-full object-cover rounded-lg" />
              <Icon v-else name="image-off" :size="20" class="text-gray-400" />
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="font-medium text-gray-800 text-sm truncate">{{ item.nombre }}</h4>
              <p class="text-primary-600 font-semibold text-sm">S/ {{ item.precio.toFixed(2) }}</p>
            </div>
            <button 
              @click="cartStore.removeItem(item.id)"
              class="text-red-400 hover:text-red-600 p-1"
            >
              <Icon name="x" :size="18" />
            </button>
          </div>
          
          <!-- Quantity controls -->
          <div class="flex items-center justify-between mt-2">
            <div class="flex items-center gap-2">
              <button 
                @click="cartStore.updateQuantity(item.id, item.cantidad - 1)"
                class="w-8 h-8 rounded-lg bg-gray-200 hover:bg-gray-300 flex items-center justify-center"
              >
                <Icon name="minus" :size="14" />
              </button>
              <span class="w-8 text-center font-medium">{{ item.cantidad }}</span>
              <button 
                @click="cartStore.updateQuantity(item.id, item.cantidad + 1)"
                class="w-8 h-8 rounded-lg bg-gray-200 hover:bg-gray-300 flex items-center justify-center"
              >
                <Icon name="plus" :size="14" />
              </button>
            </div>
            <span class="font-semibold text-gray-800">
              S/ {{ (item.precio * item.cantidad).toFixed(2) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Cart Footer -->
      <div class="p-4 border-t border-gray-100 space-y-3">
        <!-- Totals -->
        <div class="space-y-2 text-sm">
          <div class="flex justify-between text-gray-600">
            <span>Subtotal</span>
            <span>S/ {{ cartStore.subtotal.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>IGV (18%)</span>
            <span>S/ {{ cartStore.igv.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
            <span>Total</span>
            <span>S/ {{ cartStore.total.toFixed(2) }}</span>
          </div>
        </div>

        <!-- Actions -->
        <!-- Send to Kitchen button (only for table orders) -->
        <button 
          v-if="cartStore.mesa && !cartStore.isEmpty"
          @click="enviarACocina"
          class="btn-secondary w-full py-3 text-lg mb-2"
        >
          <Icon name="chef" :size="20" />
          Enviar a Cocina
        </button>

        <!-- Payment button -->
        <button 
          @click="openPaymentModal"
          :disabled="cartStore.isEmpty"
          class="btn-primary w-full py-3 text-lg"
        >
          <Icon name="credit-card" :size="20" />
          Cobrar S/ {{ cartStore.total.toFixed(2) }}
        </button>
      </div>
    </div>

    <!-- Payment Modal -->
    <PaymentModal 
      v-if="showPaymentModal"
      @close="showPaymentModal = false"
      @success="handlePaymentSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { productosApi, categoriasApi, comandasApi } from '@/services/api'
import { useCartStore } from '@/stores/cart'
import PaymentModal from '@/components/pos/PaymentModal.vue'
import Icon from '@/components/ui/Icon.vue'

const cartStore = useCartStore()

const products = ref([])
const categories = ref([])
const searchQuery = ref('')
const selectedCategory = ref(null)
const showPaymentModal = ref(false)

const filteredProducts = computed(() => {
  let result = products.value

  if (selectedCategory.value) {
    result = result.filter(p => p.id_categoria === selectedCategory.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(p => 
      p.nombre.toLowerCase().includes(query) ||
      p.codigo?.toLowerCase().includes(query)
    )
  }

  return result
})

function addToCart(product) {
  cartStore.addItem(product)
}

// Mapeo de nombres de categorías a iconos
function getCategoryIcon(nombre) {
  const nombreLower = nombre.toLowerCase()
  
  if (nombreLower.includes('entrada')) return 'salad'
  if (nombreLower.includes('sopa') || nombreLower.includes('crema')) return 'soup'
  if (nombreLower.includes('plato') || nombreLower.includes('fuerte')) return 'utensils'
  if (nombreLower.includes('parrilla') || nombreLower.includes('carne')) return 'beef'
  if (nombreLower.includes('pasta')) return 'pizza'
  if (nombreLower.includes('pescado') || nombreLower.includes('marisco')) return 'fish'
  if (nombreLower.includes('bebida')) return 'bebidas'
  if (nombreLower.includes('cóctel') || nombreLower.includes('coctel')) return 'wine'
  if (nombreLower.includes('postre')) return 'ice-cream'
  if (nombreLower.includes('combo')) return 'star'
  
  return 'utensils' // Default
}

function openPaymentModal() {
  if (!cartStore.isEmpty) {
    showPaymentModal.value = true
  }
}

async function enviarACocina() {
  if (cartStore.isEmpty || !cartStore.mesa) return
  
  try {
    // Si ya hay una comanda, agregar items
    if (cartStore.comanda) {
      // Agregar cada item nuevo a la comanda
      for (const item of cartStore.items) {
        await comandasApi.addItem(cartStore.comanda.id, {
          id_producto: item.id,
          cantidad: item.cantidad,
          precio_unitario: item.precio,
          notas: item.notas || null
        })
      }
      
      // Enviar a cocina
      await comandasApi.enviarCocina(cartStore.comanda.id)
    } else {
      // Crear nueva comanda con los items
      const { data } = await comandasApi.create({
        id_mesa: cartStore.mesa.id,
        comensales: 1,
        items: cartStore.items.map(item => ({
          id_producto: item.id,
          cantidad: item.cantidad,
          precio_unitario: item.precio,
          notas: item.notas || null
        }))
      })
      
      if (data.success) {
        // Guardar la comanda en el store
        cartStore.setComanda(data.data)
        
        // Enviar a cocina
        await comandasApi.enviarCocina(data.data.id)
      }
    }
    
    // Limpiar carrito después de enviar
    cartStore.clearCart()
    alert('Pedido enviado a cocina exitosamente')
  } catch (error) {
    console.error('Error sending to kitchen:', error)
    alert('Error al enviar a cocina: ' + (error.response?.data?.message || error.message))
  }
}

function handlePaymentSuccess() {
  showPaymentModal.value = false
  cartStore.clearCart()
}

async function fetchProducts() {
  try {
    const { data } = await productosApi.getAll({ disponible: 'true' })
    if (data.success) {
      products.value = data.data
    }
  } catch (error) {
    console.error('Error fetching products:', error)
  }
}

async function fetchCategories() {
  try {
    const { data } = await categoriasApi.getAll()
    if (data.success) {
      categories.value = data.data
    }
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
}

onMounted(() => {
  fetchProducts()
  fetchCategories()
})
</script>
