<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="text-lg">🔍</span>
                <h3 class="font-semibold text-gray-800">Filter Data</h3>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <div v-for="(config, key) in options" :key="key" class="min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ config.label }}
                    </label>
                    <select
                        :value="filters[key]"
                        @change="updateFilter(key, $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-sm"
                    >
                        <option 
                            v-for="option in config.options" 
                            :key="option.value" 
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <BaseButton
                        @click="clearAll"
                        variant="secondary"
                        size="sm"
                        class="h-[42px]"
                        :disabled="!hasActiveFilters"
                    >
                        <template #icon>🔄</template>
                        Reset
                    </BaseButton>
                </div>
            </div>
        </div>
        
        <!-- Active filters indicator -->
        <div v-if="hasActiveFilters" class="mt-3 pt-3 border-t border-gray-100">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm text-gray-600">Filter aktif:</span>
                <span 
                    v-for="(value, key) in activeFilters" 
                    :key="key"
                    class="inline-flex items-center gap-1 px-2 py-1 bg-pink-100 text-pink-800 rounded-full text-xs font-medium"
                >
                    {{ getFilterLabel(key, value) }}
                    <button 
                        @click="clearFilter(key)"
                        class="hover:text-pink-900 transition-colors"
                    >
                        ×
                    </button>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import BaseButton from './BaseButton.vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true
    },
    options: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:filters']);

const hasActiveFilters = computed(() => {
    return Object.values(props.filters).some(value => value !== '');
});

const activeFilters = computed(() => {
    return Object.fromEntries(
        Object.entries(props.filters).filter(([_, value]) => value !== '')
    );
});

const getFilterLabel = (key, value) => {
    const option = props.options[key]?.options.find(opt => opt.value === value);
    return option?.label || value;
};

const updateFilter = (key, value) => {
    emit('update:filters', {
        ...props.filters,
        [key]: value
    });
};

const clearFilter = (key) => {
    updateFilter(key, '');
};

const clearAll = () => {
    const clearedFilters = {};
    Object.keys(props.filters).forEach(key => {
        clearedFilters[key] = '';
    });
    emit('update:filters', clearedFilters);
};
</script>