<template>
  <div class="mb-4 md:mb-6 relative">
    <label v-if="label" :for="id" class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-2">
      <span v-if="icon" :class="iconClass">{{ icon }}</span>
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="relative">
      <input
        :id="id"
        v-model="internalValue"
        type="date"
        :placeholder="placeholder"
        :disabled="disabled"
        class="w-full border border-gray-200 rounded-xl md:rounded-2xl p-3 md:p-4 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 transition-all duration-300 bg-white/50 backdrop-blur-sm font-medium text-sm md:text-base"
        :class="{
          'pl-10 md:pl-12': icon,
          'border-red-300': error,
          'opacity-50 cursor-not-allowed': disabled,
          'bg-green-50 border-green-200': isToday,
          'bg-blue-50 border-blue-200': isSelectedDate
        }"
        :min="minDate"
        :max="maxDate"
      />

      <div v-if="icon" class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-base md:text-lg">
        {{ icon }}
      </div>
    </div>

    <p v-if="error" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">⚠️ {{ error }}</p>
    <p v-if="help" class="mt-1 md:mt-2 text-xs md:text-sm text-gray-500 flex items-center gap-1 md:gap-2">💡 {{ help }}</p>

    <div v-if="internalValue && showDateInfo" class="mt-2 md:mt-3 p-2 md:p-3 bg-blue-50 rounded-lg md:rounded-xl border border-blue-200">
      <p class="text-xs md:text-sm text-blue-700 flex items-center gap-1 md:gap-2">
        <span class="text-base md:text-lg">📅</span>
        <span>
          <strong>Tanggal terpilih:</strong> {{ formattedSelectedDate }}
          <span v-if="isToday" class="ml-1 md:ml-2 px-1.5 md:px-2 py-0.5 md:py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Hari Ini</span>
        </span>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Date], default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'Pilih tanggal...' },
  error: { type: String, default: '' },
  help: { type: String, default: '' },
  icon: { type: String, default: '📅' },
  iconClass: { type: String, default: 'text-blue-400' },
  id: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  showDateInfo: { type: Boolean, default: true },
  minDate: { type: String, default: '' },
  maxDate: { type: String, default: '' }
});

const emit = defineEmits(['update:modelValue']);

const internalValue = ref('');

// Convert modelValue to YYYY-MM-DD for input[type="date"]
watch(() => props.modelValue, (newVal) => {
  if (!newVal) {
    internalValue.value = '';
    return;
  }
  
  if (newVal instanceof Date) {
    internalValue.value = newVal.toISOString().split('T')[0];
  } else if (typeof newVal === 'string') {
    // Convert from dd/mm/yyyy to yyyy-mm-dd if needed
    if (newVal.includes('/')) {
      const [day, month, year] = newVal.split('/');
      internalValue.value = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    } else {
      internalValue.value = newVal;
    }
  }
}, { immediate: true });

// Emit value when internal value changes
watch(internalValue, (newVal) => {
  emit('update:modelValue', newVal);
});

const isToday = computed(() => {
  if (!internalValue.value) return false;
  const today = new Date().toISOString().split('T')[0];
  return internalValue.value === today;
});

const isSelectedDate = computed(() => !!internalValue.value);

const formattedSelectedDate = computed(() => {
  if (!internalValue.value) return '';
  const date = new Date(internalValue.value);
  return date.toLocaleDateString('id-ID', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
});
</script>