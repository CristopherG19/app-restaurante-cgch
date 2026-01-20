<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
          <Icon name="package" :size="28" class="text-primary-500" />
          Gestión de Productos
        </h2>
        <p class="text-gray-500">{{ productos.length }} productos registrados</p>
      </div>
      
      <button @click="openModal()" class="btn-primary">
        <Icon name="plus" :size="18" />
        Nuevo Producto
      </button>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="flex flex-wrap gap-4 items-center">
        <!-- Search -->
        <div class="flex-1 min-w-64">
          <div class="relative">
            <Icon name="search" :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Buscar por nombre o código..."
              class="input pl-10"
              @input="debouncedSearch"
            />
          </div>
        </div>
        
        <!-- Category filter -->
        <select v-model="filtroCategoria" class="input w-48" @change="fetchProductos">
          <option :value="null">Todas las categorías</option>
          <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
            {{ cat.nombre }}
          </option>
        </select>

        <!-- Availability filter -->
        <select v-model="filtroDisponible" class="input w-40" @change="fetchProductos">
          <option :value="null">Todos</option>
          <option :value="true">Disponibles</option>
          <option :value="false">No disponibles</option>
        </select>
      </div>
    </div>

    <!-- Products Table -->
    <div class="card overflow-hidden p-0">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Producto</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Categoría</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Precio</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Stock</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Disponible</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr 
            v-for="producto in productos" 
            :key="producto.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div 
                  v-if="producto.imagen" 
                  class="w-12 h-12 rounded-lg bg-cover bg-center"
                  :style="{ backgroundImage: `url(${producto.imagen})` }"
                ></div>
                <div v-else class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                  <Icon name="image" :size="20" class="text-gray-400" />
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ producto.nombre }}</p>
                  <p class="text-sm text-gray-500">{{ producto.codigo || 'Sin código' }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <span 
                v-if="producto.categoria_nombre"
                class="badge"
                :style="{ backgroundColor: producto.categoria_color + '20', color: producto.categoria_color }"
              >
                {{ producto.categoria_nombre }}
              </span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="px-4 py-3 text-right">
              <span class="font-bold text-primary-600">S/ {{ parseFloat(producto.precio).toFixed(2) }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <span 
                :class="producto.stock <= producto.stock_minimo ? 'text-red-600 font-bold' : 'text-gray-700'"
              >
                {{ producto.stock }}
              </span>
              <span v-if="producto.stock <= producto.stock_minimo" class="ml-1">
                <Icon name="alert-triangle" :size="14" class="text-red-500 inline" />
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <button 
                @click="toggleDisponible(producto)"
                class="w-10 h-6 rounded-full transition-colors relative"
                :class="producto.disponible ? 'bg-accent-500' : 'bg-gray-300'"
              >
                <span 
                  class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform"
                  :class="producto.disponible ? 'left-5' : 'left-1'"
                ></span>
              </button>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex gap-1 justify-end">
                <button 
                  @click="openModal(producto)" 
                  class="btn-icon text-gray-500 hover:text-primary-600"
                  title="Editar"
                >
                  <Icon name="edit" :size="18" />
                </button>
                <button 
                  @click="confirmDelete(producto)" 
                  class="btn-icon text-gray-500 hover:text-red-600"
                  title="Eliminar"
                >
                  <Icon name="trash-2" :size="18" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty state -->
      <div v-if="productos.length === 0 && !loading" class="text-center py-12">
        <Icon name="package" :size="48" class="mx-auto text-gray-300" />
        <p class="text-gray-500 mt-4">No se encontraron productos</p>
        <button @click="openModal()" class="btn-primary mt-4">
          <Icon name="plus" :size="18" />
          Agregar Producto
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-gray-500 mt-4">Cargando productos...</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex justify-center gap-2">
      <button 
        @click="changePage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="btn-secondary"
      >
        <Icon name="chevron-left" :size="18" />
      </button>
      <span class="px-4 py-2 text-gray-600">
        Página {{ currentPage }} de {{ totalPages }}
      </span>
      <button 
        @click="changePage(currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="btn-secondary"
      >
        <Icon name="chevron-right" :size="18" />
      </button>
    </div>

    <!-- Product Modal -->
    <Teleport v-if="showModal" to="body">
      <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
          <!-- Modal Header -->
          <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <Icon :name="editingProduct ? 'edit' : 'plus'" :size="24" class="text-primary-500" />
                {{ editingProduct ? 'Editar Producto' : 'Nuevo Producto' }}
              </h2>
              <button @click="closeModal" class="btn-icon text-gray-400">
                <Icon name="x" :size="20" />
              </button>
            </div>
          </div>

          <!-- Modal Content -->
          <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
            <div class="grid grid-cols-2 gap-4">
              <!-- Código -->
              <div>
                <label class="label">Código</label>
                <input v-model="form.codigo" type="text" class="input" placeholder="SKU001" />
              </div>
              
              <!-- Nombre -->
              <div>
                <label class="label">Nombre *</label>
                <input v-model="form.nombre" type="text" class="input" placeholder="Nombre del producto" />
              </div>
            </div>

            <!-- Descripción -->
            <div>
              <label class="label">Descripción</label>
              <textarea v-model="form.descripcion" class="input" rows="2" placeholder="Descripción opcional"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Categoría -->
              <div>
                <label class="label">Categoría</label>
                <select v-model="form.id_categoria" class="input">
                  <option :value="null">Sin categoría</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.nombre }}
                  </option>
                </select>
              </div>

              <!-- Unidad de medida -->
              <div>
                <label class="label">Unidad</label>
                <select v-model="form.unidad_medida" class="input">
                  <option value="UNIDAD">Unidad</option>
                  <option value="KG">Kilogramo</option>
                  <option value="LT">Litro</option>
                  <option value="PORCION">Porción</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <!-- Precio -->
              <div>
                <label class="label">Precio (S/) *</label>
                <input v-model.number="form.precio" type="number" step="0.01" class="input" placeholder="0.00" />
              </div>

              <!-- Costo -->
              <div>
                <label class="label">Costo (S/)</label>
                <input v-model.number="form.costo" type="number" step="0.01" class="input" placeholder="0.00" />
              </div>

              <!-- Tiempo preparación -->
              <div>
                <label class="label">Tiempo prep. (min)</label>
                <input v-model.number="form.tiempo_preparacion" type="number" class="input" placeholder="15" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Stock -->
              <div>
                <label class="label">Stock *</label>
                <input v-model.number="form.stock" type="number" class="input" placeholder="0" />
              </div>

              <!-- Stock mínimo -->
              <div>
                <label class="label">Stock mínimo</label>
                <input v-model.number="form.stock_minimo" type="number" class="input" placeholder="5" />
              </div>
            </div>

            <!-- Imagen URL -->
            <div>
              <label class="label">URL de Imagen</label>
              <input v-model="form.imagen" type="url" class="input" placeholder="https://..." />
            </div>

            <!-- Opciones -->
            <div class="flex gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.disponible" type="checkbox" class="w-4 h-4 accent-primary-500" />
                <span>Disponible para venta</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.es_combo" type="checkbox" class="w-4 h-4 accent-primary-500" />
                <span>Es combo</span>
              </label>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="p-6 border-t border-gray-100 flex gap-3">
            <button @click="closeModal" class="btn-secondary flex-1">
              Cancelar
            </button>
            <button @click="saveProduct" :disabled="saving" class="btn-success flex-1">
              <span v-if="saving">Guardando...</span>
              <span v-else class="flex items-center gap-2">
                <Icon name="check" :size="18" />
                {{ editingProduct ? 'Actualizar' : 'Crear' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { productosApi, categoriasApi } from '@/services/api'
import Icon from '@/components/ui/Icon.vue'

// State
const productos = ref([])
const categorias = ref([])
const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const editingProduct = ref(null)

// Filters
const searchQuery = ref('')
const filtroCategoria = ref(null)
const filtroDisponible = ref(null)

// Pagination
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = 20

// Form
const defaultForm = {
  codigo: '',
  nombre: '',
  descripcion: '',
  id_categoria: null,
  precio: 0,
  costo: 0,
  stock: 0,
  stock_minimo: 5,
  unidad_medida: 'UNIDAD',
  imagen: '',
  es_combo: false,
  tiempo_preparacion: 15,
  disponible: true
}
const form = reactive({ ...defaultForm })

// Debounce for search
let searchTimeout = null
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchProductos()
  }, 300)
}

// Fetch products
async function fetchProductos() {
  loading.value = true
  try {
    const params = {
      pagina: currentPage.value,
      por_pagina: perPage
    }
    
    if (searchQuery.value) params.buscar = searchQuery.value
    if (filtroCategoria.value) params.categoria = filtroCategoria.value
    if (filtroDisponible.value !== null) params.disponible = filtroDisponible.value
    
    const { data } = await productosApi.getAll(params)
    
    if (data.success) {
      productos.value = data.data
      totalPages.value = data.pagination?.total_pages || 1
    }
  } catch (error) {
    console.error('Error fetching products:', error)
  } finally {
    loading.value = false
  }
}

// Fetch categories
async function fetchCategorias() {
  try {
    const { data } = await categoriasApi.getAll()
    if (data.success) {
      categorias.value = data.data
    }
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
}

// Toggle disponible
async function toggleDisponible(producto) {
  try {
    await productosApi.update(producto.id, { disponible: !producto.disponible })
    producto.disponible = !producto.disponible
  } catch (error) {
    console.error('Error updating product:', error)
    alert('Error al actualizar disponibilidad')
  }
}

// Open modal
function openModal(producto = null) {
  if (producto) {
    editingProduct.value = producto
    Object.assign(form, {
      codigo: producto.codigo || '',
      nombre: producto.nombre,
      descripcion: producto.descripcion || '',
      id_categoria: producto.id_categoria,
      precio: producto.precio,
      costo: producto.costo || 0,
      stock: producto.stock,
      stock_minimo: producto.stock_minimo || 5,
      unidad_medida: producto.unidad_medida || 'UNIDAD',
      imagen: producto.imagen || '',
      es_combo: !!producto.es_combo,
      tiempo_preparacion: producto.tiempo_preparacion || 15,
      disponible: !!producto.disponible
    })
  } else {
    editingProduct.value = null
    Object.assign(form, defaultForm)
  }
  showModal.value = true
}

// Close modal
function closeModal() {
  showModal.value = false
  editingProduct.value = null
}

// Save product
async function saveProduct() {
  if (!form.nombre || !form.precio) {
    alert('Nombre y precio son requeridos')
    return
  }
  
  saving.value = true
  try {
    if (editingProduct.value) {
      await productosApi.update(editingProduct.value.id, form)
    } else {
      await productosApi.create(form)
    }
    closeModal()
    fetchProductos()
  } catch (error) {
    console.error('Error saving product:', error)
    alert(error.response?.data?.message || 'Error al guardar producto')
  } finally {
    saving.value = false
  }
}

// Confirm delete
function confirmDelete(producto) {
  if (confirm(`¿Eliminar "${producto.nombre}"?`)) {
    deleteProduct(producto)
  }
}

// Delete product
async function deleteProduct(producto) {
  try {
    await productosApi.delete(producto.id)
    fetchProductos()
  } catch (error) {
    console.error('Error deleting product:', error)
    alert('Error al eliminar producto')
  }
}

// Change page
function changePage(page) {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    fetchProductos()
  }
}

// Init
onMounted(() => {
  fetchProductos()
  fetchCategorias()
})
</script>
