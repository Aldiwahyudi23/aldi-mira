<template>
  <div class="mb-6">
    <label v-if="label" :for="id" class="block text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <span v-if="icon" :class="iconClass">{{ icon }}</span>
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

  <div class="relative">
    <input
      ref="inputRef"
      :id="id"
      type="text"
      :value="displayValue"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      @keydown="handleKeydown"
      :placeholder="placeholder"
      :disabled="disabled"
      class="w-full border border-gray-200 rounded-2xl p-4 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 transition-all duration-300 bg-white/50 backdrop-blur-sm font-mono"
      :class="{
        'pl-20': icon && showCurrency,   // Jika ada dua elemen di kiri
        'pl-12': (icon && !showCurrency) || (!icon && showCurrency),
        'opacity-50 cursor-not-allowed': disabled,
        'border-red-300': error
      }"
    />

     <div v-if="icon" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                {{ icon }}
            </div>

    <button
      v-if="showCopyButton && displayValue"
      @click="copyToClipboard"
      class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-pink-500 transition-colors"
      type="button"
      :title="copied ? 'Tersalin!' : 'Salin ke clipboard'"
    >
      {{ copied ? '✅' : '📋' }}
    </button>
  </div>


    <p v-if="error" class="mt-2 text-sm text-red-500 flex items-center gap-2">
      ⚠️ {{ error }}
    </p>
    <p v-if="helper" class="mt-2 text-sm text-gray-500 flex items-center gap-2">
      💡 {{ helper }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  placeholder: String,
  required: Boolean,
  disabled: Boolean,
  error: String,
  helper: String,
  id: String,
  showCurrency: { type: Boolean, default: true },
  allowDecimal: { type: Boolean, default: false },
  decimalPlaces: { type: Number, default: 0 },
  icon: { type: String, default: '💰' },
  iconClass: { type: String, default: 'text-pink-400' },
  showCopyButton: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
const isFocused = ref(false)
const rawValue = ref('')
const copied = ref(false)

/**
 * Format angka ke format Rupiah yang benar
 * Pastikan nilai float seperti 50000.00 tidak berubah jadi 5.000.000
 */
const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return ''

  // Pastikan angka dikonversi dengan benar
  let numberValue = Number(value)
  if (isNaN(numberValue)) return ''

  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: props.allowDecimal ? props.decimalPlaces : 0,
    maximumFractionDigits: props.allowDecimal ? props.decimalPlaces : 0
  }).format(numberValue)
}

/**
 * Ubah tampilan input jadi angka murni
 */
const parseCurrency = (formatted) => {
  if (!formatted) return 0
  const clean = formatted.replace(/[^\d]/g, '')
  return parseInt(clean) || 0
}

/**
 * Nilai yang ditampilkan di input
 */
const displayValue = computed(() => {
  if (isFocused.value && rawValue.value !== '') {
    return formatCurrency(rawValue.value)
  }
  if (props.modelValue || props.modelValue === 0) {
    return formatCurrency(props.modelValue)
  }
  return ''
})

/**
 * Handle input real-time
 */
const handleInput = (e) => {
  let value = e.target.value
  const cursorPosition = e.target.selectionStart

  const clean = value.replace(/[^\d]/g, '')
  rawValue.value = clean

  const numericValue = parseInt(clean) || 0
  emit('update:modelValue', numericValue)

  nextTick(() => {
    const formatted = formatCurrency(clean)
    let newCursor = cursorPosition
    if (formatted.length !== value.length) {
      const diff = formatted.length - value.length
      newCursor = cursorPosition + diff
    }
    newCursor = Math.min(newCursor, formatted.length)
    e.target.setSelectionRange(newCursor, newCursor)
  })
}

/**
 * Fokus & Blur
 */
const handleFocus = (e) => {
  isFocused.value = true
  // Ambil nilai asli tanpa titik/koma
  const val = props.modelValue ? Number(props.modelValue).toString() : ''
  rawValue.value = val.replace(/[^\d]/g, '')
  nextTick(() => e.target.select())
}

const handleBlur = () => {
  isFocused.value = false
  rawValue.value = ''
}

/**
 * Batasi input hanya angka
 */
const handleKeydown = (e) => {
  if ([8, 9, 13, 27, 46, 35, 36, 37, 38, 39, 40].includes(e.keyCode)) return
  if ((e.ctrlKey || e.metaKey) && [65, 67, 86, 88].includes(e.keyCode)) return
  if ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) return
  e.preventDefault()
}

/**
 * Saat modelValue berubah dari luar (misal edit)
 * Pastikan tidak menambah nol berlebih
 */
watch(
  () => props.modelValue,
  (newVal) => {
    if (!isFocused.value) {
      // Jika ada desimal .00 dari database, buang bagian itu
      const cleanVal = Number(newVal)
      if (!isNaN(cleanVal)) rawValue.value = cleanVal.toString()
    }
  },
  { immediate: true }
)

/**
 * Copy ke clipboard
 */
async function copyToClipboard() {
  try {
    await navigator.clipboard.writeText(props.modelValue ?? '')
    copied.value = true
    setTimeout(() => (copied.value = false), 1200)
  } catch (err) {
    console.error(err)
  }
}

defineExpose({
  focus: () => inputRef.value?.focus()
})
</script>
