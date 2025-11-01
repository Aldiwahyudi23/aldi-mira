<template>
  <div class="mb-4 md:mb-6 relative" ref="rootRef">
    <label
      v-if="label"
      :for="id"
      class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-2"
    >
      <span v-if="icon" :class="iconClass">{{ icon }}</span>
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Wrapper -->
    <div
      class="relative cursor-pointer select-none"
      @click="toggleDropdown"
      :class="{ 'opacity-50 pointer-events-none': disabled }"
    >
      <!-- Display area -->
      <div
        class="flex items-center justify-between w-full border border-pink-200 rounded-xl md:rounded-2xl bg-white/70 backdrop-blur-md p-3 md:p-4 shadow-sm hover:shadow-md transition-all duration-300"
        :class="{ 'border-red-300': error }"
      >
        <div class="flex items-center gap-2 text-gray-700 truncate">
          <span v-if="icon" class="text-pink-400 text-base md:text-lg">{{ icon }}</span>
          <span v-if="selectedLabel" class="font-medium truncate text-sm md:text-base">
            {{ selectedLabel }}
          </span>
          <span v-else class="text-gray-400 truncate text-sm md:text-base">
            {{ placeholder }}
          </span>
        </div>
        <span class="text-gray-400 transform transition-transform duration-300 text-sm" :class="{ 'rotate-180': showDropdown }">
          🔽
        </span>
      </div>

      <!-- Dropdown list -->
      <transition name="fade-slide">
        <div
          v-if="showDropdown"
          ref="dropdownRef"
          class="absolute z-50 w-full mt-1 md:mt-2 bg-white rounded-xl md:rounded-2xl shadow-lg border border-pink-100 overflow-hidden"
          @click.stop
        >
          <!-- Search input -->
          <div class="p-2 border-b border-pink-100 bg-pink-50/50">
            <input
              v-model="search"
              ref="searchRef"
              type="text"
              placeholder="Ketik untuk mencari..."
              class="w-full p-2 rounded-lg md:rounded-xl border border-pink-200 focus:ring-2 focus:ring-pink-300 outline-none text-gray-700 text-sm md:text-base"
              @click.stop
            />
          </div>

          <!-- Options -->
          <div class="max-h-48 md:max-h-56 overflow-y-auto">
            <div
              v-for="option in filteredOptions"
              :key="option.value"
              @click.stop="selectOption(option)"
              class="p-2 md:p-3 hover:bg-pink-50 transition-all duration-150 flex items-center justify-between cursor-pointer"
              :class="{ 'bg-pink-100': option.value === modelValue }"
            >
              <span class="text-gray-700 truncate text-sm md:text-base">{{ option.label }}</span>
              <span v-if="option.value === modelValue" class="text-pink-400 text-sm">💞</span>
            </div>

            <div v-if="filteredOptions.length === 0" class="p-2 md:p-3 text-gray-400 text-center text-sm md:text-base">
              Tidak ditemukan 💔
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Error & help text -->
    <p v-if="error" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">
      ⚠️ {{ error }}
    </p>
    <p v-if="help" class="mt-1 md:mt-2 text-xs md:text-sm text-gray-500 flex items-center gap-1 md:gap-2">
      💡 {{ help }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: [String, Number, Boolean],
  label: String,
  placeholder: { type: String, default: 'Pilih opsi' },
  error: String,
  help: String,
  icon: { type: String, default: '💖' },
  iconClass: { type: String, default: 'text-pink-400' },
  id: String,
  required: Boolean,
  disabled: Boolean,
  options: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const showDropdown = ref(false)
const search = ref('')
const dropdownRef = ref(null)
const rootRef = ref(null)
const searchRef = ref(null)

// Filter options berdasarkan search
const filteredOptions = computed(() => {
  if (!search.value) return props.options
  return props.options.filter(opt =>
    String(opt.label || opt.value).toLowerCase().includes(search.value.toLowerCase())
  )
})

// Label yang dipilih sekarang
const selectedLabel = computed(() => {
  const found = props.options.find(opt => opt.value === props.modelValue)
  return found ? found.label : ''
})

const toggleDropdown = async () => {
  if (props.disabled) return
  showDropdown.value = !showDropdown.value
  if (showDropdown.value) {
    // reset search and focus input
    search.value = ''
    await nextTick()
    searchRef.value?.focus()
    attachClickOutside()
  } else {
    detachClickOutside()
  }
}

const selectOption = (option) => {
  emit('update:modelValue', option.value)
  showDropdown.value = false
  detachClickOutside()
}

// --- click outside handler (manual) ---
let onDocClick = null

function attachClickOutside() {
  // guard: jika sudah ada listener jangan pasang lagi
  if (onDocClick) return

  onDocClick = (e) => {
    const root = rootRef.value
    if (!root) return
    // Jika klik terjadi di luar rootRef, tutup dropdown
    if (!root.contains(e.target)) {
      showDropdown.value = false
      detachClickOutside()
    }
  }
  document.addEventListener('click', onDocClick)
}

function detachClickOutside() {
  if (!onDocClick) return
  document.removeEventListener('click', onDocClick)
  onDocClick = null
}

onBeforeUnmount(() => {
  detachClickOutside()
})

// jika props.options berubah, pastikan selectedLabel sinkron
watch(() => props.modelValue, () => {
  // nothing special, computed will update selectedLabel
})

// expose focus method if needed
defineExpose({
  focus: () => {
    if (!showDropdown.value) {
      toggleDropdown()
    } else {
      searchRef.value?.focus()
    }
  }
})
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.2s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(-6px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* scrollbar yang lembut (opsional) */
::-webkit-scrollbar {
  height: 4px;
  width: 4px;
}
::-webkit-scrollbar-thumb {
  background: #f9c6d2;
  border-radius: 4px;
}

/* Utility classes untuk mobile */
@media (max-width: 640px) {
  .overflow-x-auto {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  
  .overflow-x-auto::-webkit-scrollbar {
    display: none;
  }
}
</style>