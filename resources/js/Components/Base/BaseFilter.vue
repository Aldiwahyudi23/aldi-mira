<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
        <!-- Filter Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🔍</span>
                <div>
                    <h3 class="font-semibold text-gray-800">Filter Data</h3>
                    <p class="text-sm text-gray-500">Saring data berdasarkan kriteria tertentu</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Toggle Filter Visibility -->
                <BaseButton
                    @click="showFilters = !showFilters"
                    variant="secondary"
                    size="sm"
                >
                    <template #icon>{{ showFilters ? '📕' : '📖' }}</template>
                    {{ showFilters ? 'Sembunyikan' : 'Tampilkan' }} Filter
                </BaseButton>
                
                <!-- Reset Filters -->
                <BaseButton
                    @click="resetFilters"
                    variant="outline"
                    size="sm"
                    :disabled="!hasActiveFilters"
                >
                    <template #icon>🔄</template>
                    Reset
                </BaseButton>
            </div>
        </div>

        <!-- Filter Content -->
        <div v-if="showFilters" class="space-y-6">
            <!-- 🎯 SECTION 1: PERIODE & JENIS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column: Date Range & Presets -->
                <div class="space-y-4">
                    <!-- Date Range -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <span>📅</span>
                                Periode Tanggal
                            </span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <DateInput
                                v-model="filters.start_date"
                                placeholder="Dari Tanggal"
                                :max="filters.end_date || today"
                                @update:modelValue="onFilterChange"
                            />
                            <DateInput
                                v-model="filters.end_date"
                                placeholder="Sampai Tanggal"
                                :min="filters.start_date"
                                :max="today"
                                @update:modelValue="onFilterChange"
                            />
                        </div>
                    </div>

                    <!-- Quick Date Presets -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <span>⚡</span>
                                Periode Cepat
                            </span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <BaseButton
                                v-for="preset in datePresets"
                                :key="preset.value"
                                @click="applyDatePreset(preset)"
                                variant="outline"
                                size="xs"
                                class="justify-center"
                                :class="{
                                    'bg-blue-100 border-blue-300 text-blue-700': activeDatePreset === preset.value
                                }"
                            >
                                {{ preset.label }}
                            </BaseButton>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Type & Amount Range -->
                <div class="space-y-4">
                    <!-- Type Filter -->
                    <div class="space-y-2" v-if="showTypeFilter">
                        <label class="block text-sm font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <span>📊</span>
                                Jenis Transaksi
                            </span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="typeOpt in typeOptions.filter(opt => opt.value !== '')"
                                :key="typeOpt.value"
                                @click="filters.type = filters.type === typeOpt.value ? '' : typeOpt.value"
                                class="p-2 rounded-lg border text-center transition-all font-medium text-xs"
                                :class="filters.type === typeOpt.value 
                                    ? (typeOpt.value === 'income' ? 'bg-green-100 text-green-700 border-green-400' : 
                                       typeOpt.value === 'expense' ? 'bg-red-100 text-red-700 border-red-400' : 
                                       'bg-blue-100 text-blue-700 border-blue-400') + ' scale-105 shadow-sm' 
                                    : 'bg-white border-gray-200 text-gray-500 hover:scale-102 hover:border-gray-300'"
                            >
                                <div class="text-base mb-1">{{ typeOpt.label.split(' ')[0] }}</div>
                                <div class="text-xs">{{ typeOpt.label.split(' ')[1] }}</div>
                            </button>
                        </div>
                    </div>

                    <!-- Amount Range Filter -->
                    <div class="space-y-2" v-if="showAmountFilter">
                        <label class="block text-sm font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <span>💰</span>
                                Rentang Jumlah
                            </span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <AccountInput
                                    v-model="filters.min_amount"
                                    placeholder="Minimum"
                                    account-type="account_number"
                                    @update:modelValue="onFilterChange"
                                />
                                <p class="text-xs text-gray-500 text-center">Min</p>
                            </div>
                            <div class="space-y-1">
                                <AccountInput
                                    v-model="filters.max_amount"
                                    placeholder="Maksimum"
                                    account-type="account_number"
                                    @update:modelValue="onFilterChange"
                                />
                                <p class="text-xs text-gray-500 text-center">Max</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🎯 SECTION 2: ENTITY FILTERS (Akun, Kategori, User) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" v-if="showAccountFilter || showCategoryFilter || showUserFilter">
                <!-- Account Filter -->
                <div class="space-y-2" v-if="showAccountFilter && accounts.length">
                    <label class="block text-sm font-medium text-gray-700">
                        <span class="flex items-center gap-2">
                            <span>🏦</span>
                            Akun
                        </span>
                    </label>
                    <SelectInput
                        v-model="filters.account_id"
                        :options="accountOptions"
                        placeholder="Pilih Akun"
                        @update:modelValue="onFilterChange"
                    />
                </div>

                <!-- Category Filter -->
                <div class="space-y-2" v-if="showCategoryFilter && categories.length">
                    <label class="block text-sm font-medium text-gray-700">
                        <span class="flex items-center gap-2">
                            <span>📁</span>
                            Kategori
                        </span>
                    </label>
                    <SelectInput
                        v-model="filters.category_id"
                        :options="categoryOptions"
                        placeholder="Pilih Kategori"
                        @update:modelValue="onFilterChange"
                    />
                </div>

                <!-- User Filter -->
                <div class="space-y-2" v-if="showUserFilter">
                    <label class="block text-sm font-medium text-gray-700">
                        <span class="flex items-center gap-2">
                            <span>👤</span>
                            Dicatat Oleh
                        </span>
                    </label>
                    <SelectInput
                        v-model="filters.user_id"
                        :options="userOptions"
                        placeholder="Pilih User"
                        @update:modelValue="onFilterChange"
                    />
                </div>
            </div>

            <!-- 🎯 SECTION 3: STATUS FILTER (jika ada) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" v-if="showStatusFilter">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        <span class="flex items-center gap-2">
                            <span>🟢</span>
                            Status
                        </span>
                    </label>
                    <SelectInput
                        v-model="filters.status"
                        :options="statusOptions"
                        placeholder="Pilih Status"
                        @update:modelValue="onFilterChange"
                    />
                </div>
            </div>

            <!-- 🎯 SECTION 4: ACTIVE FILTERS BADGES -->
            <div v-if="hasActiveFilters" class="pt-4 border-t border-gray-200">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-sm font-medium text-gray-700 flex items-center gap-2">
                        <span>🎯</span>
                        Filter Aktif:
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="filter in activeFilterBadges"
                        :key="filter.key"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 text-blue-800 shadow-sm"
                    >
                        <span class="text-xs">{{ filter.label }}</span>
                        <button
                            @click="removeFilter(filter.key)"
                            class="hover:text-blue-600 transition-colors text-xs font-bold"
                            title="Hapus filter"
                        >
                            ×
                        </button>
                    </span>
                </div>
            </div>

            <!-- 🎯 SECTION 5: QUICK ACTIONS -->
            <div class="flex justify-between items-center pt-2">
                <div class="text-sm text-gray-500">
                    <span v-if="hasActiveFilters" class="text-blue-600 font-medium">
                        🔍 {{ activeFilterBadges.length }} filter aktif
                    </span>
                    <span v-else>
                        📝 Tidak ada filter aktif
                    </span>
                </div>
                
                <div class="flex gap-2">
                    <BaseButton
                        @click="resetFilters"
                        variant="outline"
                        size="sm"
                        :disabled="!hasActiveFilters"
                    >
                        <template #icon>🗑️</template>
                        Hapus Semua Filter
                    </BaseButton>
                </div>
            </div>
        </div>

        <!-- 🎯 COLLAPSED FILTER SUMMARY -->
        <div v-else class="text-center py-2">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-full">
                <span class="text-sm text-gray-600">
                    <span v-if="hasActiveFilters" class="font-medium text-blue-600">
                        🔍 {{ activeFilterBadges.length }} filter aktif - 
                        <span class="text-gray-500">{{ filterSummaryShort }}</span>
                    </span>
                    <span v-else class="text-gray-500">
                        Klik "Tampilkan Filter" untuk menyaring data
                    </span>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
import SelectInput from '@/Components/Form/SelectInput.vue'
import DateInput from '@/Components/Form/DateInput.vue'
import AccountInput from '@/Components/Form/AccountInput.vue'

// Props
const props = defineProps({
    // Filter configuration
    showTypeFilter: { type: Boolean, default: true },
    showAccountFilter: { type: Boolean, default: true },
    showCategoryFilter: { type: Boolean, default: true },
    showUserFilter: { type: Boolean, default: true },
    showStatusFilter: { type: Boolean, default: false },
    showAmountFilter: { type: Boolean, default: true },
    
    // Data for dropdowns
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    
    // Initial filters
    initialFilters: { type: Object, default: () => ({}) }
})

// Emits
const emit = defineEmits(['filters-change', 'filters-reset'])

// State
const showFilters = ref(false)
const filters = ref({
    start_date: '',
    end_date: '',
    type: '',
    account_id: '',
    category_id: '',
    user_id: '',
    status: '',
    min_amount: '',
    max_amount: '',
    ...props.initialFilters
})

const activeDatePreset = ref('')

// Computed
const today = computed(() => new Date().toISOString().split('T')[0])

const datePresets = computed(() => [
    { label: 'Hari Ini', value: 'today', getRange: () => ({ start: today.value, end: today.value }) },
    { label: 'Minggu Ini', value: 'this_week', getRange: () => {
        const start = new Date();
        start.setDate(start.getDate() - start.getDay());
        return { start: start.toISOString().split('T')[0], end: today.value };
    }},
    { label: 'Bulan Ini', value: 'this_month', getRange: () => {
        const start = new Date();
        start.setDate(1);
        return { start: start.toISOString().split('T')[0], end: today.value };
    }},
    { label: 'Bulan Lalu', value: 'last_month', getRange: () => {
        const start = new Date();
        start.setMonth(start.getMonth() - 1);
        start.setDate(1);
        const end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
        return { 
            start: start.toISOString().split('T')[0], 
            end: end.toISOString().split('T')[0] 
        };
    }},
    { label: '3 Bulan', value: 'last_3_months', getRange: () => {
        const end = new Date();
        const start = new Date();
        start.setMonth(start.getMonth() - 3);
        return { 
            start: start.toISOString().split('T')[0], 
            end: end.toISOString().split('T')[0] 
        };
    }},
])

const typeOptions = computed(() => [
    { value: '', label: 'Semua Jenis' },
    { value: 'income', label: '💰 Pemasukan' },
    { value: 'expense', label: '💸 Pengeluaran' },
    { value: 'savings', label: '🏦 Tabungan' }
])

const accountOptions = computed(() => [
    { value: '', label: 'Semua Akun' },
    ...props.accounts.map(acc => ({
        value: acc.id,
        label: `${acc.name} ${acc.type === 'joint' ? '👥' : '👤'}`
    }))
])

const categoryOptions = computed(() => [
    { value: '', label: 'Semua Kategori' },
    ...props.categories.map(cat => ({
        value: cat.id,
        label: `${cat.name} ${cat.type === 'joint' ? '👥' : '👤'}`
    }))
])

const userOptions = computed(() => [
    { value: '', label: 'Semua User' },
    ...props.users.map(user => ({
        value: user.id,
        label: user.name
    }))
])

const statusOptions = computed(() => [
    { value: '', label: 'Semua Status' },
    { value: 'active', label: '🟢 Aktif' },
    { value: 'inactive', label: '🔴 Nonaktif' }
])

const hasActiveFilters = computed(() => {
    return Object.keys(filters.value).some(key => {
        const value = filters.value[key]
        return value !== '' && value !== null && value !== undefined
    })
})

const activeFilterBadges = computed(() => {
    const badges = []
    
    if (filters.value.start_date && filters.value.end_date) {
        badges.push({
            key: 'date_range',
            label: `📅 ${filters.value.start_date} s/d ${filters.value.end_date}`
        })
    }
    
    if (filters.value.type) {
        const typeLabel = typeOptions.value.find(opt => opt.value === filters.value.type)?.label || filters.value.type
        badges.push({ key: 'type', label: typeLabel.split(' ')[1] })
    }
    
    if (filters.value.account_id) {
        const account = props.accounts.find(acc => acc.id == filters.value.account_id)
        badges.push({ key: 'account_id', label: `🏦 ${account?.name || 'Akun'}` })
    }
    
    if (filters.value.category_id) {
        const category = props.categories.find(cat => cat.id == filters.value.category_id)
        badges.push({ key: 'category_id', label: `📁 ${category?.name || 'Kategori'}` })
    }
    
    if (filters.value.user_id) {
        const user = props.users.find(u => u.id == filters.value.user_id)
        badges.push({ key: 'user_id', label: `👤 ${user?.name || 'User'}` })
    }
    
    if (filters.value.min_amount) {
        badges.push({ key: 'min_amount', label: `💰 Min: Rp ${parseInt(filters.value.min_amount).toLocaleString('id-ID')}` })
    }
    
    if (filters.value.max_amount) {
        badges.push({ key: 'max_amount', label: `💰 Max: Rp ${parseInt(filters.value.max_amount).toLocaleString('id-ID')}` })
    }
    
    return badges
})

const filterSummaryShort = computed(() => {
    if (!hasActiveFilters.value) return ''
    
    const parts = []
    if (filters.value.start_date && filters.value.end_date) {
        parts.push('Periode')
    }
    if (filters.value.type) parts.push('Jenis')
    if (filters.value.account_id) parts.push('Akun')
    if (filters.value.category_id) parts.push('Kategori')
    if (filters.value.min_amount || filters.value.max_amount) parts.push('Jumlah')
    
    return parts.join(', ')
})

// Methods
const applyDatePreset = (preset) => {
    const range = preset.getRange()
    filters.value.start_date = range.start
    filters.value.end_date = range.end
    activeDatePreset.value = preset.value
    onFilterChange()
}

const resetFilters = () => {
    filters.value = {
        start_date: '',
        end_date: '',
        type: '',
        account_id: '',
        category_id: '',
        user_id: '',
        status: '',
        min_amount: '',
        max_amount: ''
    }
    activeDatePreset.value = ''
    emit('filters-reset', filters.value)
}

const removeFilter = (filterKey) => {
    if (filterKey === 'date_range') {
        filters.value.start_date = ''
        filters.value.end_date = ''
        activeDatePreset.value = ''
    } else {
        filters.value[filterKey] = ''
    }
    onFilterChange()
}

const onFilterChange = () => {
    // Clean empty values
    const cleanFilters = Object.fromEntries(
        Object.entries(filters.value).filter(([_, value]) => 
            value !== '' && value !== null && value !== undefined
        )
    )
    
    emit('filters-change', cleanFilters)
}

// Watch for initial filters changes
watch(() => props.initialFilters, (newFilters) => {
    filters.value = { ...filters.value, ...newFilters }
}, { deep: true })

onMounted(() => {
    // Emit initial filters
    onFilterChange()
})
</script>