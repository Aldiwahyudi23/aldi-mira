<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import BaseTable from '@/Components/Base/BaseTable.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import TextAreaInput from '@/Components/Form/TextAreaInput.vue';
import SelectInput from '@/Components/Form/SelectInput.vue';
import AccountInput from '@/Components/Form/AccountInput.vue';
import DateInput from '@/Components/Form/DateInput.vue';
import BaseFilter from '@/Components/Base/BaseFilter.vue'

const page = usePage();
const user = page.props.auth.user;
const partnerName = user?.partner?.name ?? 'Pasangan';

// Format tampilan nama pasangan
const displayNames = `${user?.name ?? ''} 💕 ${partnerName}`;

// Data dan state
const transactions = ref([]);
const accounts = ref([]);
const categories = ref([]);
const loading = ref(false);
const deleting = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingTransaction = ref(null);
const transactionToDelete = ref(null);
const flashMessage = ref(null);

// Filter state
const filters = ref({})

// 🎯 COMPUTED UNTUK FILTERED DATA YANG DIGUNAKAN OLEH SEMUA KOMPONEN
const filteredTransactions = computed(() => {
    if (Object.keys(filters.value).length === 0) {
        return transactions.value
    }
    
    return transactions.value.filter(transaction => {
        // Date filter
        if (filters.value.start_date && filters.value.end_date) {
            const transactionDate = transaction.transaction_date.split('T')[0]
            if (transactionDate < filters.value.start_date || transactionDate > filters.value.end_date) {
                return false
            }
        }
        
        // Type filter
        if (filters.value.type && transaction.type !== filters.value.type) {
            return false
        }
        
        // Account filter
        if (filters.value.account_id && transaction.account_id != filters.value.account_id) {
            return false
        }
        
        // Category filter
        if (filters.value.category_id && transaction.category_id != filters.value.category_id) {
            return false
        }
        
        // User filter
        if (filters.value.user_id && transaction.user_id != filters.value.user_id) {
            return false
        }
        
        // Amount range filter
        if (filters.value.min_amount && transaction.amount < parseFloat(filters.value.min_amount)) {
            return false
        }
        
        if (filters.value.max_amount && transaction.amount > parseFloat(filters.value.max_amount)) {
            return false
        }
        
        return true
    })
})

// 🎯 COMPUTED UNTUK FILTERED STATS (DIGUNAKAN OLEH CARDS DAN TABLE)
const filteredIncomeTransactions = computed(() => 
    filteredTransactions.value.filter(t => t.type === 'income')
)

const filteredExpenseTransactions = computed(() => 
    filteredTransactions.value.filter(t => t.type === 'expense')
)

const filteredSavingsTransactions = computed(() => 
    filteredTransactions.value.filter(t => t.type === 'savings')
)

const filteredMyTransactions = computed(() => 
    filteredTransactions.value.filter(t => t.user_id === user.id)
)

const filteredTotalIncome = computed(() => 
    filteredIncomeTransactions.value.reduce((sum, t) => sum + parseFloat(t.amount), 0)
)

const filteredTotalExpense = computed(() => 
    filteredExpenseTransactions.value.reduce((sum, t) => sum + parseFloat(t.amount), 0)
)

const filteredTotalSavings = computed(() => 
    filteredSavingsTransactions.value.reduce((sum, t) => sum + parseFloat(t.amount), 0)
)

const filteredNetAmount = computed(() => 
    filteredTotalIncome.value - filteredTotalExpense.value
)

// 🎯 COMPUTED UNTUK MENENTUKAN DATA YANG DITAMPILKAN
const displayedTransactions = computed(() => filteredTransactions.value)
const displayedIncomeTransactions = computed(() => filteredIncomeTransactions.value)
const displayedExpenseTransactions = computed(() => filteredExpenseTransactions.value)
const displayedSavingsTransactions = computed(() => filteredSavingsTransactions.value)
const displayedMyTransactions = computed(() => filteredMyTransactions.value)
const displayedTotalIncome = computed(() => filteredTotalIncome.value)
const displayedTotalExpense = computed(() => filteredTotalExpense.value)
const displayedTotalSavings = computed(() => filteredTotalSavings.value)
const displayedNetAmount = computed(() => filteredNetAmount.value)

// 🎯 COMPUTED UNTUK FILTER STATUS
const hasActiveFilters = computed(() => Object.keys(filters.value).length > 0)
const filterSummary = computed(() => {
    if (!hasActiveFilters.value) return ''
    
    const summary = []
    
    if (filters.value.start_date && filters.value.end_date) {
        summary.push(`Periode: ${filters.value.start_date} s/d ${filters.value.end_date}`)
    }
    
    if (filters.value.type) {
        const typeLabel = {
            income: 'Pemasukan',
            expense: 'Pengeluaran', 
            savings: 'Tabungan'
        }[filters.value.type] || filters.value.type
        summary.push(`Jenis: ${typeLabel}`)
    }
    
    if (filters.value.account_id) {
        const account = accounts.value.find(acc => acc.id == filters.value.account_id)
        summary.push(`Akun: ${account?.name || 'Terpilih'}`)
    }
    
    if (filters.value.category_id) {
        const category = categories.value.find(cat => cat.id == filters.value.category_id)
        summary.push(`Kategori: ${category?.name || 'Terpilih'}`)
    }
    
    return summary.join(' • ')
})

// Filter handlers
const handleFiltersChange = (newFilters) => {
    filters.value = newFilters
}

const handleFiltersReset = () => {
    filters.value = {}
}

// Load accounts and categories for filter dropdowns
const loadFilterData = async () => {
    await Promise.all([loadAccounts(), loadCategories()])
}

// Watch for flash messages
watch(() => page.props.flash, (newFlash) => {
    if (newFlash.success || newFlash.error) {
        flashMessage.value = {
            message: newFlash.success || newFlash.error,
            type: newFlash.type || (newFlash.success ? 'success' : 'error')
        };
        
        // Auto hide flash message after 5 seconds
        setTimeout(() => {
            flashMessage.value = null;
        }, 5000);
    }
}, { immediate: true });

// Form
const form = useForm({
    type: 'expense',
    account_id: '',
    category_id: '',
    amount: 0,
    description: '',
    transaction_date: new Date().toISOString().split('T')[0],
});

// Transaction Types
const transactionTypes = [
    { value: 'income', label: '💰 Pemasukan' },
    { value: 'expense', label: '💸 Pengeluaran' },
    { value: 'savings', label: '🏦 Tabungan' }
];

// Columns untuk tabel
const columns = [
    {
        key: 'type',
        label: 'Jenis',
        type: 'badge',
        icon: '📊',
        sortable: true,
        badgeVariant: 'romantic'
    },
    {
        key: 'amount',
        label: 'Jumlah',
        icon: '💰',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'account.name',
        label: 'Akun',
        icon: '🏦',
        sortable: true
    },
    {
        key: 'category.name',
        label: 'Kategori',
        icon: '📁',
        sortable: true
    },
    {
        key: 'description',
        label: 'Keterangan',
        icon: '📝',
        class: 'max-w-xs'
    },
    {
        key: 'transaction_date',
        label: 'Tanggal',
        icon: '📅',
        sortable: true,
        type: 'date'
    },
    {
        key: 'user.name',
        label: 'Dicatat Oleh',
        icon: '👤',
        sortable: true
    }
];

// Computed properties untuk dynamic content
const infoBoxClass = computed(() => {
    const classes = {
        income: 'bg-green-50 border-green-200 text-green-800',
        expense: 'bg-red-50 border-red-200 text-red-800',
        savings: 'bg-blue-50 border-blue-200 text-blue-800'
    };
    return classes[form.type] || 'bg-gray-50 border-gray-200';
});

const typeIcon = computed(() => {
    const icons = {
        income: '💰',
        expense: '💸',
        savings: '🏦'
    };
    return icons[form.type] || '📊';
});

const infoTitle = computed(() => {
    const titles = {
        income: 'Pencatatan Pemasukan',
        expense: 'Pencatatan Pengeluaran', 
        savings: 'Pencatatan Tabungan'
    };
    return titles[form.type] || 'Pencatatan Transaksi';
});

const infoDescription = computed(() => {
    const descriptions = {
        income: 'Catat semua uang yang masuk ke akun kita. Contoh: Gaji, bonus, hadiah, dll.',
        expense: 'Catat semua pengeluaran untuk memantau kebiasaan belanja kita berdua.',
        savings: 'Pindahkan uang ke akun tabungan untuk tujuan masa depan kita.'
    };
    return descriptions[form.type] || 'Catat transaksi keuangan kita.';
});

// Load data
const loadTransactions = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('transactions.index'));
        transactions.value = response.data.transactions || response.data;
    } catch (error) {
        console.error('Error loading transactions:', error);
        flashMessage.value = {
            message: 'Gagal memuat data transaksi',
            type: 'error'
        };
    } finally {
        loading.value = false;
    }
};

// Load accounts
const loadAccounts = async () => {
    try {
        const response = await axios.get(route('transactions.api.accounts'));
        accounts.value = response.data;
        console.log('Accounts loaded:', accounts.value);
    } catch (error) {
        console.error('Error loading accounts:', error);
        // Fallback: load semua accounts milik user
        try {
            const fallbackResponse = await axios.get('/api/accounts');
            accounts.value = fallbackResponse.data;
        } catch (fallbackError) {
            console.error('Fallback also failed:', fallbackError);
            flashMessage.value = {
                message: 'Gagal memuat data akun',
                type: 'error'
            };
        }
    }
};

// Load categories - FIXED: Load semua kategori dulu
const loadCategories = async () => {
    try {
        const response = await axios.get(route('transactions.api.categories'));
        categories.value = response.data;
        console.log('All categories loaded:', categories.value);
    } catch (error) {
        console.error('Error loading categories:', error);
        // Fallback
        try {
            const fallbackResponse = await axios.get('/api/categories');
            categories.value = fallbackResponse.data;
        } catch (fallbackError) {
            console.error('Fallback also failed:', fallbackError);
            flashMessage.value = {
                message: 'Gagal memuat data kategori',
                type: 'error'
            };
        }
    }
};

// Filter categories berdasarkan type transaksi - FIXED
const filteredCategories = computed(() => {
    if (!categories.value.length) return [];
    
    const budgetType = form.type === 'savings' ? 'savings' : form.type;
    console.log('Filtering categories for budget type:', budgetType);
    console.log('All categories:', categories.value);
    
    const filtered = categories.value.filter(cat => cat.budget_type === budgetType);
    console.log('Filtered categories:', filtered);
    return filtered;
});

// Watch untuk reset category ketika type berubah
watch(() => form.type, (newType) => {
    // Reset category_id ketika type berubah
    form.category_id = '';
    console.log('Transaction type changed to:', newType);
});

// Actions
const openCreateModal = async () => {
    editingTransaction.value = null;
    form.reset();
    form.type = 'expense';
    form.transaction_date = new Date().toISOString().split('T')[0];
    showModal.value = true;
    
    // Load data untuk dropdown
    await Promise.all([loadAccounts(), loadCategories()]);
};

const openEditModal = async (transaction) => {
    // Cek apakah user adalah creator transaksi
    if (transaction.user_id !== user.id) {
        flashMessage.value = {
            message: 'Anda hanya dapat mengedit transaksi yang Anda buat',
            type: 'error'
        };
        return;
    }

    editingTransaction.value = transaction;
    form.type = transaction.type;
    form.account_id = transaction.account_id;
    form.category_id = transaction.category_id;
    form.amount = transaction.amount;
    form.description = transaction.description;
    form.transaction_date = transaction.transaction_date.split('T')[0];
    showModal.value = true;
    
    // Load data untuk dropdown - FIXED: Load semua kategori dulu
    await Promise.all([loadAccounts(), loadCategories()]);
    
    // Set timeout untuk memastikan categories sudah terload
    setTimeout(() => {
        console.log('Current category_id:', form.category_id);
        console.log('Available categories:', filteredCategories.value);
    }, 100);
};

const openDeleteModal = (transaction) => {
    // Cek apakah user adalah creator transaksi
    if (transaction.user_id !== user.id) {
        flashMessage.value = {
            message: 'Anda hanya dapat menghapus transaksi yang Anda buat',
            type: 'error'
        };
        return;
    }

    transactionToDelete.value = transaction;
    showDeleteModal.value = true;
};

const saveTransaction = () => {
    console.log('Saving transaction:', form);
    
    // Validasi required fields
    if (!form.description.trim()) {
        flashMessage.value = {
            message: 'Keterangan transaksi wajib diisi',
            type: 'error'
        };
        return;
    }
    
    if (editingTransaction.value) {
        form.put(route('transactions.update', editingTransaction.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadTransactions();
                form.reset();
                editingTransaction.value = null;
            },
            onError: (errors) => {
                console.error('Update error:', errors);
                flashMessage.value = {
                    message: 'Gagal memperbarui transaksi',
                    type: 'error'
                };
            }
        });
    } else {
        form.post(route('transactions.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadTransactions();
                form.reset();
            },
            onError: (errors) => {
                console.error('Create error:', errors);
                flashMessage.value = {
                    message: 'Gagal mencatat transaksi',
                    type: 'error'
                };
            }
        });
    }
};

const deleteTransaction = () => {
    if (!transactionToDelete.value) return;

    deleting.value = true;

    router.delete(route('transactions.destroy', transactionToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            loadTransactions();
            transactionToDelete.value = null;
            deleting.value = false;
        },
        onError: (errors) => {
            console.error('Delete error:', errors);
            showDeleteModal.value = false;
            deleting.value = false;
            flashMessage.value = {
                message: 'Gagal menghapus transaksi',
                type: 'error'
            };
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    editingTransaction.value = null;
    form.clearErrors();
    form.reset();
    form.type = 'expense';
    form.transaction_date = new Date().toISOString().split('T')[0];
};

// Format functions
const formatTypeBadge = (type) => {
    const typeMap = {
        income: 'Pemasukan',
        expense: 'Pengeluaran',
        savings: 'Tabungan'
    };
    return typeMap[type] || type;
};

const formatTypeIcon = (type) => {
    const iconMap = {
        income: '💰',
        expense: '💸',
        savings: '🏦'
    };
    return iconMap[type] || '📊';
};

const formatCurrency = (amount, type) => {
    const formatted = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);

    if (type === 'income') return `+ ${formatted}`;
    if (type === 'expense') return `- ${formatted}`;
    return formatted;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const getAmountColor = (type) => {
    return {
        income: 'text-green-600',
        expense: 'text-red-600',
        savings: 'text-blue-600'
    }[type] || 'text-gray-600';
};

// Cek apakah user bisa edit/hapus transaksi
const canEditTransaction = (transaction) => transaction.user_id === user.id;

onMounted(() => {
    loadTransactions()
    loadFilterData()
})
</script>

<template>
    <AppLayout title="Catat Transaksi">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                        💰 Catat Transaksi Keuangan {{ displayNames }}
                    </h2>
                    <!-- 🎯 FILTER STATUS -->
                    <div v-if="filterSummary" class="mt-2">
                        <span class="text-sm text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
                            🔍 Filter Aktif: {{ filterSummary }}
                        </span>
                    </div>
                </div>
                <span class="text-sm text-gray-500 italic">Catat setiap pemasukan dan pengeluaran untuk keuangan yang lebih teratur 💞</span>
            </div>
        </template>

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
                        Catat Transaksi Keuangan
                    </h1>
                    <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                        Pantau semua 
                        <span class="text-green-500 font-semibold">pemasukan</span>, 
                        <span class="text-red-500 font-semibold">pengeluaran</span>, dan 
                        <span class="text-blue-500 font-semibold">tabungan</span> 
                        kita berdua. Setiap transaksi adalah cerita perjalanan keuangan kita 💫
                    </p>
                </div>

                <!-- Filter Component -->
                <BaseFilter
                    :accounts="accounts"
                    :categories="categories"
                    :initial-filters="filters"
                    @filters-change="handleFiltersChange"
                    @filters-reset="handleFiltersReset"
                />

                <!-- 🎯 STATS CARDS DENGAN FILTERED DATA -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                    <!-- Total Income -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-green-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">💰</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(displayedTotalIncome, 'income') }}
                            </h3>
                            <p class="text-sm text-gray-600">Pemasukan</p>
                            <p class="text-xs text-gray-500 mt-1">{{ displayedIncomeTransactions.length }} transaksi</p>
                        </div>
                    </BaseCard>

                    <!-- Total Expense -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-red-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-red-400 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">💸</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(displayedTotalExpense, 'expense') }}
                            </h3>
                            <p class="text-sm text-gray-600">Pengeluaran</p>
                            <p class="text-xs text-gray-500 mt-1">{{ displayedExpenseTransactions.length }} transaksi</p>
                        </div>
                    </BaseCard>

                    <!-- Net Amount -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-blue-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">📊</span>
                            </div>
                            <h3 class="text-xl font-bold mb-1" :class="displayedNetAmount >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ formatCurrency(Math.abs(displayedNetAmount), displayedNetAmount >= 0 ? 'income' : 'expense') }}
                            </h3>
                            <p class="text-sm text-gray-600">Saldo Bersih</p>
                            <p class="text-xs" :class="displayedNetAmount >= 0 ? 'text-green-500' : 'text-red-500'">
                                {{ displayedNetAmount >= 0 ? 'Surplus' : 'Defisit' }}
                            </p>
                        </div>
                    </BaseCard>

                    <!-- My Transactions -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-purple-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-400 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">👤</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ displayedMyTransactions.length }}
                            </h3>
                            <p class="text-sm text-gray-600">Transaksi Saya</p>
                        </div>
                    </BaseCard>
                </div>

                <div class="grid grid-cols-3 lg:grid-cols-4 gap-2 mb-4">
                    <!-- Catat Kategori Baru -->
                    <Link 
                        :href="route('master-data.categories.index')"
                        class="flex items-center justify-center gap-2 px-4 py-2 rounded-xl
                            bg-gradient-to-r from-rose-400 via-pink-400 to-pink-500
                            text-white font-semibold shadow-md hover:shadow-lg 
                            hover:from-rose-500 hover:to-pink-600
                            transition-all duration-300 ease-in-out transform hover:-translate-y-0.5 active:scale-95"
                    >
                        <span class="text-lg">💗</span>
                        <span>Kategori</span>
                    </Link>

                    <!-- Akun -->
                    <Link 
                        :href="route('master-data.accounts.index')"
                        class="flex items-center justify-center gap-2 px-4 py-2 rounded-xl
                            bg-gradient-to-r from-pink-300 via-rose-400 to-rose-500
                            text-white font-semibold shadow-md hover:shadow-lg 
                            hover:from-pink-400 hover:to-rose-600
                            transition-all duration-300 ease-in-out transform hover:-translate-y-0.5 active:scale-95"
                    >
                        <span class="text-lg">🌸</span>
                        <span>Akun</span>
                    </Link>

                    <!-- Kelola Anggaran -->
                    <Link 
                        :href="route('budgets.index')"
                        class="flex items-center justify-center gap-2 px-4 py-2 rounded-xl
                            bg-gradient-to-r from-pink-200 via-rose-300 to-rose-400
                            text-white font-semibold shadow-md hover:shadow-lg 
                            hover:from-pink-300 hover:to-rose-500
                            transition-all duration-300 ease-in-out transform hover:-translate-y-0.5 active:scale-95 animate-pulse"
                    >
                        <span class="text-lg">💰</span>
                        <span>Kelola Anggaran</span>
                    </Link>
                </div>

                <!-- Main Table -->
                <BaseTable
                    title="Data Transaksi"
                    :description="`Menampilkan ${displayedTransactions.length} dari ${transactions.length} transaksi`"
                    :columns="columns"
                    :data="displayedTransactions"
                    :loading="loading"
                    :show-actions="true"
                    :pagination="true"
                    :items-per-page="10"
                    @create="openCreateModal"
                    @edit="openEditModal"
                    @delete="openDeleteModal"
                >
                    <!-- Custom column for type with icon -->
                    <template #column-type="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ formatTypeIcon(item.type) }}</span>
                            <span class="font-medium text-gray-700 text-sm">{{ formatTypeBadge(item.type) }}</span>
                        </div>
                    </template>

                    <!-- Custom column for amount -->
                    <template #column-amount="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ formatTypeIcon(item.type) }}</span>
                            <span class="font-bold text-sm" :class="getAmountColor(item.type)">
                                {{ formatCurrency(item.amount, item.type) }}
                            </span>
                        </div>
                    </template>

                    <!-- Custom column for account -->
                    <template #column-account.name="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🏦</span>
                            <span class="font-medium text-gray-700 text-sm">{{ item.account?.name }}</span>
                        </div>
                    </template>

                    <!-- Custom column for category -->
                    <template #column-category.name="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📁</span>
                            <span class="font-medium text-gray-700 text-sm">{{ item.category?.name }}</span>
                        </div>
                    </template>

                    <!-- Custom column for date -->
                    <template #column-transaction_date="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📅</span>
                            <span class="font-medium text-gray-700 text-sm">{{ formatDate(item.transaction_date) }}</span>
                        </div>
                    </template>

                    <!-- Custom column for created by -->
                    <template #column-user.name="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ item.user_id === user.id ? '👤' : '👥' }}</span>
                            <span class="font-medium text-gray-700 text-sm" :class="item.user_id === user.id ? 'text-blue-600' : 'text-green-600'">
                                {{ item.user_id === user.id ? 'Saya' : (item.user?.name || 'Partner') }}
                            </span>
                        </div>
                    </template>

                    <!-- Custom actions slot -->
                    <template #actions="{ item }">
                        <BaseButton
                            @click="openEditModal(item)"
                            variant="secondary"
                            size="sm"
                            class="!px-2 !py-2"
                            :disabled="!canEditTransaction(item)"
                            :title="!canEditTransaction(item) ? 'Hanya dapat mengedit transaksi yang Anda buat' : 'Edit transaksi'"
                        >
                            <template #icon>✏️</template>
                            Edit
                        </BaseButton>
                        <BaseButton
                            @click="openDeleteModal(item)"
                            variant="danger"
                            size="sm"
                            class="!px-2 !py-2"
                            :disabled="!canEditTransaction(item)"
                            :title="!canEditTransaction(item) ? 'Hanya dapat menghapus transaksi yang Anda buat' : 'Hapus transaksi'"
                        >
                            <template #icon>🗑️</template>
                            Hapus
                        </BaseButton>
                    </template>
                </BaseTable>

                <!-- Empty State CTA -->
                <div v-if="transactions.length === 0 && !loading" class="text-center mt-6">
                    <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-lg p-8 border border-gray-100">
                        <div class="text-6xl mb-4">💰</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada transaksi</h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            Mulai dengan mencatat transaksi pertama untuk melacak keuangan {{ displayNames }}
                        </p>
                        <BaseButton
                            @click="openCreateModal"
                            class="px-6 py-3"
                        >
                            <template #icon>➕</template>
                            Catat Transaksi Pertama
                        </BaseButton>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <BaseModal
                    v-model:show="showModal"
                    :title="editingTransaction ? 'Edit Transaksi' : 'Catat Transaksi Baru'"
                    :description="editingTransaction ? 
                        'Perbarui informasi transaksi keuangan kita' : 
                        'Catat transaksi baru untuk melacak keuangan bersama'"
                    :icon="editingTransaction ? '✏️' : '➕'"
                    :confirm-text="editingTransaction ? 'Perbarui' : 'Simpan'"
                    :confirm-loading="form.processing"
                    @confirm="saveTransaction"
                    @close="closeModal"
                    size="lg"
                >
                    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                        <!-- Type Selector -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="text-blue-400">📊</span>
                                Jenis Transaksi
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="type in transactionTypes"
                                    :key="type.value"
                                    @click="form.type = type.value"
                                    class="p-3 rounded-xl border-2 text-center transition-all font-medium text-sm"
                                    :class="form.type === type.value 
                                        ? (type.value === 'income' ? 'bg-green-100 text-green-700 border-green-400' : 
                                           type.value === 'expense' ? 'bg-red-100 text-red-700 border-red-400' : 
                                           'bg-blue-100 text-blue-700 border-blue-400') + ' scale-105 shadow-md' 
                                        : 'bg-white border-gray-200 text-gray-500 hover:scale-102 hover:border-gray-300'"
                                >
                                    <div class="text-lg mb-1">{{ type.icon }}</div>
                                    <div>{{ type.label.replace('💰 ', '').replace('💸 ', '').replace('🏦 ', '') }}</div>
                                </button>
                            </div>
                            <p v-if="form.errors.type" class="mt-2 text-sm text-red-500">
                                {{ form.errors.type }}
                            </p>
                        </div>

                        <!-- Account -->
                        <SelectInput
                            v-model="form.account_id"
                            :label="form.type === 'income' || form.type === 'savings' ? 'Akun Penerima' : 'Akun Sumber'"
                            :placeholder="form.type === 'income' || form.type === 'savings' ? 'Pilih akun penerima' : 'Pilih akun sumber'"
                            :options="accounts.map(acc => ({ value: acc.id, label: acc.name + (acc.type === 'joint' ? ' 👥' : ' 👤') }))"
                            :error="form.errors.account_id"
                            icon="🏦"
                            required
                            :disabled="form.processing || accounts.length === 0"
                        />

                        <!-- Category - FIXED: Load semua kategori dulu, baru filter -->
                        <SelectInput
                            v-model="form.category_id"
                            :label="form.type === 'income' ? 'Kategori Pemasukan' : form.type === 'savings' ? 'Tujuan Tabungan' : 'Kategori Pengeluaran'"
                            :placeholder="form.type === 'income' ? 'Pilih kategori pemasukan' : form.type === 'savings' ? 'Pilih tujuan tabungan' : 'Pilih kategori pengeluaran'"
                            :options="filteredCategories.map(cat => ({ value: cat.id, label: cat.name + (cat.type === 'joint' ? ' 👥' : ' 👤') }))"
                            :error="form.errors.category_id"
                            icon="📁"
                            required
                            :disabled="form.processing || filteredCategories.length === 0"
                        />

                        <!-- Amount -->
                        <AccountInput
                            v-model="form.amount"
                            label="Jumlah"
                            :placeholder="form.type === 'income' ? 'Contoh: 5000000' : form.type === 'savings' ? 'Contoh: 1000000' : 'Contoh: 150000'"
                            :error="form.errors.amount"
                            icon="💰"
                            account-type="account_number"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Description - FIXED: Added required field -->
                        <TextAreaInput
                            v-model="form.description"
                            label="Keterangan"
                            :placeholder="form.type === 'income' ? 'Contoh: Gaji bulan Januari, Bonus project...' : form.type === 'savings' ? 'Contoh: Tabungan rumah, Dana liburan...' : 'Contoh: Belanja bulanan, Bayar listrik...'"
                            :error="form.errors.description"
                            icon="📝"
                            :rows="3"
                            :show-counter="true"
                            :max-length="255"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Transaction Date -->
                        <DateInput
                            v-model="form.transaction_date"
                            label="Tanggal Transaksi"
                            :error="form.errors.transaction_date"
                            icon="📅"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Info Box -->
                        <div class="p-4 rounded-2xl border" :class="infoBoxClass">
                            <p class="text-sm flex items-start gap-2">
                                <span class="text-lg mt-0.5">{{ typeIcon }}</span>
                                <span>
                                    <strong class="block">{{ infoTitle }}</strong>
                                    {{ infoDescription }}
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

                <!-- Delete Confirmation Modal -->
                <BaseModal
                    v-model:show="showDeleteModal"
                    title="Hapus Transaksi"
                    :description="transactionToDelete ? `Apakah Anda yakin ingin menghapus transaksi ${transactionToDelete.description}?` : 'Apakah Anda yakin ingin menghapus transaksi ini?'"
                    icon="🗑️"
                    confirm-text="Hapus"
                    cancel-text="Batal"
                    confirm-variant="danger"
                    :confirm-loading="deleting"
                    @confirm="deleteTransaction"
                    @close="showDeleteModal = false"
                >
                    <div class="space-y-4">
                        <div class="p-4 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-2xl">
                            <p class="text-sm text-red-600 flex items-start gap-2">
                                <span class="text-lg mt-0.5">⚠️</span>
                                <span>
                                    <strong class="block">Tindakan ini tidak dapat dibatalkan!</strong>
                                    Transaksi yang dihapus akan mempengaruhi saldo akun dan laporan keuangan.
                                </span>
                            </p>
                        </div>

                        <div v-if="transactionToDelete" class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-2xl">
                            <h4 class="font-semibold text-gray-800 mb-2">Detail Transaksi:</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Jenis:</span>
                                    <p class="font-medium text-gray-800">{{ formatTypeBadge(transactionToDelete.type) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Jumlah:</span>
                                    <p class="font-bold" :class="getAmountColor(transactionToDelete.type)">
                                        {{ formatCurrency(transactionToDelete.amount, transactionToDelete.type) }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Akun:</span>
                                    <p class="font-medium text-gray-800">{{ transactionToDelete.account?.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Kategori:</span>
                                    <p class="font-medium text-gray-800">{{ transactionToDelete.category?.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Tanggal:</span>
                                    <p class="font-medium text-gray-800">{{ formatDate(transactionToDelete.transaction_date) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Dicatat Oleh:</span>
                                    <p class="font-medium text-gray-800">{{ transactionToDelete.user_id === user.id ? 'Saya' : (transactionToDelete.user?.name || 'Partner') }}</p>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-600">Keterangan:</span>
                                    <p class="font-medium text-gray-800">{{ transactionToDelete.description }}</p>
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
.max-h-\[60vh\]::-webkit-scrollbar {
    width: 6px;
}

.max-h-\[60vh\]::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.max-h-\[60vh\]::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f472b6, #60a5fa);
    border-radius: 10px;
}

.max-h-\[60vh\]::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ec4899, #3b82f6);
}
</style>