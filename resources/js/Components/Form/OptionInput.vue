<template>
    <div class="mb-6">
        <label v-if="label" class="block text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <span v-if="icon" :class="iconClass">{{ icon }}</span>
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div class="space-y-3">
            <div
                v-for="option in options"
                :key="option.value"
                class="flex items-center p-2 border border-gray-200 rounded-2xl hover:bg-gray-50 transition-all duration-200 cursor-pointer"
                :class="{
                    'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-300': isSelected(option.value),
                    'border-red-300': error
                }"
                @click="toggleOption(option.value)"
            >
                <input
                    :id="`${id}-${option.value}`"
                    :ref="inputRef"
                    type="checkbox"
                    :checked="isSelected(option.value)"
                    :disabled="disabled"
                    class="w-4 h-4 text-blue-500 bg-gray-100 border-gray-300 rounded focus:ring-blue-400 focus:ring-2"
                    :class="{
                        'opacity-50 cursor-not-allowed': disabled
                    }"
                    @change="toggleOption(option.value)"
                >
                <label
                    :for="`${id}-${option.value}`"
                    class="ml-3 text-sm font-medium text-gray-700 cursor-pointer flex-1 flex items-center gap-2"
                >
                    <span v-if="option.icon" class="text-lg">{{ option.icon }}</span>
                    {{ option.label }}
                    <span v-if="option.description" class="text-xs text-gray-500 ml-2">{{ option.description }}</span>
                </label>
                
                <div v-if="option.badge" class="px-2 py-1 text-xs rounded-full" :class="option.badgeClass">
                    {{ option.badge }}
                </div>
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
        type: [Array, String, Number, Boolean],
        default: () => []
    },
    label: {
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
    options: {
        type: Array,
        required: true
    },
    multiple: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue']);

const inputRef = ref(null);

const selectedValues = computed({
    get: () => {
        if (Array.isArray(props.modelValue)) {
            return props.modelValue;
        }
        return props.modelValue ? [props.modelValue] : [];
    },
    set: (values) => {
        if (props.multiple) {
            emit('update:modelValue', values);
        } else {
            emit('update:modelValue', values[0] || '');
        }
    }
});

const isSelected = (value) => {
    return selectedValues.value.includes(value);
};

const toggleOption = (value) => {
    if (props.disabled) return;

    let newValues;
    if (props.multiple) {
        if (isSelected(value)) {
            newValues = selectedValues.value.filter(v => v !== value);
        } else {
            newValues = [...selectedValues.value, value];
        }
    } else {
        newValues = isSelected(value) ? [] : [value];
    }

    selectedValues.value = newValues;
};

defineExpose({
    focus: () => inputRef.value?.[0]?.focus()
});
</script>