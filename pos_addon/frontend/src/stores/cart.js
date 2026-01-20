import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const IGV_RATE = 0.18

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const mesa = ref(null)
  const comanda = ref(null)
  const cliente = ref(null)
  const tipoServicio = ref('mesa')
  const descuento = ref(0)

  // Getters
  const itemCount = computed(() => 
    items.value.reduce((sum, item) => sum + item.cantidad, 0)
  )

  const subtotalBruto = computed(() => 
    items.value.reduce((sum, item) => sum + (item.precio * item.cantidad), 0)
  )

  const subtotalConDescuento = computed(() => 
    subtotalBruto.value - descuento.value
  )

  // IGV already included in prices, extract it
  const igv = computed(() => 
    Math.round((subtotalConDescuento.value * IGV_RATE / (1 + IGV_RATE)) * 100) / 100
  )

  const subtotal = computed(() => 
    Math.round((subtotalConDescuento.value - igv.value) * 100) / 100
  )

  const total = computed(() => subtotalConDescuento.value)

  const isEmpty = computed(() => items.value.length === 0)

  // Actions
  function addItem(product) {
    const existing = items.value.find(item => item.id === product.id)
    
    if (existing) {
      existing.cantidad++
    } else {
      items.value.push({
        id: product.id,
        codigo: product.codigo,
        nombre: product.nombre,
        precio: parseFloat(product.precio),
        imagen: product.imagen,
        cantidad: 1,
        notas: ''
      })
    }
  }

  function updateQuantity(productId, quantity) {
    const item = items.value.find(i => i.id === productId)
    if (item) {
      if (quantity <= 0) {
        removeItem(productId)
      } else {
        item.cantidad = quantity
      }
    }
  }

  function removeItem(productId) {
    const index = items.value.findIndex(i => i.id === productId)
    if (index > -1) {
      items.value.splice(index, 1)
    }
  }

  function updateItemNotes(productId, notes) {
    const item = items.value.find(i => i.id === productId)
    if (item) {
      item.notas = notes
    }
  }

  function setMesa(mesaData) {
    mesa.value = mesaData
    tipoServicio.value = mesaData ? 'mesa' : 'llevar'
  }

  function setComanda(comandaData) {
    comanda.value = comandaData
  }

  function setCliente(clienteData) {
    cliente.value = clienteData
  }

  function setDescuento(amount) {
    descuento.value = Math.max(0, Math.min(amount, subtotalBruto.value))
  }

  function clearCart() {
    items.value = []
    mesa.value = null
    comanda.value = null
    cliente.value = null
    descuento.value = 0
    tipoServicio.value = 'mesa'
  }

  // Format for API
  function getItemsForApi() {
    return items.value.map(item => ({
      id_producto: item.id,
      cantidad: item.cantidad,
      precio_unitario: item.precio,
      notas: item.notas
    }))
  }

  return {
    // State
    items,
    mesa,
    comanda,
    cliente,
    tipoServicio,
    descuento,
    
    // Getters
    itemCount,
    subtotalBruto,
    subtotal,
    igv,
    total,
    isEmpty,
    
    // Actions
    addItem,
    updateQuantity,
    removeItem,
    updateItemNotes,
    setMesa,
    setComanda,
    setCliente,
    setDescuento,
    clearCart,
    getItemsForApi
  }
})
