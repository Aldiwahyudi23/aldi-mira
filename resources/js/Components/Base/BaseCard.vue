<template>
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl md:rounded-3xl shadow-xl border border-white/50 overflow-hidden transition-all duration-300 hover:shadow-2xl hover:scale-105" :class="customClass">
        <!-- Header dengan gradient -->
        <div v-if="$slots.header || title" class="bg-gradient-to-r from-pink-400/10 to-sky-400/10 py-3 md:py-4 px-3 md:px-4 border-b border-pink-100">
            <slot name="header">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg md:text-xl font-bold bg-gradient-to-r from-pink-600 to-sky-600 bg-clip-text text-transparent truncate">
                            {{ title }}
                        </h3>
                        <p v-if="description" class="text-gray-600 mt-1 flex items-center gap-1 md:gap-2 text-xs md:text-sm">
                            <span class="text-pink-400 text-sm md:text-base">💫</span>
                            {{ description }}
                        </p>
                    </div>
                    <div v-if="$slots['header-action']" class="flex-shrink-0 ml-2 md:ml-3">
                        <slot name="header-action"></slot>
                    </div>
                </div>
            </slot>
        </div>

        <!-- Content -->
        <div class="p-3 md:p-4 bg-gradient-to-br from-white via-pink-50/30 to-sky-50/30">
            <slot></slot>
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer" class="bg-gradient-to-r from-pink-50/50 to-sky-50/50 px-3 md:px-4 py-3 md:py-4 border-t border-pink-100 rounded-b-2xl md:rounded-b-3xl">
            <slot name="footer"></slot>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    customClass: {
        type: String,
        default: ''
    }
});
</script>