<template>
    <div class="mb-6">
        <label v-if="label" :for="id" class="block text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <span v-if="icon" :class="iconClass">{{ icon }}</span>
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div
            class="border-2 border-dashed border-gray-300 rounded-2xl p-4 text-center transition-all duration-300 hover:border-blue-400 hover:bg-blue-50/50"
            :class="{
                'border-red-300 bg-red-50/50': error,
                'border-green-300 bg-green-50/50': hasFiles,
                'opacity-50 cursor-not-allowed': disabled
            }"
            @drop.prevent="handleDrop"
            @dragover.prevent="dragover = true"
            @dragleave.prevent="dragover = false"
        >
            <input
                :id="id"
                :ref="fileInputRef"
                type="file"
                :multiple="multiple"
                :accept="accept"
                :disabled="disabled"
                class="hidden"
                @change="handleFileSelect"
            />
            
            <div class="space-y-3">
                <div class="text-4xl mb-2">
                    {{ hasFiles ? '📁' : (dragover ? '📂' : '📄') }}
                </div>
                
                <div>
                    <p class="text-sm text-gray-600 mb-2">
                        <span v-if="!hasFiles">
                            {{ placeholder }}
                        </span>
                        <span v-else class="text-green-600 font-medium">
                            ✅ {{ selectedFiles.length }} file terpilih
                        </span>
                    </p>
                    
                    <p class="text-xs text-gray-500 mb-4">
                        {{ helpText }}
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button
                        type="button"
                        @click="fileInputRef?.click()"
                        class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl hover:from-blue-600 hover:to-purple-600 transition-all duration-300 font-medium"
                        :class="{
                            'opacity-50 cursor-not-allowed': disabled
                        }"
                        :disabled="disabled"
                    >
                        <span class="flex items-center gap-2">
                            <span>📎</span>
                            Pilih File
                        </span>
                    </button>
                    
                    <button
                        v-if="hasFiles && clearable"
                        type="button"
                        @click="clearFiles"
                        class="px-4 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl hover:from-gray-600 hover:to-gray-700 transition-all duration-300 font-medium"
                    >
                        <span class="flex items-center gap-2">
                            <span>🗑️</span>
                            Hapus
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Selected files preview -->
        <div v-if="hasFiles && showPreview" class="mt-4 space-y-2">
            <div
                v-for="(file, index) in selectedFiles"
                :key="index"
                class="flex items-center justify-between p-2 bg-white border border-gray-200 rounded-xl"
            >
                <div class="flex items-center gap-3">
                    <span class="text-2xl">
                        {{ getFileIcon(file) }}
                    </span>
                    <div>
                        <p class="text-sm font-medium text-gray-700">{{ file.name }}</p>
                        <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                    </div>
                </div>
                <button
                    v-if="removable"
                    @click="removeFile(index)"
                    class="text-red-500 hover:text-red-700 transition-colors"
                    type="button"
                >
                    ❌
                </button>
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
        type: [File, Array],
        default: null
    },
    label: {
        type: String,
        default: 'Pilih File'
    },
    placeholder: {
        type: String,
        default: 'Drag & drop file di sini atau klik untuk memilih'
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
        default: '📎'
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
    multiple: {
        type: Boolean,
        default: false
    },
    accept: {
        type: String,
        default: '*/*'
    },
    maxSize: {
        type: Number,
        default: 10 * 1024 * 1024 // 10MB
    },
    showPreview: {
        type: Boolean,
        default: true
    },
    clearable: {
        type: Boolean,
        default: true
    },
    removable: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue']);

const fileInputRef = ref(null);
const dragover = ref(false);
const selectedFiles = ref([]);

const hasFiles = computed(() => selectedFiles.value.length > 0);

const helpText = computed(() => {
    const maxSizeMB = props.maxSize / (1024 * 1024);
    return `Format: ${props.accept === '*/*' ? 'Semua file' : props.accept} • Maks: ${maxSizeMB}MB`;
});

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    processFiles(files);
};

const handleDrop = (event) => {
    dragover.value = false;
    const files = Array.from(event.dataTransfer.files);
    processFiles(files);
};

const processFiles = (files) => {
    const validFiles = files.filter(file => {
        if (file.size > props.maxSize) {
            console.warn(`File ${file.name} melebihi ukuran maksimum`);
            return false;
        }
        return true;
    });

    if (props.multiple) {
        selectedFiles.value = [...selectedFiles.value, ...validFiles];
    } else {
        selectedFiles.value = validFiles.slice(0, 1);
    }

    emit('update:modelValue', props.multiple ? selectedFiles.value : selectedFiles.value[0] || null);
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    emit('update:modelValue', props.multiple ? selectedFiles.value : selectedFiles.value[0] || null);
};

const clearFiles = () => {
    selectedFiles.value = [];
    emit('update:modelValue', props.multiple ? [] : null);
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const getFileIcon = (file) => {
    const type = file.type;
    if (type.startsWith('image/')) return '🖼️';
    if (type.startsWith('video/')) return '🎬';
    if (type.startsWith('audio/')) return '🎵';
    if (type.includes('pdf')) return '📕';
    if (type.includes('word')) return '📄';
    if (type.includes('excel') || type.includes('sheet')) return '📊';
    if (type.includes('zip') || type.includes('rar')) return '📦';
    return '📄';
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

defineExpose({
    focus: () => fileInputRef.value?.click(),
    clear: clearFiles
});
</script>