<template>
  <div class="mb-5">
    <!-- Label -->
    <label
      v-if="label"
      :for="id"
      class="block text-sm font-semibold text-gray-700 mb-2"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Input -->
    <div class="relative">
      <input
        :id="id"
        :ref="inputRef"
        v-model="displayValue"
        type="text"
        inputmode="numeric"
        :placeholder="placeholder"
        :disabled="disabled"
        class="w-full border border-gray-200 rounded-xl p-3 text-right focus:ring-2 focus:ring-pink-300 focus:border-pink-300 transition-all duration-300 bg-white/70 backdrop-blur-sm"
        :class="{
          'border-red-300': error,
          'opacity-50 cursor-not-allowed': disabled
        }"
        @blur="onBlur"
        @focus="onFocus"
      />
    </div>

    <!-- Error -->
    <p v-if="error" class="mt-2 text-sm text-red-500 flex items-center gap-1">
      ⚠️ {{ error }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: '0'
  },
  error: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  decimals: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['update:modelValue']);

const inputRef = ref(null);
const isFocused = ref(false);

const displayValue = computed({
  get() {
    if (isFocused.value) {
      return props.modelValue?.toString() || '';
    }
    return formatForDisplay(props.modelValue);
  },
  set(value) {
    const numericValue = parseFloat(value.replace(/[^\d.-]/g, '')) || 0;
    emit('update:modelValue', numericValue);
  }
});

function formatForDisplay(value) {
  if (value === null || value === undefined || value === '') return '';
  const number = Number(value);
  if (isNaN(number)) return '';
  return number.toLocaleString('id-ID', {
    minimumFractionDigits: props.decimals,
    maximumFractionDigits: props.decimals
  });
}

function onBlur() {
  isFocused.value = false;
}

function onFocus() {
  isFocused.value = true;
}

defineExpose({
  focus: () => inputRef.value?.focus()
});
</script>
