<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center font-semibold transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl active:scale-95"
        :class="[sizeClasses, variantClasses, customClass, roundedClasses]"
        v-bind="$attrs"
    >
        <!-- Loading State -->
        <span v-if="loading" class="animate-spin" :class="iconSpacing">
            <span class="text-base">⏳</span>
        </span>
        
        <!-- Icon (left) - Selalu tampil -->
        <span v-else-if="$slots.icon" :class="iconSpacing">
            <span class="text-base">
                <slot name="icon"></slot>
            </span>
        </span>
        
        <!-- Button Content - Sembunyi di layar sempit -->
        <span class="whitespace-nowrap" :class="textClasses">
            <slot></slot>
        </span>
        
        <!-- Right Icon - Selalu tampil -->
        <span v-if="$slots['right-icon']" :class="rightIconSpacing">
            <span class="text-base">
                <slot name="right-icon"></slot>
            </span>
        </span>

        <!-- Fallback untuk button tanpa text (hanya icon) -->
        <span v-if="!$slots.default && ($slots.icon || $slots['right-icon'])" class="sr-only">
            <slot name="icon"></slot>
            <slot name="right-icon"></slot>
        </span>
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'button'
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'success', 'warning', 'ghost'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
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
    },
    hideTextOnMobile: {
        type: Boolean,
        default: true // Fitur baru: sembunyikan text di mobile
    }
});

// ===============================================================
// PERBAIKAN: Size classes yang lebih responsive untuk mobile
const sizeClasses = computed(() => {
    const classes = {
        sm: 'px-2 py-1.5 min-h-8 text-xs md:px-3 md:py-2 md:min-h-9 md:text-sm',
        md: 'px-3 py-2 min-h-10 text-sm md:px-4 md:py-2.5 md:min-h-11 md:text-base',
        lg: 'px-4 py-2.5 min-h-12 text-base md:px-5 md:py-3 md:min-h-13 md:text-lg',
        xl: 'px-5 py-3 min-h-14 text-lg md:px-6 md:py-4 md:min-h-15 md:text-xl'
    };
    return classes[props.size] || classes.md;
});

// Text classes dengan fitur hide on mobile
const textClasses = computed(() => {
    const sizes = {
        sm: 'text-xs md:text-sm',
        md: 'text-sm md:text-base', 
        lg: 'text-base md:text-lg',
        xl: 'text-lg md:text-xl'
    };
    
    const baseSize = sizes[props.size] || sizes.md;
    
    // Jika hideTextOnMobile true, sembunyikan text di mobile
    if (props.hideTextOnMobile) {
        return `${baseSize} hidden sm:inline`;
    }
    
    return baseSize;
});

// Spacing untuk icon kiri - sesuaikan dengan visibility text
const iconSpacing = computed(() => {
    const spacing = {
        sm: props.hideTextOnMobile ? 'mr-0 sm:mr-1.5' : 'mr-1.5',
        md: props.hideTextOnMobile ? 'mr-0 sm:mr-2' : 'mr-2',
        lg: props.hideTextOnMobile ? 'mr-0 sm:mr-2.5' : 'mr-2.5', 
        xl: props.hideTextOnMobile ? 'mr-0 sm:mr-3' : 'mr-3'
    };
    return spacing[props.size] || spacing.md;
});

// Spacing untuk icon kanan - sesuaikan dengan visibility text
const rightIconSpacing = computed(() => {
    const spacing = {
        sm: props.hideTextOnMobile ? 'ml-0 sm:ml-1.5' : 'ml-1.5',
        md: props.hideTextOnMobile ? 'ml-0 sm:ml-2' : 'ml-2',
        lg: props.hideTextOnMobile ? 'ml-0 sm:ml-2.5' : 'ml-2.5',
        xl: props.hideTextOnMobile ? 'ml-0 sm:ml-3' : 'ml-3'
    };
    return spacing[props.size] || spacing.md;
});

// Padding khusus untuk button yang hanya icon di mobile
const paddingClasses = computed(() => {
    if (!props.hideTextOnMobile) return '';
    
    const padding = {
        sm: 'px-2 sm:px-3',
        md: 'px-3 sm:px-4', 
        lg: 'px-4 sm:px-5',
        xl: 'px-5 sm:px-6'
    };
    return padding[props.size] || padding.md;
});

// Border radius yang responsive
const roundedClasses = computed(() => {
    return 'rounded-xl md:rounded-2xl';
});

// ===============================================================
// Variant classes TETAP SAMA
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

<style scoped>
/* =============================================================== */
/* PERBAIKAN: Optimasi untuk mobile */
@media (max-width: 768px) {
    /* Pastikan touch target cukup besar untuk mobile */
    button {
        min-height: 44px !important;
    }
    
    /* Size khusus untuk mobile */
    .min-h-8 { min-height: 36px; }
    .min-h-10 { min-height: 44px; }
    .min-h-12 { min-height: 48px; }
    .min-h-14 { min-height: 52px; }
}

@media (min-width: 769px) {
    /* Size untuk desktop */
    .min-h-9 { min-height: 40px; }
    .min-h-11 { min-height: 44px; }
    .min-h-13 { min-height: 52px; }
    .min-h-15 { min-height: 56px; }
}

/* Smooth transitions */
button {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Loading animation */
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Shadow yang lebih subtle di mobile */
@media (max-width: 768px) {
    .shadow-lg {
        box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.06);
    }
    
    .hover\:shadow-xl:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
}

/* Accessibility untuk screen readers */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
/* =============================================================== */
</style>