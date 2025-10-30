<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed, onMounted, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import BaseTable from '@/Components/Base/BaseTable.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import BaseFilter from '@/Components/Base/BaseFilter.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import SelectInput from '@/Components/Form/SelectInput.vue';
import AccountInput from '@/Components/Form/AccountInput.vue';
import TextAreaInput from '@/Components/Form/TextAreaInput.vue';
import BaseFilterItem from '@/Components/Base/BaseFilterItem.vue';
import ChecklistManager from '@/Components/Project/ChecklistManager.vue';
import NumberInput from '@/Components/Form/NumberInput.vue';

const page = usePage();
const user = page.props.auth.user;

// Data dan state
const items = ref([]);
const itemSummary = ref({});
const loading = ref(false);
const deleting = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const showDetailsModal = ref(false);
const editingItem = ref(null);
const itemToDelete = ref(null);
const selectedItem = ref(null);
const flashMessage = ref(null);

// Computed untuk mengecek apakah item type goods atau material
const isGoodsOrMaterial = computed(() => {
    return ['goods', 'material'].includes(form.item_type);
});

// Computed untuk menghitung planned_amount otomatis
const calculatedPlannedAmount = computed(() => {
    if (!isGoodsOrMaterial.value) return form.planned_amount;
    
    const quantity = parseFloat(form.details.quantity) || 0;
    const unitPrice = parseFloat(form.details.unit_price) || 0;
    return quantity * unitPrice;
});

// Method untuk update planned_amount
const updatePlannedAmount = () => {
    if (isGoodsOrMaterial.value) {
        form.planned_amount = calculatedPlannedAmount.value;
    }
};


// Filter state
const filters = ref({
    item_type: '',
    status: ''
});

// Watch for flash messages
watch(() => page.props.flash, (newFlash) => {
    if (newFlash.success || newFlash.error) {
        flashMessage.value = {
            message: newFlash.success || newFlash.error,
            type: newFlash.type || (newFlash.success ? 'success' : 'error')
        };
        
        setTimeout(() => {
            flashMessage.value = null;
        }, 5000);
    }
}, { immediate: true });

// Forms
const form = useForm({
    item_type: 'goods',
    name: '',
    description: '',
    planned_amount: 0,
    actual_spent: 0,
    status: 'needed',
    details: {},
});

// Props
const props = defineProps({
    items: Array,
    project: Object,
});

// Item type options
const itemTypeOptions = [
    { value: 'goods', label: '🛍️ Barang' },
    { value: 'service', label: '👨‍💼 Jasa' },
    { value: 'document', label: '📄 Dokumen' },
    { value: 'task', label: '✅ Tugas' },
    { value: 'material', label: '🏗️ Material' },
];

// Status options
const statusOptions = [
    { value: 'needed', label: '⏳ Diperlukan' },
    { value: 'in_progress', label: '🚧 Dalam Proses' },
    { value: 'ready', label: '📦 Siap' },
    { value: 'complete', label: '✅ Selesai' },
    { value: 'cancelled', label: '❌ Dibatalkan' },
];

// Purchase type options untuk barang
const purchaseTypeOptions = [
    { value: 'online', label: '🛒 Beli Online' },
    { value: 'store', label: '🏪 Beli di Toko' },
];

// E-commerce platforms dengan icon
const ecommercePlatforms = {
    'shopee': { name: 'Shopee', icon: '🟠', color: 'text-orange-500' },
    'tokopedia': { name: 'Tokopedia', icon: '🟢', color: 'text-green-500' },
    'lazada': { name: 'Lazada', icon: '🔵', color: 'text-blue-500' },
    'blibli': { name: 'Blibli', icon: '🔴', color: 'text-red-500' },
    'bukalapak': { name: 'Bukalapak', icon: '🟡', color: 'text-yellow-500' },
    'other': { name: 'Lainnya', icon: '🛒', color: 'text-gray-500' }
};

// Columns untuk item aktif (selain complete)
const activeColumns = [
    {
        key: 'name',
        label: 'Nama Item',
        icon: '📝',
        sortable: true
    },
    {
        key: 'item_type',
        label: 'Jenis',
        icon: '🏷️',
        sortable: true,
        type: 'badge'
    },
    {
        key: 'planned_amount',
        label: 'Rencana Biaya',
        icon: '💰',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'actual_spent',
        label: 'Realisasi',
        icon: '💸',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'remaining_amount',
        label: 'Sisa',
        icon: '📊',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'progress_percentage',
        label: 'Progress',
        icon: '📈',
        sortable: true,
        type: 'progress'
    },
    {
        key: 'status',
        label: 'Status',
        icon: '🟢',
        sortable: true,
        type: 'badge'
    },
    {
        key: 'created_at',
        label: 'Dibuat',
        icon: '📅',
        sortable: true,
        type: 'date'
    }
];

// Columns untuk item selesai (complete)
const completedColumns = [
    {
        key: 'name',
        label: 'Nama Item',
        icon: '📝',
        sortable: true
    },
    {
        key: 'item_type',
        label: 'Jenis',
        icon: '🏷️',
        sortable: true,
        type: 'badge'
    },
    {
        key: 'planned_amount',
        label: 'Rencana Biaya',
        icon: '💰',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'actual_spent',
        label: 'Sisa Biaya',
        icon: '💸',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'status',
        label: 'Status',
        icon: '✅',
        sortable: true,
        type: 'badge'
    },
    {
        key: 'completed_at',
        label: 'Selesai',
        icon: '📅',
        sortable: true,
        type: 'date'
    }
];

// Filter options untuk BaseFilter
const filterOptions = {
    item_type: {
        label: 'Jenis Item',
        options: [
            { value: '', label: 'Semua Jenis' },
            ...itemTypeOptions
        ]
    },
    status: {
        label: 'Status',
        options: [
            { value: '', label: 'Semua Status' },
            ...statusOptions
        ]
    }
};

// Computed untuk filtered data
const filteredItems = computed(() => {
    let filtered = items.value;

    if (filters.value.item_type) {
        filtered = filtered.filter(item => item.item_type === filters.value.item_type);
    }

    if (filters.value.status) {
        filtered = filtered.filter(item => item.status === filters.value.status);
    }

    return filtered;
});

// Computed untuk filtered summary
const filteredSummary = computed(() => {
    const filtered = filteredItems.value;
    
    return {
        total_items: filtered.length,
        goods_count: filtered.filter(item => item.item_type === 'goods').length,
        services_count: filtered.filter(item => item.item_type === 'service').length,
        documents_count: filtered.filter(item => item.item_type === 'document').length,
        tasks_count: filtered.filter(item => item.item_type === 'task').length,
        materials_count: filtered.filter(item => item.item_type === 'material').length,
        total_planned: filtered.reduce((sum, item) => sum + parseFloat(item.planned_amount || 0), 0),
        total_spent: filtered.reduce((sum, item) => sum + parseFloat(item.actual_spent || 0), 0),
        total_remaining: filtered.reduce((sum, item) => sum + parseFloat(item.remaining_amount || 0), 0),
    };
});

// Load data
const loadItems = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('projects.items.index', { project: props.project.id }));
        items.value = response.data.items || response.data;
    } catch (error) {
        console.error('Error loading items:', error);
        flashMessage.value = {
            message: 'Gagal memuat data item',
            type: 'error'
        };
    } finally {
        loading.value = false;
    }
};

// Helper untuk mengecek apakah item type goods atau material (untuk modal detail)
const isItemGoodsOrMaterial = (item) => {
    return item && ['goods', 'material'].includes(item.item_type);
};

// Helper untuk mengecek apakah ada purchase details selain quantity dan unit_price
const hasPurchaseDetails = (item) => {
    if (!item.details) return false;
    
    const details = { ...item.details };
    // Hapus quantity dan unit_price dari pengecekan
    delete details.quantity;
    delete details.unit_price;
    
    return Object.keys(details).length > 0;
};

const loadItemSummary = async () => {
    try {
        const response = await axios.get(route('projects.items.api.summary', { project: props.project.id }));
        itemSummary.value = response.data;
    } catch (error) {
        console.error('Error loading item summary:', error);
    }
};

// Initialize data
onMounted(() => {
    items.value = props.items;
    loadItemSummary();
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    form.item_type = 'goods';
    form.status = 'needed'; // Default status
    form.planned_amount = 0;
    form.actual_spent = 0; // Selalu 0 untuk create
    form.details = {
        quantity: 1, // Default quantity
        unit_price: 0 // Default unit price
    };
    showModal.value = true;
};

const openEditModal = (item) => {
    if (!item.can_edit) {
        flashMessage.value = {
            message: 'Hanya pemilik kategori dan partner dalam family yang sama yang dapat mengedit item',
            type: 'error'
        };
        return;
    }

    editingItem.value = item;
    form.item_type = item.item_type;
    form.name = item.name;
    form.description = item.description;
    form.planned_amount = item.planned_amount;
    form.actual_spent = item.actual_spent; // Sesuai database untuk edit
    form.status = item.status; // Tetap ambil dari database untuk edit
    form.details = item.details || {};
    
    // Set default untuk goods dan material jika belum ada
    if (['goods', 'material'].includes(item.item_type)) {
        if (!form.details.quantity) form.details.quantity = 1;
        if (!form.details.unit_price) form.details.unit_price = 0;
    }
    
    showModal.value = true;
};

const openDeleteModal = (item) => {
    if (!item.can_edit) {
        flashMessage.value = {
            message: 'Hanya pemilik kategori dan partner dalam family yang sama yang dapat menghapus item',
            type: 'error'
        };
        return;
    }

    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const openDetailsModal = (item) => {
    selectedItem.value = item;
    showDetailsModal.value = true;
};

const saveItem = () => {
    if (editingItem.value) {
        form.put(route('projects.items.update', { 
            project: props.project.id, 
            item: editingItem.value.id 
        }), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadItems();
                loadItemSummary();
                form.reset();
                editingItem.value = null;
            },
            onError: (errors) => {
                console.error('Update error:', errors);
            }
        });
    } else {
        form.post(route('projects.items.store', { project: props.project.id }), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadItems();
                loadItemSummary();
                form.reset();
            },
            onError: (errors) => {
                console.error('Create error:', errors);
            }
        });
    }
};

const deleteItem = () => {
    if (!itemToDelete.value) return;

    deleting.value = true;

    router.delete(route('projects.items.destroy', { 
        project: props.project.id, 
        item: itemToDelete.value.id 
    }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            loadItems();
            loadItemSummary();
            itemToDelete.value = null;
            deleting.value = false;
        },
        onError: (errors) => {
            console.error('Delete error:', errors);
            showDeleteModal.value = false;
            deleting.value = false;
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    editingItem.value = null;
    form.clearErrors();
    form.reset();
};

// Format functions
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const formatItemType = (type) => {
    const typeMap = {
        goods: 'Barang',
        service: 'Jasa',
        document: 'Dokumen',
        task: 'Tugas',
        material: 'Material'
    };
    return typeMap[type] || type;
};

const formatStatus = (status) => {
    const statusMap = {
        needed: 'Diperlukan',
        in_progress: 'Dalam Proses',
        ready: 'Siap',
        complete: 'Selesai',
        cancelled: 'Dibatalkan'
    };
    return statusMap[status] || status;
};

// Helper functions untuk details
const getPurchaseType = (details) => {
    return details?.purchase_type || 'online';
};

const getEcommercePlatform = (details) => {
    return details?.ecommerce_platform || 'other';
};

const getOnlineLink = (details) => {
    return details?.online_link || '';
};

const getStoreAddress = (details) => {
    return details?.store_address || '';
};

const getStoreMaps = (details) => {
    return details?.store_maps || '';
};

const openLink = (url) => {
    if (url) {
        window.open(url, '_blank');
    }
};

// Dynamic detail fields berdasarkan item type
const detailFields = computed(() => {
    const baseFields = {
        goods: [
            // Purchase Type Radio
            { 
                key: 'purchase_type', 
                label: 'Cara Pembelian', 
                type: 'radio', 
                icon: '🛒',
                options: purchaseTypeOptions
            },
            // Online fields
            ...(form.details?.purchase_type === 'online' ? [
                { 
                    key: 'ecommerce_platform', 
                    label: 'Platform Online', 
                    type: 'select', 
                    icon: '📱',
                    options: Object.entries(ecommercePlatforms).map(([value, data]) => ({
                        value,
                        label: `${data.icon} ${data.name}`
                    }))
                },
                { 
                    key: 'online_link', 
                    label: 'Link Produk', 
                    type: 'url', 
                    icon: '🔗',
                    placeholder: 'https://...'
                }
            ] : []),
            // Store fields
            ...(form.details?.purchase_type === 'store' ? [
                { 
                    key: 'store_maps', 
                    label: 'Link Google Maps', 
                    type: 'url', 
                    icon: '🗺️',
                    placeholder: 'https://maps.google.com/...'
                },
                { 
                    key: 'store_address', 
                    label: 'Alamat Toko', 
                    type: 'textarea', 
                    icon: '🏪',
                    placeholder: 'Alamat lengkap toko...'
                }
            ] : []),
            // Common goods fields
            { key: 'ukuran', label: 'Ukuran', type: 'text', icon: '📏' },
            { key: 'warna', label: 'Warna', type: 'text', icon: '🎨' },
            { key: 'material', label: 'Material', type: 'text', icon: '⚙️' },
            { key: 'merk', label: 'Merk', type: 'text', icon: '🏷️' },
        ],
        service: [
            { key: 'vendor_kontak', label: 'Kontak Vendor', type: 'text', icon: '📞' },
            { key: 'tanggal_kontrak', label: 'Tanggal Kontrak', type: 'date', icon: '📅' },
            { key: 'sisa_pembayaran', label: 'Sisa Pembayaran', type: 'number', icon: '💰' },
            { key: 'menu_pilihan', label: 'Menu Pilihan', type: 'text', icon: '🍽️' },
            { key: 'lokasi', label: 'Lokasi', type: 'text', icon: '📍' },
        ],
        document: [
            { key: 'tanggal_kadaluarsa', label: 'Tanggal Kadaluarsa', type: 'date', icon: '📅' },
            { key: 'lokasi_fisik', label: 'Lokasi Fisik', type: 'text', icon: '📁' },
            { key: 'status_legalitas', label: 'Status Legalitas', type: 'text', icon: '⚖️' },
            { key: 'nomor_dokumen', label: 'Nomor Dokumen', type: 'text', icon: '🔢' },
        ],
        task: [
            { key: 'pihak_pengurus', label: 'Pihak Pengurus', type: 'text', icon: '👤' },
            { key: 'biaya_administrasi', label: 'Biaya Administrasi', type: 'number', icon: '💰' },
            { key: 'progress_persentase', label: 'Progress (%)', type: 'number', icon: '📊' },
            { key: 'deadline', label: 'Deadline', type: 'date', icon: '⏰' },
        ],
        material: [
            { key: 'kuantitas_target', label: 'Kuantitas Target', type: 'number', icon: '📦' },
            { key: 'satuan', label: 'Satuan', type: 'text', icon: '⚖️' },
            { key: 'supplier_preferensi', label: 'Supplier Preferensi', type: 'text', icon: '🏪' },
            { key: 'spesifikasi', label: 'Spesifikasi', type: 'text', icon: '📋' },
        ]
    };
    return baseFields[form.item_type] || [];
});

// Update detail field
const updateDetail = (key, value) => {
    form.details = {
        ...form.details,
        [key]: value
    };
};

// Computed untuk memisahkan data
const activeItems = computed(() => {
    return filteredItems.value.filter(item => item.status !== 'complete');
});

const completedItems = computed(() => {
    return filteredItems.value.filter(item => item.status === 'complete');
});

// Computed untuk summary yang terpisah
const activeSummary = computed(() => {
    const filtered = activeItems.value;
    
    return {
        total_items: filtered.length,
        total_planned: filtered.reduce((sum, item) => sum + parseFloat(item.planned_amount || 0), 0),
        total_spent: filtered.reduce((sum, item) => sum + parseFloat(item.actual_spent || 0), 0),
    };
});

const completedSummary = computed(() => {
    const filtered = completedItems.value;
    
    return {
        total_items: filtered.length,
        total_planned: filtered.reduce((sum, item) => sum + parseFloat(item.planned_amount || 0), 0),
        total_spent: filtered.reduce((sum, item) => sum + parseFloat(item.actual_spent || 0), 0),
    };
});

// Watch untuk update planned_amount ketika quantity atau unit_price berubah
watch([() => form.details.quantity, () => form.details.unit_price], () => {
    if (isGoodsOrMaterial.value) {
        form.planned_amount = calculatedPlannedAmount.value;
    }
});

// Watch untuk update planned_amount ketika item_type berubah
watch(() => form.item_type, (newType) => {
    if (['goods', 'material'].includes(newType)) {
        // Set default values untuk quantity dan unit_price
        if (!form.details.quantity) form.details.quantity = 1;
        if (!form.details.unit_price) form.details.unit_price = 0;
        updatePlannedAmount();
    }
});

// Reset details when item type changes
watch(() => form.item_type, (newType) => {
    if (newType !== 'goods') {
        // Hapus purchase-related details jika bukan barang
        const { purchase_type, ecommerce_platform, online_link, store_maps, store_address, ...otherDetails } = form.details;
        form.details = otherDetails;
    }
});
</script>

<template>
    <AppLayout :title="`Item Project - ${project.name}`">
        <div class="py-2 min-h-screen relative overflow-hidden">
            <div class="w-full px-4 sm:px-6 lg:px-8 relative z-10">
                <!-- Flash Message -->
                <div 
                    v-if="flashMessage" 
                    class="mb-6 p-4 rounded-2xl border backdrop-blur-sm transition-all duration-300"
                    :class="{
                        'bg-green-50 border-green-200 text-green-800': flashMessage.type === 'success',
                        'bg-red-50 border-red-200 text-red-800': flashMessage.type === 'error'
                    }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">
                                {{ flashMessage.type === 'success' ? '✅' : '⚠️' }}
                            </span>
                            <span class="font-medium">{{ flashMessage.message }}</span>
                        </div>
                        <button 
                            @click="flashMessage = null"
                            class="text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            ✕
                        </button>
                    </div>
                </div>
                
                <!-- Hero Section -->
                <div class="text-center mb-4">
                    <h1 class="text-3xl md:text-4xl font-extrabold text-rose-600 drop-shadow-md mb-3">
                        📋 Kelola Item Project
                    </h1>
                    <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                        Kelola semua item, barang, jasa, dokumen, dan tugas untuk project 
                        <span class="text-blue-500 font-semibold">{{ project.name }}</span>. 
                        Pantau progress dan realisasi biaya untuk setiap item 💫
                    </p>
                </div>

                <!-- 🌸 INFORMASI PROJECT ELEGAN RESPONSIF -->
                <div class="relative overflow-hidden bg-gradient-to-br from-pink-50 via-white to-blue-50 rounded-3xl border border-pink-100/40 shadow-md p-4 mb-4">
                    <!-- Ornamen Latar -->
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-pink-100/40 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-blue-100/40 rounded-full blur-3xl"></div>

                    <!-- Header -->
                    <div class="relative flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-pink-400 to-rose-500 rounded-2xl flex items-center justify-center shadow-md">
                            <span class="text-2xl text-white">🌷</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Detail Proyek</h2>
                            <p class="text-sm text-gray-500 italic">“Setiap proyek punya cerita yang indah di baliknya.”</p>
                        </div>
                    </div>

                    <!-- Isi Informasi (Responsif) -->
                    <div class="relative grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Nama Project -->
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 bg-gradient-to-r from-pink-200 to-rose-100 rounded-xl flex items-center justify-center shadow-sm">
                                💼
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Nama Project</p>
                                <p class="text-base font-medium text-gray-800 tracking-wide">{{ project.name }}</p>
                                <p class="text-xs text-gray-500 mt-1">Kategori: <span class="italic">{{ project.category }}</span></p>
                            </div>
                        </div>

                        <!-- Target Budget -->
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 bg-gradient-to-r from-yellow-200 to-orange-100 rounded-xl flex items-center justify-center shadow-sm">
                                💰
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Target Budget</p>
                                <p class="text-base font-medium text-gray-800">{{ formatCurrency(project.target_total_amount) }}</p>
                            </div>
                        </div>

                        <!-- Status Project -->
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 bg-gradient-to-r from-blue-200 to-indigo-100 rounded-xl flex items-center justify-center shadow-sm">
                                📊
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Status Project</p>
                                <p class="text-base font-medium text-gray-800 capitalize">{{ formatStatus(project.status) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Quote -->
                    <div class="mt-4 border-t border-pink-100 pt-4 text-center">
                        <p class="text-sm text-gray-500 italic">
                            “Sebuah proyek bukan sekadar angka, tapi tentang harapan, usaha, dan keindahan prosesnya.” 💖
                        </p>
                    </div>
                </div>


                <!-- Filter Section -->
                <BaseFilterItem
                    v-model:filters="filters"
                    :options="filterOptions"
                    class="mb-4"
                />

                <!-- Stats Cards - Responsive grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                    <!-- Total Items -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-rose-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-rose-400 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">📋</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ filteredSummary.total_items || 0 }}
                            </h3>
                            <p class="text-sm text-gray-600">Total Item</p>
                            <p class="text-xs text-rose-500 mt-1" v-if="filters.item_type || filters.status">
                                🔍 Filter aktif
                            </p>
                        </div>
                    </BaseCard>

                    <!-- Total Rencana Biaya -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-blue-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">💰</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(filteredSummary.total_planned) }}
                            </h3>
                            <p class="text-sm text-gray-600">Rencana Biaya</p>
                        </div>
                    </BaseCard>

                    <!-- Total Realisasi -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-orange-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-orange-400 to-red-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">💸</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(filteredSummary.total_spent) }}
                            </h3>
                            <p class="text-sm text-gray-600">Realisasi</p>
                            <p class="text-xs text-gray-500 mt-1" v-if="filteredSummary.total_planned > 0">
                                {{ ((filteredSummary.total_spent / filteredSummary.total_planned) * 100).toFixed(1) }}%
                            </p>
                        </div>
                    </BaseCard>

                    <!-- Sisa Anggaran -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-green-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">📊</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(filteredSummary.total_remaining) }}
                            </h3>
                            <p class="text-sm text-gray-600">Sisa Anggaran</p>
                        </div>
                    </BaseCard>
                </div>

                <!-- Type Distribution -->
                <div class="grid grid-cols-4 md:grid-cols-5 gap-2 mb-4">
                    <BaseCard 
                        class="text-center cursor-pointer transition-all duration-300 hover:scale-105" 
                        :class="{
                            'ring-2 ring-pink-400 shadow-lg': filters.item_type === type,
                            'hover:shadow-md': !filters.item_type
                        }"
                        v-for="type in ['goods', 'service', 'document', 'task', 'material']" 
                        :key="type"
                        @click="filters.item_type = filters.item_type === type ? '' : type"
                    >
                        <div class="p-3">
                            <div class="text-2xl mb-1">
                                {{ 
                                    type === 'goods' ? '🛍️' :
                                    type === 'service' ? '👨‍💼' :
                                    type === 'document' ? '📄' :
                                    type === 'task' ? '✅' : '🏗️'
                                }}
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">
                                <!-- PERBAIKAN: Gunakan property yang benar -->
                                {{ 
                                    type === 'goods' ? filteredSummary.goods_count :
                                    type === 'service' ? filteredSummary.services_count :
                                    type === 'document' ? filteredSummary.documents_count :
                                    type === 'task' ? filteredSummary.tasks_count :
                                    filteredSummary.materials_count 
                                }}
                            </h3>
                            <p class="text-xs text-gray-600 capitalize">
                                {{ formatItemType(type) }}
                            </p>
                            <p class="text-xs text-pink-500 mt-1" v-if="filters.item_type === type">
                                ✓ Terfilter
                            </p>
                        </div>
                    </BaseCard>
                </div>

                <!-- Tabel Item Aktif -->
                <div v-if="activeItems.length > 0" class="mb-8">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-200 mb-4">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-3 mb-2">
                            <span class="text-2xl">🚀</span>
                            Item Aktif
                            <span class="text-sm font-normal text-gray-600 bg-white px-3 py-1 rounded-full">
                                {{ activeItems.length }} item
                            </span>
                        </h3>
                        <p class="text-gray-600">
                            Item yang masih dalam proses pengerjaan. Total rencana biaya: 
                            <span class="font-bold text-blue-600">{{ formatCurrency(activeSummary.total_planned) }}</span>
                        </p>
                    </div>

                    <BaseTable
                        :title="`Item Aktif - ${project.name}`"
                        :description="`${activeItems.length} item sedang dalam proses`"
                        :columns="activeColumns"
                        :data="activeItems"
                        :loading="loading"
                        :show-actions="true"
                        :pagination="true"
                        :items-per-page="10"
                        @create="openCreateModal"
                        @edit="openEditModal"
                        @delete="openDeleteModal"
                    >
                        <!-- Custom column for item type -->
                        <template #column-item_type="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ item.item_type_icon }}</span>
                                <span 
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                    :class="item.item_type_badge"
                                >
                                    {{ formatItemType(item.item_type) }}
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for planned amount -->
                        <template #column-planned_amount="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg text-blue-600">💰</span>
                                <span class="font-bold text-gray-800 text-sm text-blue-700">
                                    {{ formatCurrency(item.planned_amount) }}
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for actual spent -->
                        <template #column-actual_spent="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg text-orange-600">💸</span>
                                <span class="font-bold text-gray-800 text-sm text-orange-700">
                                    {{ formatCurrency(item.actual_spent) }}
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for remaining amount -->
                        <template #column-remaining_amount="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg" :class="item.remaining_amount >= 0 ? 'text-green-600' : 'text-red-600'">
                                    📊
                                </span>
                                <span class="font-bold text-sm" :class="item.remaining_amount >= 0 ? 'text-green-700' : 'text-red-700'">
                                    {{ formatCurrency(item.remaining_amount) }}
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for progress -->
                        <template #column-progress_percentage="{ item }">
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="font-medium">{{ item.progress_percentage.toFixed(1) }}%</span>
                                    <span>{{ formatCurrency(item.actual_spent) }} / {{ formatCurrency(item.planned_amount) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="h-2 rounded-full transition-all duration-300"
                                        :class="{
                                            'bg-red-500': item.progress_percentage >= 100,
                                            'bg-orange-400': item.progress_percentage >= 80 && item.progress_percentage < 100,
                                            'bg-yellow-400': item.progress_percentage >= 50 && item.progress_percentage < 80,
                                            'bg-green-500': item.progress_percentage < 50
                                        }"
                                        :style="{ width: Math.min(item.progress_percentage, 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </template>

                        <!-- Custom column for status -->
                        <template #column-status="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ item.status_icon }}</span>
                                <span 
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                    :class="item.status_badge"
                                >
                                    {{ formatStatus(item.status) }}
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for date -->
                        <template #column-created_at="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">📅</span>
                                <span class="font-medium text-gray-700 text-sm">{{ formatDate(item.created_at) }}</span>
                            </div>
                        </template>

                        <!-- Custom actions slot -->
                        <template #actions="{ item }">
                            <BaseButton
                                @click="openDetailsModal(item)"
                                variant="primary"
                                size="sm"
                                class="!px-2 !py-2"
                                title="Lihat detail item"
                            >
                                <template #icon>👁️</template>
                                Detail
                            </BaseButton>
                            <BaseButton
                                @click="openEditModal(item)"
                                variant="secondary"
                                size="sm"
                                class="!px-2 !py-2"
                                :disabled="!item.can_edit" 
                                :title="!item.can_edit ? 'Hanya pemilik kategori dan partner yang dapat mengedit' : 'Edit item'"
                            >
                                <template #icon>✏️</template>
                                Edit
                            </BaseButton>
                            <BaseButton
                                @click="openDeleteModal(item)"
                                variant="danger"
                                size="sm"
                                class="!px-2 !py-2"
                                :disabled="!item.can_edit" 
                                :title="!item.can_edit ? 'Hanya pemilik kategori dan partner yang dapat menghapus' : 'Hapus item'"
                            >
                                <template #icon>🗑️</template>
                                Hapus
                            </BaseButton>
                        </template>
                    </BaseTable>
                </div>

                <!-- Tabel Item Selesai -->
                <div v-if="completedItems.length > 0" class="mb-8">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-200 mb-4">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-3 mb-2">
                            <span class="text-2xl">✅</span>
                            Item Selesai
                            <span class="text-sm font-normal text-gray-600 bg-white px-3 py-1 rounded-full">
                                {{ completedItems.length }} item
                            </span>
                        </h3>
                        <p class="text-gray-600">
                            Item yang sudah selesai dikerjakan. Total sisa biaya yang telah dihabiskan: 
                            <span class="font-bold text-green-600">{{ formatCurrency(completedSummary.total_spent) }}</span>
                        </p>
                    </div>

                    <BaseTable
                        :title="`Item Selesai - ${project.name}`"
                        :description="`${completedItems.length} item sudah selesai`"
                        :columns="completedColumns"
                        :data="completedItems"
                        :loading="loading"
                        :show-actions="true" 
                        :show-create-button="false" 
                        :pagination="true"
                        :items-per-page="10"
                    >
                        <!-- Custom column for item type -->
                        <template #column-item_type="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ item.item_type_icon }}</span>
                                <span 
                                    class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                                >
                                    {{ formatItemType(item.item_type) }}
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for planned amount -->
                        <template #column-planned_amount="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg text-blue-600">💰</span>
                                <span class="font-bold text-gray-800 text-sm text-blue-700">
                                    {{ formatCurrency(item.planned_amount) }}
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for actual spent -->
                        <template #column-actual_spent="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg text-green-600">💸</span>
                                <span class="font-bold text-gray-800 text-sm text-green-700">
                                    {{ formatCurrency(item.actual_spent) }}
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for status -->
                        <template #column-status="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">✅</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Selesai
                                </span>
                            </div>
                        </template>

                        <!-- Custom column for completed date -->
                        <template #column-completed_at="{ item }">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">📅</span>
                                <span class="font-medium text-gray-700 text-sm">
                                    {{ formatDate(item.updated_at) }} <!-- Gunakan updated_at sebagai tanggal selesai -->
                                </span>
                            </div>
                        </template>

                                <!-- Custom actions slot -->
                        <template #actions="{ item }">
                            <BaseButton
                                @click="openDetailsModal(item)"
                                variant="primary"
                                size="sm"
                                class="!px-2 !py-2"
                                title="Lihat detail item"
                            >
                                <template #icon>👁️</template>
                                Detail
                            </BaseButton>

                        </template>
                    </BaseTable>
                </div>

                <!-- Empty State untuk kedua tabel -->
                <div v-if="filteredItems.length === 0 && !loading" class="text-center mt-6">
                    <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-lg p-8 border border-gray-100">
                        <div class="text-6xl mb-4">📋</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">
                            {{ items.length === 0 ? 'Belum ada item' : 'Tidak ada item yang sesuai filter' }}
                        </h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            <span v-if="items.length === 0">
                                Mulai dengan menambahkan item pertama untuk project {{ project.name }}
                            </span>
                            <span v-else>
                                Coba ubah filter atau hapus filter untuk melihat semua item
                            </span>
                        </p>
                        <div class="flex gap-3 justify-center">
                            <BaseButton
                                @click="openCreateModal"
                                class="px-6 py-3"
                                v-if="items.length === 0"
                            >
                                <template #icon>➕</template>
                                Tambah Item Pertama
                            </BaseButton>
                            <BaseButton
                                @click="filters = { item_type: '', status: '' }"
                                variant="secondary"
                                class="px-6 py-3"
                                v-else
                            >
                                <template #icon>🔍</template>
                                Hapus Filter
                            </BaseButton>
                        </div>
                    </div>
                </div>

                <!-- Empty State khusus untuk item aktif -->
                <div v-else-if="activeItems.length === 0 && completedItems.length > 0 && !loading" class="text-center mt-6">
                    <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-lg p-8 border border-gray-100">
                        <div class="text-6xl mb-4">🎉</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">
                            Semua Item Sudah Selesai!
                        </h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            Selamat! Semua item untuk project {{ project.name }} sudah selesai dikerjakan. 
                            Anda bisa menambahkan item baru jika diperlukan.
                        </p>
                        <BaseButton
                            @click="openCreateModal"
                            class="px-6 py-3"
                        >
                            <template #icon>➕</template>
                            Tambah Item Baru
                        </BaseButton>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <BaseModal
                    v-model:show="showModal"
                    :title="editingItem ? 'Edit Item Project' : 'Tambah Item Project Baru'"
                    :description="editingItem ? 
                        'Perbarui informasi item project' : 
                        'Tambahkan item baru untuk project ' + project.name"
                    :icon="editingItem ? '✏️' : '➕'"
                    :confirm-text="editingItem ? 'Perbarui' : 'Simpan'"
                    :confirm-loading="form.processing"
                    @confirm="saveItem"
                    @close="closeModal"
                    size="xl"
                >
                    <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                        <!-- Item Type -->
                        <SelectInput
                            v-model="form.item_type"
                            label="Jenis Item"
                            placeholder="Pilih jenis item"
                            :options="itemTypeOptions"
                            :error="form.errors.item_type"
                            icon="🏷️"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Name -->
                        <TextInput
                            v-model="form.name"
                            label="Nama Item"
                            placeholder="Contoh: Cincin Nikah, Vendor Katering, IMB, Semen Portland"
                            :error="form.errors.name"
                            icon="📝"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Description -->
                        <TextAreaInput
                            v-model="form.description"
                            label="Deskripsi"
                            placeholder="Deskripsi detail tentang item ini..."
                            :error="form.errors.description"
                            icon="📄"
                            :disabled="form.processing"
                        />

                        <!-- Input untuk Goods dan Material -->
                        <div v-if="isGoodsOrMaterial" class="space-y-4">
                            <!-- Qty dan Unit Price -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Quantity -->
                                    <AccountInput
                                        label="Qty Barang"
                                        v-model="form.details.quantity"
                                        placeholder="Jumlah barang"
                                        icon="💰"
                                        required
                                        min="1"
                                        :disabled="form.processing"
                                        @input="updatePlannedAmount"
                                    />


                                <!-- Harga Satuan -->
                                    <AccountInput
                                        label="Rencana Biaya"
                                        v-model="form.details.unit_price"
                                        placeholder="Harga per unit"
                                        icon="💰"
                                        required
                                        account-type="account_number"
                                        min="0"
                                        :disabled="form.processing"
                                        @input="updatePlannedAmount"

                                    />
                        
                            </div>

                            <!-- Hasil Perhitungan -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-4 border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Total Rencana Biaya:</p>
                                        <p class="text-xs text-gray-500">
                                            {{ form.details.quantity || 0 }} × {{ formatCurrency(form.details.unit_price || 0) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xl font-bold text-green-600">
                                            {{ formatCurrency(calculatedPlannedAmount) }}
                                        </p>
                                        <p class="text-xs text-gray-500">Terhitung otomatis</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden planned_amount -->
                            <input type="hidden" v-model="form.planned_amount" />
                        </div>

                        <!-- Input Planned Amount untuk jenis item lainnya -->
                        <div v-else class="grid grid-cols-1 gap-4">
                            <!-- Planned Amount -->
                            <AccountInput
                                v-model="form.planned_amount"
                                label="Rencana Biaya"
                                placeholder="Biaya yang direncanakan"
                                :error="form.errors.planned_amount"
                                icon="💰"
                                account-type="account_number"
                                :disabled="form.processing"
                            />
                        </div>

                        <!-- Hidden Actual Spent (selalu 0 untuk create, sesuai database untuk edit) -->
                        <input type="hidden" v-model="form.actual_spent" />

                        <!-- Hidden Status (default: needed) -->
                        <input type="hidden" v-model="form.status" />

                        <!-- Dynamic Detail Fields -->
                        <div v-if="detailFields.length > 0" class="space-y-4">
                            <div class="bg-gradient-to-r from-pink-50 to-rose-50 rounded-2xl p-4 border border-pink-100">
                                <h4 class="font-semibold text-gray-800 flex items-center gap-2 text-lg mb-4">
                                    <span>✨</span>
                                    Detail Tambahan
                                </h4>
                                
                                <div class="space-y-4">
                                    <div v-for="field in detailFields" :key="field.key" class="space-y-2">
                                        <!-- Radio Button untuk Purchase Type -->
                                        <div v-if="field.type === 'radio'" class="space-y-3">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <div class="flex gap-4">
                                                <label 
                                                    v-for="option in field.options" 
                                                    :key="option.value"
                                                    class="flex items-center space-x-2 cursor-pointer p-3 rounded-xl border-2 transition-all"
                                                    :class="form.details[field.key] === option.value 
                                                        ? 'border-pink-400 bg-pink-50 shadow-sm' 
                                                        : 'border-gray-200 hover:border-pink-200'"
                                                >
                                                    <input
                                                        type="radio"
                                                        :value="option.value"
                                                        v-model="form.details[field.key]"
                                                        class="text-pink-500 focus:ring-pink-400"
                                                    >
                                                    <span class="text-sm font-medium text-gray-700">
                                                        {{ option.label }}
                                                    </span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Select Input -->
                                        <div v-else-if="field.type === 'select'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <select
                                                v-model="form.details[field.key]"
                                                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all bg-white"
                                            >
                                                <option value="">Pilih {{ field.label.toLowerCase() }}</option>
                                                <option v-for="option in field.options" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Text Input -->
                                        <div v-else-if="field.type === 'text' || field.type === 'url'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <input
                                                :type="field.type"
                                                v-model="form.details[field.key]"
                                                :placeholder="field.placeholder || `Masukkan ${field.label.toLowerCase()}`"
                                                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"
                                            >
                                        </div>

                                        <!-- Textarea Input -->
                                        <div v-else-if="field.type === 'textarea'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <textarea
                                                v-model="form.details[field.key]"
                                                :placeholder="field.placeholder || `Masukkan ${field.label.toLowerCase()}`"
                                                rows="3"
                                                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all resize-none"
                                            ></textarea>
                                        </div>

                                        <!-- Number Input -->
                                        <div v-else-if="field.type === 'number'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <input
                                                type="number"
                                                v-model="form.details[field.key]"
                                                :placeholder="field.placeholder || `Masukkan ${field.label.toLowerCase()}`"
                                                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"
                                            >
                                        </div>

                                        <!-- Date Input -->
                                        <div v-else-if="field.type === 'date'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <input
                                                type="date"
                                                v-model="form.details[field.key]"
                                                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200">
                            <p class="text-sm text-gray-700 flex items-start gap-2">
                                <span class="text-lg mt-0.5">💡</span>
                                <span>
                                    <strong class="block">Jenis Item:</strong>
                                    • <strong>Barang 🛍️</strong>: Physical goods yang dibeli (hitung otomatis dari quantity × harga satuan)<br>
                                    • <strong>Jasa 👨‍💼</strong>: Services dari vendor/penyedia jasa<br>
                                    • <strong>Dokumen 📄</strong>: Legal documents dan surat-surat<br>
                                    • <strong>Tugas ✅</strong>: Tasks dan pekerjaan yang perlu diselesaikan<br>
                                    • <strong>Material 🏗️</strong>: Bahan bangunan dan material konstruksi (hitung otomatis dari quantity × harga satuan)
                                </span>
                            </p>
                        </div>

                        <!-- Error Summary -->
                        <div v-if="form.hasErrors" class="p-2 bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-2xl">
                            <p class="text-sm text-red-600 flex items-center gap-2">
                                <span>⚠️</span>
                                Terdapat kesalahan dalam pengisian form. Silakan periksa kembali input Anda.
                            </p>
                        </div>
                    </div>
                </BaseModal>

                <!-- Item Details Modal -->
                <BaseModal
                    v-model:show="showDetailsModal"
                    :title="selectedItem ? selectedItem.name : 'Detail Item'"
                    :description="selectedItem ? `Detail lengkap untuk item ${selectedItem.name}` : 'Detail item'"
                    icon="👁️"
                    @close="showDetailsModal = false"
                    size="lg"
                >
                    <div v-if="selectedItem" class="space-y-6">
<!-- Basic Info dengan layout yang lebih romantic -->
<div class="bg-gradient-to-r from-pink-50 to-rose-50 rounded-2xl p-6 border border-pink-100">
    <h3 class="font-semibold text-gray-800 text-lg mb-4 flex items-center gap-2">
        <span>✨</span>
        Informasi Dasar
    </h3>
    <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1">
            <span class="text-sm text-gray-600 flex items-center gap-2">
                <span class="text-lg">🏷️</span>
                Jenis Item
            </span>
            <p class="font-medium text-gray-800 flex items-center gap-2">
                <span>{{ selectedItem.item_type_icon }}</span>
                {{ formatItemType(selectedItem.item_type) }}
            </p>
        </div>
        <div class="space-y-1">
            <span class="text-sm text-gray-600 flex items-center gap-2">
                <span class="text-lg">🟢</span>
                Status
            </span>
            <p class="font-medium text-gray-800 flex items-center gap-2">
                <span>{{ selectedItem.status_icon }}</span>
                {{ formatStatus(selectedItem.status) }}
            </p>
        </div>
        <div class="space-y-1">
            <span class="text-sm text-gray-600 flex items-center gap-2">
                <span class="text-lg">💰</span>
                Rencana Biaya
            </span>
            <p class="font-bold text-blue-700 text-lg">{{ formatCurrency(selectedItem.planned_amount) }}</p>
            <p v-if="isItemGoodsOrMaterial(selectedItem)" class="text-xs text-gray-500">
                Terhitung otomatis
            </p>
        </div>
        <div class="space-y-1">
            <span class="text-sm text-gray-600 flex items-center gap-2">
                <span class="text-lg">💸</span>
                Realisasi
            </span>
            <p class="font-bold text-orange-700 text-lg">{{ formatCurrency(selectedItem.actual_spent) }}</p>
        </div>
    </div>
</div>

                        <!-- Quantity dan Harga Satuan untuk Goods dan Material -->
<!-- Quantity dan Harga Satuan untuk Goods dan Material -->
<div v-if="isItemGoodsOrMaterial(selectedItem) && selectedItem.details" 
     class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-6 border border-indigo-100">
    <h3 class="font-semibold text-gray-800 text-lg mb-4 flex items-center gap-2">
        <span>📊</span>
        Informasi Kuantitas & Harga
    </h3>
    <div class="grid grid-cols-2 gap-4">
        <!-- Quantity -->
        <div class="space-y-1">
            <span class="text-sm text-gray-600 flex items-center gap-2">
                <span class="text-lg">📦</span>
                Quantity
            </span>
            <p class="font-bold text-gray-800 text-lg">
                {{ selectedItem.details.quantity || 0 }}
                <span class="text-sm font-normal text-gray-500">unit</span>
            </p>
        </div>

        <!-- Harga Satuan -->
        <div class="space-y-1">
            <span class="text-sm text-gray-600 flex items-center gap-2">
                <span class="text-lg">💰</span>
                Harga Satuan
            </span>
            <p class="font-bold text-green-600 text-lg">
                {{ formatCurrency(selectedItem.details.unit_price || 0) }}
            </p>
        </div>

        <!-- Perhitungan Total -->
        <div class="col-span-2 space-y-1">
            <span class="text-sm text-gray-600 flex items-center gap-2">
                <span class="text-lg">🧮</span>
                Perhitungan Total
            </span>
            <div class="bg-white/70 rounded-xl p-3 border border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">
                        {{ selectedItem.details.quantity || 0 }} × {{ formatCurrency(selectedItem.details.unit_price || 0) }}
                    </span>
                    <span class="font-bold text-blue-600 text-lg">
                        = {{ formatCurrency(selectedItem.planned_amount) }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Total rencana biaya dihitung otomatis dari quantity × harga satuan
                </p>
            </div>
        </div>
    </div>
</div>

                        <!-- Progress Bar yang lebih cantik -->
                        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-2xl p-6 border border-blue-100">
                            <h3 class="font-semibold text-gray-800 text-lg mb-4 flex items-center gap-2">
                                <span>📈</span>
                                Progress Keuangan
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-gray-700">{{ selectedItem.progress_percentage.toFixed(1) }}%</span>
                                    <span class="text-gray-600">{{ formatCurrency(selectedItem.actual_spent) }} / {{ formatCurrency(selectedItem.planned_amount) }}</span>
                                </div>
                                <div class="w-full bg-white rounded-full h-4 shadow-inner border">
                                    <div 
                                        class="h-4 rounded-full transition-all duration-1000 bg-gradient-to-r from-pink-400 to-rose-500 shadow-lg"
                                        :style="{ width: Math.min(selectedItem.progress_percentage, 100) + '%' }"
                                    ></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>Sisa: {{ formatCurrency(selectedItem.remaining_amount) }}</span>
                                    <span v-if="selectedItem.remaining_amount < 0" class="text-red-500 font-semibold">
                                        ⚠️ Melebihi budget
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="selectedItem.description" class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-100">
                            <h3 class="font-semibold text-gray-800 text-lg mb-3 flex items-center gap-2">
                                <span>📄</span>
                                Deskripsi
                            </h3>
                            <p class="text-gray-700 leading-relaxed bg-white/50 p-4 rounded-xl">
                                {{ selectedItem.description }}
                            </p>
                        </div>

                        <!-- Purchase Info untuk Barang -->
                        <div v-if="selectedItem.item_type === 'goods' && selectedItem.details && hasPurchaseDetails(selectedItem)" 
                            class="bg-gradient-to-r from-purple-50 to-violet-50 rounded-2xl p-6 border border-purple-100">
                            <h3 class="font-semibold text-gray-800 text-lg mb-4 flex items-center gap-2">
                                <span>🛍️</span>
                                Informasi Pembelian
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Purchase Type -->
                                <div v-if="selectedItem.details.purchase_type" class="flex items-center justify-between p-3 bg-white rounded-xl border">
                                    <span class="text-sm text-gray-600">Cara Pembelian:</span>
                                    <span class="font-medium text-gray-800 flex items-center gap-2">
                                        <span v-if="selectedItem.details.purchase_type === 'online'">🛒</span>
                                        <span v-else>🏪</span>
                                        {{ selectedItem.details.purchase_type === 'online' ? 'Beli Online' : 'Beli di Toko' }}
                                    </span>
                                </div>

                                <!-- Online Purchase Details -->
                                <div v-if="selectedItem.details.purchase_type === 'online'" class="space-y-3">
                                    <div v-if="selectedItem.details.ecommerce_platform" class="flex items-center justify-between p-3 bg-white rounded-xl border">
                                        <span class="text-sm text-gray-600">Platform:</span>
                                        <span class="font-medium flex items-center gap-2" :class="ecommercePlatforms[selectedItem.details.ecommerce_platform]?.color">
                                            <span>{{ ecommercePlatforms[selectedItem.details.ecommerce_platform]?.icon }}</span>
                                            {{ ecommercePlatforms[selectedItem.details.ecommerce_platform]?.name }}
                                        </span>
                                    </div>
                                    
                                    <div v-if="selectedItem.details.online_link" class="p-3 bg-white rounded-xl border">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-gray-600">Link Produk:</span>
                                            <BaseButton
                                                @click="openLink(selectedItem.details.online_link)"
                                                variant="primary"
                                                size="xs"
                                                class="!px-3 !py-1"
                                            >
                                                <template #icon>🔗</template>
                                                Buka Link
                                            </BaseButton>
                                        </div>
                                        <p class="text-xs text-gray-500 break-all">{{ selectedItem.details.online_link }}</p>
                                    </div>
                                </div>

                                <!-- Store Purchase Details -->
                                <div v-if="selectedItem.details.purchase_type === 'store'" class="space-y-3">
                                    <div v-if="selectedItem.details.store_maps" class="p-3 bg-white rounded-xl border">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-gray-600">Lokasi Toko:</span>
                                            <BaseButton
                                                @click="openLink(selectedItem.details.store_maps)"
                                                variant="primary"
                                                size="xs"
                                                class="!px-3 !py-1"
                                            >
                                                <template #icon>🗺️</template>
                                                Buka Maps
                                            </BaseButton>
                                        </div>
                                        <p class="text-xs text-gray-500 break-all">{{ selectedItem.details.store_maps }}</p>
                                    </div>
                                    
                                    <div v-if="selectedItem.details.store_address" class="p-3 bg-white rounded-xl border">
                                        <span class="text-sm text-gray-600 block mb-2">Alamat Toko:</span>
                                        <p class="text-sm text-gray-700">{{ selectedItem.details.store_address }}</p>
                                    </div>
                                </div>

                                <!-- Common Goods Details (tidak termasuk quantity dan unit_price) -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div v-for="field in ['ukuran', 'warna', 'material', 'merk']" :key="field">
                                        <div v-if="selectedItem.details[field]" class="p-3 bg-white rounded-xl border text-center">
                                            <span class="text-xs text-gray-500 block capitalize">{{ field }}</span>
                                            <span class="font-medium text-gray-800">{{ selectedItem.details[field] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Details -->
                        <div v-if="selectedItem.details && Object.keys(selectedItem.details).length > 0 && selectedItem.item_type !== 'goods'" class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-2xl p-6 border border-orange-100">
                            <h3 class="font-semibold text-gray-800 text-lg mb-4 flex items-center gap-2">
                                <span>🔧</span>
                                Detail Tambahan
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div 
                                    v-for="(value, key) in selectedItem.details" 
                                    :key="key" 
                                    class="p-3 bg-white rounded-xl border hover:shadow-sm transition-shadow"
                                >
                                    <span class="text-xs text-gray-500 block capitalize mb-1">{{ key.replace('_', ' ') }}</span>
                                    <span class="font-medium text-gray-800 text-sm">{{ value }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Timestamps -->
                        <div class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-2xl p-6 border border-gray-100">
                            <h3 class="font-semibold text-gray-800 text-lg mb-4 flex items-center gap-2">
                                <span>⏰</span>
                                Informasi Waktu
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-3 bg-white rounded-xl border">
                                    <span class="text-xs text-gray-500 block">Dibuat</span>
                                    <span class="font-medium text-gray-800">{{ formatDate(selectedItem.created_at) }}</span>
                                </div>
                                <div class="text-center p-3 bg-white rounded-xl border">
                                    <span class="text-xs text-gray-500 block">Diupdate</span>
                                    <span class="font-medium text-gray-800">{{ formatDate(selectedItem.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <BaseButton
                            @click="$inertia.visit(route('projects.items.show', { project: project.id, item: selectedItem.id }))"
                            variant="primary"
                            size="sm"
                            class="!px-2 !py-2"
                            title="Lihat detail lengkap item"
                        >
                            <template #icon>👁️</template>
                            Lihat detail lengkap item
                        </BaseButton>
                    </div>
                </BaseModal>

                <!-- Delete Confirmation Modal -->
                <BaseModal
                    v-model:show="showDeleteModal"
                    title="Hapus Item Project"
                    :description="itemToDelete ? `Apakah Anda yakin ingin menghapus item ${itemToDelete.name}?` : 'Apakah Anda yakin ingin menghapus item ini?'"
                    icon="🗑️"
                    confirm-text="Hapus"
                    cancel-text="Batal"
                    confirm-variant="danger"
                    :confirm-loading="deleting"
                    @confirm="deleteItem"
                    @close="showDeleteModal = false"
                >
                    <div class="space-y-4">
                        <div class="p-4 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-2xl">
                            <p class="text-sm text-red-600 flex items-start gap-2">
                                <span class="text-lg mt-0.5">⚠️</span>
                                <span>
                                    <strong class="block">Tindakan ini tidak dapat dibatalkan!</strong>
                                    Semua data yang terkait dengan item ini akan hilang.
                                </span>
                            </p>
                        </div>

                        <div v-if="itemToDelete" class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-2xl">
                            <h4 class="font-semibold text-gray-800 mb-2">Detail Item:</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Nama:</span>
                                    <p class="font-medium text-gray-800">{{ itemToDelete.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Jenis:</span>
                                    <p class="font-medium text-gray-800">{{ formatItemType(itemToDelete.item_type) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Rencana Biaya:</span>
                                    <p class="font-bold text-blue-700">{{ formatCurrency(itemToDelete.planned_amount) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Realisasi:</span>
                                    <p class="font-bold text-orange-700">{{ formatCurrency(itemToDelete.actual_spent) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <p class="font-medium text-gray-800">{{ formatStatus(itemToDelete.status) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Progress:</span>
                                    <p class="font-medium text-gray-800">{{ itemToDelete.progress_percentage.toFixed(1) }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </BaseModal>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar untuk modal */
.max-h-\[70vh\]::-webkit-scrollbar {
    width: 6px;
}

.max-h-\[70vh\]::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.max-h-\[70vh\]::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f472b6, #60a5fa);
    border-radius: 10px;
}

.max-h-\[70vh\]::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ec4899, #3b82f6);
}
</style>