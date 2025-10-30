<template>
    <div class="mb-6">
        <label v-if="label" :for="id" class="block text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <span v-if="icon" :class="iconClass">{{ icon }}</span>
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div class="relative">
            <textarea
                :id="id"
                :ref="textareaRef"
                v-model="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :rows="rows"
                :maxlength="maxLength"
                class="w-full border border-gray-200 rounded-2xl p-4 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 transition-all duration-300 bg-white/50 backdrop-blur-sm resize-vertical"
                :class="{
                    'pl-12': icon,
                    'border-red-300': error,
                    'opacity-50 cursor-not-allowed': disabled
                }"
                v-bind="$attrs"
                @input="handleInput"
            />
            
            <div v-if="icon" class="absolute left-4 top-4 text-gray-400">
                {{ icon }}
            </div>

            <div v-if="showCounter" class="absolute right-4 bottom-4 text-xs text-gray-400 bg-white/80 px-2 py-1 rounded-full">
                {{ currentLength }} / {{ maxLength }}
            </div>
        </div>
        
        <p v-if="error" class="mt-2 text-sm text-red-500 flex items-center gap-2">
            <span>⚠️</span>
            {{ error }}
        </p>
        
        <p v-if="help" class="mt-2 text-sm text-gray-500 flex items-center gap-2">
            <span>💡</span>
            {{ help }}
        </p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
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
    rows: {
        type: Number,
        default: 4
    },
    maxLength: {
        type: Number,
        default: null
    },
    showCounter: {
        type: Boolean,
        default: false
    },
    autoGrow: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const modelValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const textareaRef = ref(null);
const currentLength = ref(0);

const handleInput = (event) => {
    currentLength.value = event.target.value.length;
    
    if (props.autoGrow) {
        // Auto-grow functionality
        event.target.style.height = 'auto';
        event.target.style.height = event.target.scrollHeight + 'px';
    }
};

defineExpose({
    focus: () => textareaRef.value?.focus()
});
</script>