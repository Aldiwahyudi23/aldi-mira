<template>
  <div class="flex items-start gap-2 md:gap-3 select-none">
    <!-- Toggle Button -->
    <button
      type="button"
      class="relative inline-flex items-center justify-center w-10 h-6 md:w-12 md:h-7 rounded-full transition-all duration-300 focus:outline-none"
      :class="[
        modelValue ? 'bg-gradient-to-r from-pink-400 to-rose-500 shadow-md' : 'bg-gray-200',
        disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:scale-[1.05]'
      ]"
      @click="toggle"
      :disabled="disabled"
    >
      <span
        class="absolute left-0.5 md:left-1 top-0.5 md:top-1 w-4 h-4 md:w-5 md:h-5 bg-white rounded-full shadow-md transform transition-all duration-300"
        :class="{ 'translate-x-4 md:translate-x-5': modelValue }"
      ></span>
    </button>

    <!-- Label + Description -->
    <div class="flex flex-col flex-1">
      <label
        class="text-xs md:text-sm font-medium"
        :class="[
          disabled ? 'text-gray-400' : modelValue ? 'text-pink-600' : 'text-gray-800'
        ]"
        @click="toggle"
      >
        {{ label }}
      </label>
      <p v-if="description" class="text-xs text-gray-500 mt-0.5">
        {{ description }}
      </p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emits = defineEmits(['update:modelValue'])

const toggle = () => {
  if (!props.disabled) {
    emits('update:modelValue', !props.modelValue)
  }
}
</script>

<style scoped>
button {
  outline: none;
  border: none;
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