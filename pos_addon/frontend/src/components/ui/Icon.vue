<template>
  <component 
    :is="iconComponent" 
    :size="size" 
    :stroke-width="strokeWidth" 
    :class="className"
  />
</template>

<script setup>
import { computed } from 'vue'
import {
  // Navigation & Layout
  LayoutDashboard,
  ChevronLeft,
  ChevronRight,
  Menu,
  X,
  
  // Restaurant & Food
  ChefHat,
  UtensilsCrossed,
  Utensils,
  Salad,
  Soup,
  Beef,
  Pizza,
  Fish,
  Coffee,
  Wine,
  IceCream,
  Star,
  GlassWater,
  
  // Commerce & Money
  ShoppingCart,
  CreditCard,
  Wallet,
  Banknote,
  DollarSign,
  Receipt,
  
  // Status & Alerts
  AlertTriangle,
  CheckCircle,
  Check,
  Clock,
  Flame,
  Sparkles,
  TrendingUp,
  
  // Actions
  Search,
  RefreshCw,
  LogOut,
  Lock,
  Unlock,
  Plus,
  Minus,
  Trash2,
  Edit,
  Eye,
  EyeOff,
  Loader2,
  
  // User roles
  User,
  UserCog,
  
  // Objects
  Package,
  Settings,
  ClipboardList,
  Users,
  Smartphone,
  ImageOff,
  
  // Shapes (for table forms)
  Circle,
  Square,
  RectangleHorizontal,
} from 'lucide-vue-next'

const props = defineProps({
  name: {
    type: String,
    required: true
  },
  size: {
    type: [Number, String],
    default: 20
  },
  strokeWidth: {
    type: [Number, String],
    default: 2
  },
  className: {
    type: String,
    default: ''
  }
})

// Map icon names to components
const iconMap = {
  // Navigation & Layout
  'dashboard': LayoutDashboard,
  'chevron-left': ChevronLeft,
  'chevron-right': ChevronRight,
  'menu': Menu,
  'x': X,
  'close': X,
  
  // Restaurant & Food
  'chef': ChefHat,
  'chef-hat': ChefHat,
  'utensils': UtensilsCrossed,
  'utensils-crossed': UtensilsCrossed,
  'fork-knife': Utensils,
  'salad': Salad,
  'entradas': Salad,
  'soup': Soup,
  'sopas': Soup,
  'beef': Beef,
  'meat': Beef,
  'parrilla': Beef,
  'carnes': Beef,
  'pizza': Pizza,
  'pasta': Pizza,
  'fish': Fish,
  'seafood': Fish,
  'mariscos': Fish,
  'pescados': Fish,
  'coffee': Coffee,
  'bebidas': GlassWater,
  'drinks': GlassWater,
  'wine': Wine,
  'cocktail': Wine,
  'cocteles': Wine,
  'ice-cream': IceCream,
  'postre': IceCream,
  'dessert': IceCream,
  'star': Star,
  'combo': Star,
  'combos': Star,
  
  // Commerce & Money
  'cart': ShoppingCart,
  'shopping-cart': ShoppingCart,
  'credit-card': CreditCard,
  'card': CreditCard,
  'wallet': Wallet,
  'cash': Banknote,
  'banknote': Banknote,
  'money': DollarSign,
  'dollar': DollarSign,
  'receipt': Receipt,
  
  // Status & Alerts
  'alert': AlertTriangle,
  'warning': AlertTriangle,
  'check-circle': CheckCircle,
  'success': CheckCircle,
  'check': Check,
  'clock': Clock,
  'pending': Clock,
  'flame': Flame,
  'fire': Flame,
  'sparkles': Sparkles,
  'trending': TrendingUp,
  'trending-up': TrendingUp,
  
  // Actions
  'search': Search,
  'refresh': RefreshCw,
  'logout': LogOut,
  'log-out': LogOut,
  'lock': Lock,
  'unlock': Unlock,
  'plus': Plus,
  'add': Plus,
  'minus': Minus,
  'trash': Trash2,
  'delete': Trash2,
  'edit': Edit,
  'eye': Eye,
  'eye-off': EyeOff,
  'loader': Loader2,
  'loading': Loader2,
  
  // User roles
  'user': User,
  'user-cog': UserCog,
  'admin': UserCog,
  
  // Objects
  'package': Package,
  'box': Package,
  'settings': Settings,
  'config': Settings,
  'clipboard': ClipboardList,
  'list': ClipboardList,
  'users': Users,
  'people': Users,
  'phone': Smartphone,
  'smartphone': Smartphone,
  'image-off': ImageOff,
  'no-image': ImageOff,
  
  // Shapes (for table forms)
  'circle': Circle,
  'round': Circle,
  'square': Square,
  'rectangle': RectangleHorizontal,
}

const iconComponent = computed(() => {
  const icon = iconMap[props.name]
  if (!icon) {
    console.warn(`Icon "${props.name}" not found in icon map`)
    return null
  }
  return icon
})
</script>
