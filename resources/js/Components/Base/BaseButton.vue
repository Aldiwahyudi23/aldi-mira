<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center font-semibold rounded-2xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl active:scale-95"
        :class="[sizeClasses, variantClasses, customClass]"
        v-bind="$attrs"
    >
        <!-- Loading State -->
        <span v-if="loading" class="animate-spin mr-2">⏳</span>
        
        <!-- Icon (left) -->
        <span v-else-if="$slots.icon" class="mr-2">
            <slot name="icon"></slot>
        </span>
        
        <!-- Button Content -->
        <span class="whitespace-nowrap">
            <slot></slot>
        </span>
        
        <!-- Right Icon -->
        <span v-if="$slots['right-icon']" class="ml-2">
            <slot name="right-icon"></slot>
        </span>
    </button>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'button'
    },
    variant: {
        type: String,
        default: 'primary', // primary, secondary, danger, success, warning, ghost
        validator: (value) => ['primary', 'secondary', 'danger', 'success', 'warning', 'ghost'].includes(value)
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg, xl
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
    },
    disabled: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    customClass: {
        type: String,
        default: ''
    }
});

const sizeClasses = computed(() => {
    const classes = {
        sm: 'px-2 py-2 text-sm',
        md: 'px-3 py-2 text-base',
        lg: 'px-4 py-2 text-lg',
        xl: 'px-6 py-4 text-xl'
    };
    return classes[props.size];
});

const variantClasses = computed(() => {
    const baseClasses = {
        primary: 'bg-gradient-to-r from-pink-400 to-sky-400 text-white hover:from-pink-500 hover:to-sky-500 focus:ring-pink-300 border border-transparent',
        secondary: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 hover:border-pink-300 focus:ring-pink-300',
        danger: 'bg-gradient-to-r from-red-400 to-pink-500 text-white hover:from-red-500 hover:to-pink-600 focus:ring-red-300 border border-transparent',
        success: 'bg-gradient-to-r from-green-400 to-emerald-500 text-white hover:from-green-500 hover:to-emerald-600 focus:ring-green-300 border border-transparent',
        warning: 'bg-gradient-to-r from-amber-400 to-orange-500 text-white hover:from-amber-500 hover:to-orange-600 focus:ring-amber-300 border border-transparent',
        ghost: 'bg-transparent text-gray-600 hover:text-pink-600 hover:bg-pink-50 focus:ring-pink-300 border border-transparent'
    };
    return baseClasses[props.variant];
});
</script>