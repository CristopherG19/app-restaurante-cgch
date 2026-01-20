import { defineStore } from 'pinia'
import { ref } from 'vue'
import { comandasApi } from '@/services/api'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref([])
  const kitchenItems = ref([])
  const kitchenGrouped = ref({ pendientes: [], preparando: [], listos: [] })
  const loading = ref(false)
  const alertTime = ref(15)

  async function fetchOrders(params = {}) {
    loading.value = true
    try {
      const { data } = await comandasApi.getAll(params)
      if (data.success) {
        orders.value = data.data
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchKitchen() {
    try {
      const { data } = await comandasApi.getCocina()
      if (data.success) {
        kitchenItems.value = data.data.items
        kitchenGrouped.value = {
          pendientes: data.data.grouped.pendientes || [],
          preparando: data.data.grouped.preparando || [],
          listos: data.data.grouped.listos || []
        }
        alertTime.value = data.data.alert_time
      }
    } catch (error) {
      console.error('Error fetching kitchen data:', error)
    }
  }

  async function createOrder(orderData) {
    try {
      const { data } = await comandasApi.create(orderData)
      if (data.success) {
        return data.data
      }
    } catch (error) {
      console.error('Error creating order:', error)
      throw error
    }
  }

  async function addItemToOrder(orderId, item) {
    try {
      const { data } = await comandasApi.addItem(orderId, item)
      return data.success
    } catch (error) {
      console.error('Error adding item:', error)
      return false
    }
  }

  async function sendToKitchen(orderId) {
    try {
      const { data } = await comandasApi.enviarCocina(orderId)
      return data.success
    } catch (error) {
      console.error('Error sending to kitchen:', error)
      return false
    }
  }

  async function updateItemStatus(itemId, status) {
    try {
      await comandasApi.updateItemEstado(itemId, status)
      // Refresh kitchen data
      await fetchKitchen()
      return true
    } catch (error) {
      console.error('Error updating item status:', error)
      return false
    }
  }

  async function updateOrderStatus(orderId, status) {
    try {
      await comandasApi.updateEstado(orderId, status)
      return true
    } catch (error) {
      console.error('Error updating order status:', error)
      return false
    }
  }

  return {
    orders,
    kitchenItems,
    kitchenGrouped,
    loading,
    alertTime,
    fetchOrders,
    fetchKitchen,
    createOrder,
    addItemToOrder,
    sendToKitchen,
    updateItemStatus,
    updateOrderStatus
  }
})
