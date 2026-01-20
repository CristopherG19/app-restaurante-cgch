import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { mesasApi, zonasApi } from '@/services/api'

export const useTablesStore = defineStore('tables', () => {
  const mesas = ref([])
  const zonas = ref([])
  const loading = ref(false)
  const selectedMesa = ref(null)
  const filtroZona = ref(null)

  const mesasFiltradas = computed(() => {
    if (!filtroZona.value) return mesas.value
    return mesas.value.filter(m => m.id_zona === filtroZona.value)
  })

  const mesasLibres = computed(() => 
    mesas.value.filter(m => m.estado === 'libre').length
  )

  const mesasOcupadas = computed(() => 
    mesas.value.filter(m => m.estado === 'ocupada').length
  )

  async function fetchMesas() {
    loading.value = true
    try {
      const { data } = await mesasApi.getAll()
      if (data.success) {
        mesas.value = data.data
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchZonas() {
    try {
      const { data } = await zonasApi.getAll()
      if (data.success) {
        zonas.value = data.data
      }
    } catch (error) {
      console.error('Error fetching zones:', error)
    }
  }

  async function updateEstado(mesaId, estado) {
    try {
      await mesasApi.updateEstado(mesaId, estado)
      const mesa = mesas.value.find(m => m.id === mesaId)
      if (mesa) mesa.estado = estado
    } catch (error) {
      console.error('Error updating table status:', error)
    }
  }

  function selectMesa(mesa) {
    selectedMesa.value = mesa
  }

  function clearSelection() {
    selectedMesa.value = null
  }

  return {
    mesas,
    zonas,
    loading,
    selectedMesa,
    filtroZona,
    mesasFiltradas,
    mesasLibres,
    mesasOcupadas,
    fetchMesas,
    fetchZonas,
    updateEstado,
    selectMesa,
    clearSelection
  }
})
