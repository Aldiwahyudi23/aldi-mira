<template>
    <div class="bg-white/80 backdrop-blur-sm rounded-xl md:rounded-2xl shadow-lg md:shadow-xl border border-white/50 overflow-hidden">
        <!-- Table Header dengan gradient romantis -->
        <div class="bg-gradient-to-r from-pink-400/10 to-sky-400/10 py-3 md:py-4 px-3 md:px-4 border-b border-pink-100">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <!-- =============================================================== -->
                    <h2 class="text-lg md:text-xl lg:text-2xl font-bold bg-gradient-to-r from-pink-600 to-sky-600 bg-clip-text text-transparent mb-1 md:mb-2">
                        {{ title }}
                    </h2>
                    <p class="text-gray-600 flex items-center gap-2 text-xs md:text-sm">
                        <span class="text-pink-400">📊</span>
                        {{ description }}
                    </p>
                    <!-- =============================================================== -->
                </div>
                
                <div class="flex flex-nowrap items-center gap-2 w-full lg:w-auto">
                   <!-- Search -->
                    <div v-if="searchable" class="relative flex-1 lg:flex-none">
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="Cari..."
                            class="pl-10 md:pl-12 pr-3 md:pr-4 py-2 border border-gray-200 rounded-lg md:rounded-xl focus:ring-2 focus:ring-pink-300 focus:border-pink-300 transition-all duration-300 bg-white/50 backdrop-blur-sm w-full lg:w-48 xl:w-64 text-sm md:text-base"
                        >
                        <div class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-base md:text-lg">
                            🔍
                        </div>
                    </div>

                    <!-- Create Button -->
                    <slot name="header-action">
                        <BaseButton
                            v-if="showCreateButton"
                            @click="$emit('create')"
                            class="whitespace-nowrap flex-shrink-0 text-xs md:text-sm"
                            size="sm"
                        >
                            <template #icon>➕</template>
                            <span class="hidden xs:inline">Tambah {{ title }}</span>
                            <span class="xs:hidden">Tambah</span>
                        </BaseButton>
                    </slot>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-pink-50/50 to-sky-50/50 border-b border-pink-100">
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-3 md:px-4 lg:px-6 py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-gray-700 whitespace-nowrap"
                            :class="column.class"
                        >
                            <div class="flex items-center gap-1 md:gap-2">
                                <span v-if="column.icon" class="text-pink-400 text-sm md:text-base">{{ column.icon }}</span>
                                <span class="truncate">{{ column.label }}</span>
                                <button
                                    v-if="column.sortable"
                                    @click="sortColumn(column.key)"
                                    class="text-gray-400 hover:text-pink-500 transition-colors text-xs md:text-sm ml-1"
                                >
                                    {{ sortKey === column.key ? (sortDirection === 'asc' ? '↑' : '↓') : '↕' }}
                                </button>
                            </div>
                        </th>
                        <th v-if="showActions" class="px-3 md:px-4 lg:px-6 py-3 md:py-4 text-right text-xs md:text-sm font-semibold text-gray-700 whitespace-nowrap">
                            <div class="flex items-center gap-1 md:gap-2 justify-end">
                                <span class="text-pink-400 text-sm md:text-base">⚡</span>
                                <span class="truncate">Aksi</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pink-100/50">
                    <tr
                        v-for="(item, index) in paginatedData"
                        :key="item.id || index"
                        class="hover:bg-gradient-to-r from-pink-50/30 to-sky-50/30 transition-all duration-200 group"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-3 md:px-4 lg:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-700 whitespace-nowrap"
                            :class="column.class"
                        >
                            <slot :name="`column-${column.key}`" :item="item">
                                <div v-if="column.type === 'badge'" class="inline-flex">
                                    <span
                                        class="px-2 md:px-3 py-1 rounded-full text-xs font-medium shadow-sm"
                                        :class="getBadgeClasses(item[column.key], column.badgeVariant)"
                                    >
                                        {{ getBadgeText(item[column.key], column.badgeMap) }}
                                    </span>
                                </div>
                                <div v-else-if="column.type === 'icon'" class="text-base md:text-lg">
                                    {{ getIcon(item[column.key], column.iconMap) }}
                                </div>
                                <div v-else-if="column.type === 'avatar'" class="flex items-center gap-2 md:gap-3">
                                    <div class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-gradient-to-r from-pink-300 to-sky-300 flex items-center justify-center text-white text-xs md:text-sm font-bold">
                                        {{ getInitials(item[column.key]) }}
                                    </div>
                                    <span class="truncate max-w-[80px] md:max-w-none">{{ item[column.key] }}</span>
                                </div>
                                <div v-else-if="column.type === 'date'" class="text-gray-600 text-xs md:text-sm">
                                    {{ formatDate(item[column.key]) }}
                                </div>
                                <span v-else class="text-gray-700 truncate max-w-[100px] md:max-w-none block">
                                    {{ item[column.key] }}
                                </span>
                            </slot>
                        </td>
                        <td v-if="showActions" class="px-3 md:px-4 lg:px-6 py-3 md:py-4 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-1 md:gap-2">
                                <slot name="actions" :item="item"></slot>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredData.length === 0 && !loading" class="text-center py-6 md:py-8">
            <!-- =============================================================== -->
            <div class="text-4xl md:text-6xl mb-3 md:mb-4 text-gray-300">📝</div>
            <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">Data belum tersedia</h3>
            <p class="text-gray-500 mb-4 md:mb-6 text-xs md:text-sm max-w-md mx-auto px-2">
                {{ emptyDescription || `Belum ada data ${title.toLowerCase()} yang tersedia. Mulai dengan menambahkan data pertama Anda.` }}
            </p>
            <!-- =============================================================== -->
            <BaseButton
                v-if="showCreateButton"
                @click="$emit('create')"
                size="md"
                class="text-xs md:text-sm"
            >
                <template #icon>➕</template>
                Tambah Data Pertama
            </BaseButton>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-6 md:py-8">
            <!-- =============================================================== -->
            <div class="animate-spin text-3xl md:text-4xl mb-3 md:mb-4 text-pink-400">⏳</div>
            <p class="text-gray-500 text-xs md:text-sm">Memuat data...</p>
            <!-- =============================================================== -->
        </div>

        <!-- Pagination -->
        <div v-if="pagination && filteredData.length > 0" class="bg-gradient-to-r from-pink-50/30 to-sky-50/30 px-3 md:px-4 py-3 border-t border-pink-100">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-3 md:gap-4">
                <!-- Info jumlah data -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600">
                    <span>Menampilkan {{ startIndex }} - {{ endIndex }} dari {{ totalItems }} data</span>
                    
                    <!-- Pilihan items per page -->
                    <div class="flex items-center gap-2">
                        <span>Tampilkan:</span>
                        <select 
                            v-model="itemsPerPage"
                            class="border border-gray-300 rounded-lg px-2 py-1 text-xs md:text-sm focus:ring-2 focus:ring-pink-300 focus:border-pink-300"
                            @change="currentPage = 1"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span class="hidden sm:inline">per halaman</span>
                    </div>
                </div>

                <!-- Navigasi halaman -->
                <div class="flex items-center gap-1 md:gap-2">
                    <!-- Tombol Previous -->
                    <BaseButton
                        variant="secondary"
                        size="sm"
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                        class="!px-2 md:!px-3 !py-1 text-xs"
                    >
                        <template #icon>⬅️</template>
                        <span class="hidden xs:inline">Sebelumnya</span>
                    </BaseButton>

                    <!-- Info halaman -->
                    <div class="flex items-center gap-1 mx-1 md:mx-2">
                        <span class="text-xs md:text-sm text-gray-600">Halaman</span>
                        <span class="font-semibold text-pink-600 text-xs md:text-sm">{{ currentPage }}</span>
                        <span class="text-xs md:text-sm text-gray-600">dari {{ totalPages }}</span>
                    </div>

                    <!-- Tombol Next -->
                    <BaseButton
                        variant="secondary"
                        size="sm"
                        :disabled="currentPage >= totalPages"
                        @click="currentPage++"
                        class="!px-2 md:!px-3 !py-1 text-xs"
                    >
                        <span class="hidden xs:inline">Selanjutnya</span>
                        <template #icon>➡️</template>
                    </BaseButton>
                </div>
            </div>

            <!-- Page numbers (optional) -->
            <div v-if="totalPages > 1" class="flex justify-center gap-1 mt-2 md:mt-3">
                <button
                    v-for="page in visiblePages"
                    :key="page"
                    @click="currentPage = page"
                    class="w-6 h-6 md:w-8 md:h-8 rounded text-xs md:text-sm transition-all duration-200"
                    :class="page === currentPage 
                        ? 'bg-gradient-to-r from-pink-500 to-sky-500 text-white font-bold' 
                        : 'bg-white text-gray-600 hover:bg-pink-50 border border-gray-200'"
                >
                    {{ page }}
                </button>
                
                <span v-if="showEllipsis" class="px-1 md:px-2 text-gray-400 text-xs md:text-sm">...</span>
            </div>
        </div>
    </div>
</template>


<style scoped>
/* Breakpoint untuk screen sangat kecil */
@media (max-width: 475px) {
    .xs\:inline {
        display: inline !important;
    }
    
    .xs\:hidden {
        display: none !important;
    }
}

/* Improve scrolling on mobile */
@media (max-width: 768px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .overflow-x-auto::-webkit-scrollbar {
        display: none;
    }
}
</style>

<script setup>
import { ref, computed, watch } from 'vue';
import BaseButton from './BaseButton.vue';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        default: 'Kelola data dengan mudah dan efisien'
    },
    emptyDescription: {
        type: String,
        default: ''
    },
    columns: {
        type: Array,
        required: true
    },
    data: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    },
    searchable: {
        type: Boolean,
        default: true
    },
    showCreateButton: {
        type: Boolean,
        default: true
    },
    showEdit: {
        type: Boolean,
        default: true
    },
    showDelete: {
        type: Boolean,
        default: true
    },
    showActions: {
        type: Boolean,
        default: true
    },
    pagination: {
        type: Boolean,
        default: false
    },
    itemsPerPage: {
        type: Number,
        default: 10
    }
});

const emit = defineEmits(['create', 'edit', 'delete']);

const searchTerm = ref('');
const sortKey = ref('');
const sortDirection = ref('asc');
const currentPage = ref(1);
const itemsPerPage = ref(props.itemsPerPage);

// Reset current page ketika data berubah
watch(() => props.data, () => {
    currentPage.value = 1;
});

// Filter data berdasarkan search
const filteredData = computed(() => {
    let result = [...props.data];

    // // Search filter
    // if (searchTerm.value) {
    //     const term = searchTerm.value.toLowerCase();
    //     result = result.filter(item => 
    //         props.columns.some(column => 
    //             String(item[column.key] || '').toLowerCase().includes(term)
    //         )
    //     );
    // }

    //   // 🔍 Search filter (pencarian di semua kolom)
    // if (searchTerm.value) {
    //     const term = searchTerm.value.toLowerCase();

    //     result = result.filter(item => {
    //         // Gabungkan semua nilai field dalam 1 string
    //         const allValues = Object.values(item)
    //             .join(' ') // gabungkan semua nilai jadi satu string
    //             .toLowerCase();

    //         return allValues.includes(term);
    //     });
    // }

     // 🔍 Filter berdasarkan search
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();

        result = result.filter(item => {
            return props.columns.some(column => {
                // Ambil nilai kolom (mendukung nested key seperti 'user.name')
                const keys = column.key.split('.');
                let value = item;
                for (const key of keys) {
                    value = value ? value[key] : '';
                }

                // Ubah jadi string dan cocokkan dengan search term
                return String(value || '').toLowerCase().includes(term);
            });
        });
    }

    // Sorting
    if (sortKey.value) {
        result.sort((a, b) => {
            const aVal = a[sortKey.value];
            const bVal = b[sortKey.value];
            
            if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
            return 0;
        });
    }

    return result;
});

// Pagination calculations
const totalItems = computed(() => filteredData.value.length);
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value));
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value + 1);
const endIndex = computed(() => {
    const end = currentPage.value * itemsPerPage.value;
    return end > totalItems.value ? totalItems.value : end;
});

// Data yang ditampilkan di halaman saat ini
const paginatedData = computed(() => {
    if (!props.pagination) {
        return filteredData.value;
    }
    
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredData.value.slice(start, end);
});

// Visible pages untuk pagination numbers
const visiblePages = computed(() => {
    const pages = [];
    const maxVisiblePages = 5;
    
    let startPage = Math.max(1, currentPage.value - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages.value, startPage + maxVisiblePages - 1);
    
    // Adjust start page jika end page mencapai batas
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    
    return pages;
});

const showEllipsis = computed(() => {
    return totalPages.value > 5 && (currentPage.value < totalPages.value - 2 || currentPage.value > 3);
});

const sortColumn = (key) => {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
    currentPage.value = 1; // Reset ke halaman pertama saat sorting
};

const getBadgeClasses = (value, variant = 'default') => {
    const classes = {
        default: {
            active: 'bg-green-100 text-green-800 border border-green-200',
            inactive: 'bg-red-100 text-red-800 border border-red-200',
            pending: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            success: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            warning: 'bg-amber-100 text-amber-800 border border-amber-200',
            info: 'bg-blue-100 text-blue-800 border border-blue-200'
        },
        romantic: {
            active: 'bg-pink-100 text-pink-800 border border-pink-200',
            inactive: 'bg-gray-100 text-gray-800 border border-gray-200',
            pending: 'bg-sky-100 text-sky-800 border border-sky-200',
            success: 'bg-green-100 text-green-800 border border-green-200',
            warning: 'bg-orange-100 text-orange-800 border border-orange-200',
            info: 'bg-purple-100 text-purple-800 border border-purple-200'
        }
    };
    
    const variantClasses = classes[variant] || classes.default;
    return variantClasses[value] || 'bg-gray-100 text-gray-800 border border-gray-200';
};

const getBadgeText = (value, badgeMap = {}) => {
    const defaultMap = {
        active: 'Aktif',
        inactive: 'Nonaktif',
        pending: 'Tertunda',
        success: 'Sukses',
        warning: 'Peringatan',
        info: 'Info'
    };
    
    const map = { ...defaultMap, ...badgeMap };
    return map[value] || value;
};

const getIcon = (value, iconMap = {}) => {
    const defaultMap = {
        active: '✅',
        inactive: '❌',
        pending: '⏳',
        success: '🎉',
        warning: '⚠️',
        info: '💡'
    };
    
    const map = { ...defaultMap, ...iconMap };
    return map[value] || '📄';
};

const getInitials = (name) => {
    return name ? name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID');
};

</script>