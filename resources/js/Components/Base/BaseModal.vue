<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="show"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
                aria-labelledby="modal-title"
                aria-modal="true"
                role="dialog"
            >
                <!-- Background overlay dengan backdrop blur -->
                <div 
                    class="absolute inset-0 bg-black/60 backdrop-blur-md transition-all duration-300"
                    @click="!persistent && close()"
                ></div>

                <!-- Modal container -->
                <div class="relative w-full max-w-2xl max-h-[85vh] flex flex-col bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all duration-300 scale-100">
                    <!-- Header - Fixed -->
                    <div class="flex-shrink-0 bg-gradient-to-r from-pink-400/10 to-sky-400/10 py-4 px-6 border-b border-pink-100/50">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-gradient-to-r from-pink-400 to-sky-400 shadow-lg">
                                <span class="text-white text-xl">{{ icon }}</span>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-xl font-bold bg-gradient-to-r from-pink-600 to-sky-600 bg-clip-text text-transparent" id="modal-title">
                                    {{ title }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 flex items-center gap-2">
                                    <span>💫</span>
                                    {{ description }}
                                </p>
                            </div>
                            <button
                                v-if="closable"
                                @click="close"
                                class="flex-shrink-0 p-2 rounded-2xl text-gray-400 hover:text-gray-600 hover:bg-pink-50 transition-all duration-200 transform hover:scale-110"
                            >
                                <span class="text-lg">✕</span>
                            </button>
                        </div>
                    </div>

                    <!-- Content - Scrollable -->
                    <div class="flex-1 overflow-y-auto bg-gradient-to-br from-white via-pink-50/30 to-sky-50/30">
                        <div class="p-6">
                            <slot></slot>
                        </div>
                    </div>

                    <!-- Footer - Fixed -->
                    <div v-if="$slots.footer || showCancel" class="flex-shrink-0 bg-gradient-to-r from-pink-50/50 to-sky-50/50 px-6 py-4 border-t border-pink-100/50">
                        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end">
                            <slot name="footer">
                                <BaseButton
                                    v-if="showCancel"
                                    type="button"
                                    variant="secondary"
                                    @click="close"
                                    class="w-full sm:w-auto"
                                >
                                    <template #icon>❌</template>
                                    {{ cancelText }}
                                </BaseButton>
                                <BaseButton
                                    type="button"
                                    :variant="confirmVariant"
                                    @click="confirm"
                                    :loading="confirmLoading"
                                    class="w-full sm:w-auto"
                                >
                                    <template #icon>{{ confirmIcon }}</template>
                                    {{ confirmText }}
                                </BaseButton>
                            </slot>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import BaseButton from './BaseButton.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    title: {
        type: String,
        default: 'Modal Title'
    },
    description: {
        type: String,
        default: 'Modal description'
    },
    icon: {
        type: String,
        default: '💫'
    },
    confirmText: {
        type: String,
        default: 'Konfirmasi'
    },
    cancelText: {
        type: String,
        default: 'Batal'
    },
    confirmIcon: {
        type: String,
        default: '✅'
    },
    confirmVariant: {
        type: String,
        default: 'primary'
    },
    confirmLoading: {
        type: Boolean,
        default: false
    },
    showCancel: {
        type: Boolean,
        default: true
    },
    closable: {
        type: Boolean,
        default: true
    },
    persistent: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
    }
});

const emit = defineEmits(['update:show', 'confirm', 'close']);

const modalSize = computed(() => {
    const sizes = {
        sm: 'max-w-md',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl'
    };
    return sizes[props.size];
});

// Prevent body scroll when modal is open
watch(() => props.show, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
    }
});

const close = () => {
    if (!props.persistent) {
        emit('update:show', false);
        emit('close');
    }
};

const confirm = () => {
    emit('confirm');
};

// Handle ESC key
const handleKeydown = (event) => {
    if (event.key === 'Escape' && props.show && !props.persistent) {
        close();
    }
};

// Add event listener for ESC key
import { onMounted, onUnmounted } from 'vue';
onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});
onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    // Pastikan overflow direset saat component di-unmount
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
});
</script>

<style scoped>
/* Custom scrollbar untuk content area */
.flex-1::-webkit-scrollbar {
    width: 8px;
}

.flex-1::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    margin: 8px;
}

.flex-1::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f472b6, #60a5fa);
    border-radius: 10px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

.flex-1::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ec4899, #3b82f6);
}
</style>