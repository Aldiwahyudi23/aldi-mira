<template>
    <div class="mb-4 md:mb-6">
        <label v-if="label" :for="id" class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-2">
            <span v-if="icon" :class="iconClass">{{ icon }}</span>
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div class="relative">
            <input
                :id="id"
                :ref="inputRef"
                v-model="modelValue"
                type="text"
                :placeholder="placeholder"
                :disabled="disabled"
                class="w-full border border-gray-200 rounded-xl md:rounded-2xl p-3 md:p-4 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 transition-all duration-300 bg-white/50 backdrop-blur-sm text-sm md:text-base"
                :class="{
                    'pl-10 md:pl-12': icon,
                    'border-red-300': error,
                    'opacity-50 cursor-not-allowed': disabled,
                    'pr-10 md:pr-12': clearable && modelValue
                }"
                v-bind="$attrs"
            />
            
            <div v-if="icon" class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-base md:text-lg">
                {{ icon }}
            </div>

            <button
                v-if="clearable && modelValue"
                @click="clearInput"
                class="absolute right-3 md:right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors text-sm"
                type="button"
            >
                ❌
            </button>
        </div>
        
        <p v-if="error" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">
            <span>⚠️</span>
            {{ error }}
        </p>
        
        <p v-if="help" class="mt-1 md:mt-2 text-xs md:text-sm text-gray-500 flex items-center gap-1 md:gap-2">
            <span>💡</span>
            {{ help }}
        </p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    help: {
        type: String,
        default: ''
    },
    icon: {
        type: String,
        default: ''
    },
    iconClass: {
        type: String,
        default: 'text-blue-400'
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
    clearable: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const modelValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const inputRef = ref(null);

const clearInput = () => {
    modelValue.value = '';
};

defineExpose({
    focus: () => inputRef.value?.focus()
});
</script>