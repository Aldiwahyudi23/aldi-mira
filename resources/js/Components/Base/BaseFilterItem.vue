<template>
  <div class="bg-white rounded-xl md:rounded-2xl shadow-sm border border-gray-100 p-3 md:p-4 mb-4">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4">
      <div class="flex items-center gap-2">
        <span class="text-base md:text-lg">🔍</span>
        <h3 class="font-semibold text-gray-800 text-sm md:text-base">Filter Data</h3>
      </div>

      <!-- Filters -->
      <div class="flex flex-col md:flex-row gap-2 md:gap-2 w-full md:w-auto">
        <!-- SelectInput replaces the native <select> -->
        <div
          v-for="(config, key) in options"
          :key="key"
          class="w-full md:min-w-[150px] md:flex-1"
        >
          <SelectInput
            v-model="localFilters[key]"
            :label="config.label"
            :placeholder="config.placeholder || 'Pilih ' + config.label"
            :options="config.options || []"
            :icon="config.icon || '💖'"
            :error="config.error"
            @update:modelValue="(val) => updateFilter(key, val)"
          />
        </div>

        <!-- Reset button -->
        <div class="flex items-end mt-2 md:mt-0">
          <BaseButton
            @click="clearAll"
            variant="secondary"
            size="xs"
            class="h-[38px] md:h-[42px] w-full md:w-auto text-xs"
            :disabled="!hasActiveFilters"
          >
            <template #icon>🔄</template>
            Reset
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Active filters indicator -->
    <div v-if="hasActiveFilters" class="mt-2 md:mt-3 pt-2 md:pt-3 border-t border-gray-100">
      <div class="flex items-center gap-1 md:gap-2 flex-wrap">
        <span class="text-xs md:text-sm text-gray-600">Filter aktif:</span>
        <span
          v-for="(value, key) in activeFilters"
          :key="key"
          class="inline-flex items-center gap-1 px-2 py-1 bg-pink-100 text-pink-800 rounded-full text-xs font-medium"
        >
          {{ getFilterLabel(key, value) }}
          <button
            @click="clearFilter(key)"
            class="hover:text-pink-900 transition-colors text-xs"
          >
            ×
          </button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseButton from './BaseButton.vue'
import SelectInput from '../Form/SelectInput.vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
  options: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:filters'])

// 🔹 buat salinan reactive untuk binding dua arah
const localFilters = reactive({ ...props.filters })

// 🔹 sinkronkan perubahan dari parent
watch(
  () => props.filters,
  (newVal) => {
    Object.assign(localFilters, newVal)
  },
  { deep: true }
)

// 🔹 update parent setiap kali localFilters berubah
const updateFilter = (key, value) => {
  localFilters[key] = value
  emit('update:filters', { ...localFilters })
}

// 🔹 computed untuk status aktif filter
const hasActiveFilters = computed(() => {
  return Object.values(localFilters).some((v) => v !== '')
})

const activeFilters = computed(() => {
  return Object.fromEntries(
    Object.entries(localFilters).filter(([_, v]) => v !== '')
  )
})

// 🔹 tampilkan label filter aktif
const getFilterLabel = (key, value) => {
  const option = props.options[key]?.options.find((opt) => opt.value === value)
  return option?.label || value
}

// 🔹 hapus filter tertentu
const clearFilter = (key) => {
  updateFilter(key, '')
}

// 🔹 hapus semua filter
const clearAll = () => {
  const cleared = {}
  Object.keys(localFilters).forEach((key) => (cleared[key] = ''))
  Object.assign(localFilters, cleared)
  emit('update:filters', { ...cleared })
}
</script>
