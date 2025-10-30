<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed, onMounted, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import BaseTable from '@/Components/Base/BaseTable.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import SelectInput from '@/Components/Form/SelectInput.vue';
import AccountInput from '@/Components/Form/AccountInput.vue';
import DateInput from '@/Components/Form/DateInput.vue';

const page = usePage();
const user = page.props.auth.user;
const partnerName = user?.partner?.name ?? 'Pasangan';

// Format tampilan nama pasangan
const displayNames = `${user?.name ?? ''} 💕 ${partnerName}`;

// Data dan state
const budgets = ref([]);
const categories = ref([]);
const loading = ref(false);
const deleting = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingBudget = ref(null);
const budgetToDelete = ref(null);
const flashMessage = ref(null);

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
    category_id: '',
    period_start: '',
    period_end: '',
    target_amount: 0,
});

// Columns untuk tabel
const columns = [
    {
        key: 'category.name',
        label: 'Kategori',
        icon: '📁',
        sortable: true
    },
    {
        key: 'category.budget_type',
        label: 'Jenis',
        icon: '💰',
        sortable: true,
        type: 'badge'
    },
    {
        key: 'period',
        label: 'Periode',
        icon: '📅',
        sortable: true
    },
    {
        key: 'target_amount',
        label: 'Target',
        icon: '🎯',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'spent_amount',
        label: 'Terpakai',
        icon: '💰',
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
        key: 'usage_percentage',
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
    }
];

// Load data
const loadBudgets = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('budgets.index'));
        budgets.value = response.data.budgets || response.data;
        console.log('Budgets loaded:', budgets.value); // Debug
    } catch (error) {
        console.error('Error loading budgets:', error);
        flashMessage.value = {
            message: 'Gagal memuat data budget',
            type: 'error'
        };
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const response = await axios.get(route('budgets.api.categories'));
        categories.value = response.data;
        console.log('Categories loaded:', categories.value); // Debug
    } catch (error) {
        console.error('Error loading categories:', error);
        flashMessage.value = {
            message: 'Gagal memuat data kategori',
            type: 'error'
        };
    }
};

// Set default period (bulan ini)
const setDefaultPeriod = () => {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    form.period_start = firstDay.toISOString().split('T')[0];
    form.period_end = lastDay.toISOString().split('T')[0];
};

// Actions
const openCreateModal = async () => {
    editingBudget.value = null;
    form.reset();
    setDefaultPeriod();
    showModal.value = true;
    
    await loadCategories();
};

const openEditModal = async (budget) => {
    editingBudget.value = budget;
    form.category_id = budget.category_id;
    form.period_start = budget.period_start;
    form.period_end = budget.period_end;
    form.target_amount = budget.target_amount;
    showModal.value = true;
    
    await loadCategories();
};

const openDeleteModal = (budget) => {
    budgetToDelete.value = budget;
    showDeleteModal.value = true;
};

const saveBudget = () => {
    if (editingBudget.value) {
        form.put(route('budgets.update', editingBudget.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadBudgets();
                form.reset();
                editingBudget.value = null;
            },
            onError: (errors) => {
                console.error('Update error:', errors);
            }
        });
    } else {
        form.post(route('budgets.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadBudgets();
                form.reset();
                setDefaultPeriod();
            },
            onError: (errors) => {
                console.error('Create error:', errors);
            }
        });
    }
};

const deleteBudget = () => {
    if (!budgetToDelete.value) return;

    deleting.value = true;

    router.delete(route('budgets.destroy', budgetToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            loadBudgets();
            budgetToDelete.value = null;
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
    editingBudget.value = null;
    form.clearErrors();
    form.reset();
    setDefaultPeriod();
};

const createNextMonthBudget = async (budget) => {
    try {
        await axios.post(route('budgets.next-month', budget.id));
        loadBudgets();
        flashMessage.value = {
            message: 'Budget untuk bulan depan berhasil dibuat!',
            type: 'success'
        };
    } catch (error) {
        console.error('Error creating next month budget:', error);
        flashMessage.value = {
            message: 'Gagal membuat budget bulan depan',
            type: 'error'
        };
    }
};

const updateSpentAmount = async (budget) => {
    try {
        await axios.post(route('budgets.update-spent', budget.id));
        loadBudgets();
        flashMessage.value = {
            message: 'Data pengeluaran berhasil diupdate!',
            type: 'success'
        };
    } catch (error) {
        console.error('Error updating spent amount:', error);
    }
};

// Format functions
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
};

const formatPeriod = (start, end) => {
    const startDate = new Date(start).toLocaleDateString('id-ID', {
        month: 'short',
        year: 'numeric'
    });
    const endDate = new Date(end).toLocaleDateString('id-ID', {
        month: 'short', 
        year: 'numeric'
    });
    return `${startDate} - ${endDate}`;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const formatBudgetType = (type) => {
    const typeMap = {
        expense: 'Pengeluaran',
        savings: 'Tabungan',
        income: 'Pemasukan'
    };
    return typeMap[type] || type;
};

const formatBudgetTypeIcon = (type) => {
    const iconMap = {
        expense: '💸',
        savings: '🏦',
        income: '💰'
    };
    return iconMap[type] || '📊';
};

// Computed untuk stats
const activeBudgets = computed(() => budgets.value.filter(b => b.isActive));
const expiredBudgets = computed(() => budgets.value.filter(b => b.isExpired));
const upcomingBudgets = computed(() => budgets.value.filter(b => b.isUpcoming));

const totalTarget = computed(() => budgets.value.reduce((sum, b) => sum + parseFloat(b.target_amount || 0), 0));
const totalSpent = computed(() => budgets.value.reduce((sum, b) => sum + parseFloat(b.spent_amount || 0), 0));
const totalRemaining = computed(() => totalTarget.value - totalSpent.value);
const averageUsage = computed(() => totalTarget.value > 0 ? (totalSpent.value / totalTarget.value) * 100 : 0);

const overBudgetCount = computed(() => budgets.value.filter(b => b.isOverBudget).length);
const warningCount = computed(() => budgets.value.filter(b => 
    (b.usage_percentage || 0) >= 80 && (b.usage_percentage || 0) < 100
).length);
const safeCount = computed(() => budgets.value.filter(b => 
    (b.usage_percentage || 0) < 80
).length);

// Helper functions tambahan
const getDaysRemaining = (budget) => {
    if (!budget.period_end) return 0;
    const endDate = new Date(budget.period_end);
    const today = new Date();
    const diffTime = endDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

const getBudgetStatusIcon = (budget) => {
    const percentage = getUsagePercentage(budget);
    if (percentage >= 100) return '💸';
    if (percentage >= 80) return '⚠️';
    if (percentage >= 50) return '🔶';
    return '✅';
};

// Safe accessor yang lebih robust
const getUsagePercentage = (budget) => {
    const spent = parseFloat(budget.spent_amount || 0);
    const target = parseFloat(budget.target_amount || 0);
    if (!target || target === 0) return 0;
    return (spent / target) * 100;
};

const getRemainingAmount = (budget) => {
    const spent = parseFloat(budget.spent_amount || 0);
    const target = parseFloat(budget.target_amount || 0);
    return target - spent;
};

const getSpentAmount = (budget) => parseFloat(budget.spent_amount || 0);
const getTargetAmount = (budget) => parseFloat(budget.target_amount || 0);
const getIsOverBudget = (budget) => getSpentAmount(budget) > getTargetAmount(budget);
const getIsActive = (budget) => {
    if (!budget.period_start || !budget.period_end) return false;
    const today = new Date().toISOString().split('T')[0];
    return today >= budget.period_start && today <= budget.period_end;
};
const getIsExpired = (budget) => {
    if (!budget.period_end) return false;
    const today = new Date().toISOString().split('T')[0];
    return today > budget.period_end;
};

const getBudgetStatusText = (budget) => {
    const percentage = getUsagePercentage(budget);
    if (percentage >= 100) return 'Melebihi Budget';
    if (percentage >= 80) return 'Perhatian';
    if (percentage >= 50) return 'Moderat';
    return 'Aman';
};

onMounted(() => {
    loadBudgets();
    setDefaultPeriod();
});
</script>

<template>
    <AppLayout title="Kelola Budget">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    🎯 Kelola Budget Keuangan {{ displayNames }}
                </h2>
                <span class="text-sm text-gray-500 italic">Rencanakan pengeluaran dan tabungan untuk keuangan yang lebih terkontrol 💞</span>
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
                        Kelola Budget Keuangan
                    </h1>
                    <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                        Rencanakan dan pantau 
                        <span class="text-orange-500 font-semibold">target pengeluaran</span> dan
                        <span class="text-blue-500 font-semibold">tabungan</span> 
                        kita berdua. Budget yang baik adalah kunci keuangan yang sehat 💫
                    </p>
                </div>

                <!-- Stats Cards - Responsive grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                    <!-- Total Target -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-blue-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">🎯</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(totalTarget) }}
                            </h3>
                            <p class="text-sm text-gray-600">Total Target</p>
                        </div>
                    </BaseCard>

                    <!-- Total Terpakai -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-orange-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-orange-400 to-red-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">💰</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(totalSpent) }}
                            </h3>
                            <p class="text-sm text-gray-600">Total Terpakai</p>
                            <p class="text-xs text-gray-500 mt-1">{{ averageUsage.toFixed(1) }}%</p>
                        </div>
                    </BaseCard>

                    <!-- Sisa Budget -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-green-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">📊</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(totalRemaining) }}
                            </h3>
                            <p class="text-sm text-gray-600">Sisa Budget</p>
                        </div>
                    </BaseCard>

                    <!-- Status Budget -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-purple-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-400 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">📈</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ budgets.length }}
                            </h3>
                            <p class="text-sm text-gray-600">Total Budget</p>
                            <div class="flex justify-center gap-1 mt-1">
                                <span class="w-2 h-2 bg-green-500 rounded-full" :title="`${safeCount} Aman`"></span>
                                <span class="w-2 h-2 bg-yellow-500 rounded-full" :title="`${warningCount} Perhatian`"></span>
                                <span class="w-2 h-2 bg-red-500 rounded-full" :title="`${overBudgetCount} Melebihi`"></span>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Main Table -->
                <BaseTable
                    title="Data Budget"
                    description="Kelola semua budget pengeluaran dan tabungan kita berdua"
                    :columns="columns"
                    :data="budgets"
                    :loading="loading"
                    :show-actions="true"
                    :pagination="true"
                    :items-per-page="10"
                    @create="openCreateModal"
                    @edit="openEditModal"
                    @delete="openDeleteModal"
                >
<!-- Custom column for category -->
<template #column-category.name="{ item }">
    <div class="flex items-center gap-3 min-w-[200px]">
        <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-white text-lg shadow-md"
             :class="{
                 'bg-gradient-to-r from-blue-500 to-cyan-500': item.category?.type === 'joint',
                 'bg-gradient-to-r from-gray-500 to-gray-700': item.category?.type !== 'joint'
             }">
            {{ formatBudgetTypeIcon(item.category?.budget_type) }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-900 text-sm truncate">{{ item.category?.name }}</p>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-xs px-2 py-1 rounded-full font-medium"
                      :class="{
                          'bg-blue-100 text-blue-700': item.category?.type === 'joint',
                          'bg-gray-100 text-gray-700': item.category?.type !== 'joint'
                      }">
                    {{ item.category?.type === 'joint' ? '👥 Bersama' : '👤 Pribadi' }}
                </span>
                <span class="text-xs text-gray-500">•</span>
                <span class="text-xs text-gray-500">{{ item.category?.user?.name || 'Unknown' }}</span>
            </div>
        </div>
    </div>
</template>

<!-- Custom column for budget type -->
<template #column-category.budget_type="{ item }">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-lg"
             :class="{
                 'bg-green-100 text-green-600': item.category?.budget_type === 'savings',
                 'bg-orange-100 text-orange-600': item.category?.budget_type === 'expense',
                 'bg-blue-100 text-blue-600': item.category?.budget_type === 'income'
             }">
            {{ formatBudgetTypeIcon(item.category?.budget_type) }}
        </div>
        <span class="font-medium text-gray-700 text-sm">{{ formatBudgetType(item.category?.budget_type) }}</span>
    </div>
</template>

<!-- Custom column for period -->
<template #column-period="{ item }">
    <div class="space-y-1">
        <div class="flex items-center gap-2">
            <span class="text-lg">📅</span>
            <span class="font-semibold text-gray-900 text-sm">{{ formatPeriod(item.period_start, item.period_end) }}</span>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-500">
            <span>{{ formatDate(item.period_start) }}</span>
            <span>→</span>
            <span>{{ formatDate(item.period_end) }}</span>
        </div>
        <div v-if="getDaysRemaining(item) >= 0" class="flex items-center gap-1 text-xs"
             :class="{
                 'text-green-600': getDaysRemaining(item) > 7,
                 'text-orange-600': getDaysRemaining(item) <= 7 && getDaysRemaining(item) > 0,
                 'text-red-600': getDaysRemaining(item) <= 0
             }">
            <span>⏳</span>
            <span>{{ getDaysRemaining(item) }} hari lagi</span>
        </div>
        <div v-else class="flex items-center gap-1 text-xs text-red-600">
            <span>⌛</span>
            <span>Berakhir {{ Math.abs(getDaysRemaining(item)) }} hari lalu</span>
        </div>
    </div>
</template>

<!-- Custom column for target -->
<template #column-target_amount="{ item }">
    <div class="text-right">
        <div class="font-bold text-gray-900 text-sm">{{ formatCurrency(getTargetAmount(item)) }}</div>
        <div class="text-xs text-gray-500 mt-1">Target</div>
    </div>
</template>

<!-- Custom column for spent -->
<template #column-spent_amount="{ item }">
    <div class="text-right">
        <div class="font-bold text-sm flex items-center justify-end gap-1"
             :class="{
                 'text-red-600': getSpentAmount(item) > getTargetAmount(item),
                 'text-orange-600': getSpentAmount(item) <= getTargetAmount(item)
             }">
            {{ formatCurrency(getSpentAmount(item)) }}
            <span v-if="getSpentAmount(item) > getTargetAmount(item)" class="text-xs">⚠️</span>
        </div>
        <div class="text-xs text-gray-500 mt-1">Terpakai</div>
    </div>
</template>

<!-- Custom column for remaining -->
<template #column-remaining_amount="{ item }">
    <div class="text-right">
        <div class="font-bold text-sm flex items-center justify-end gap-1"
             :class="{
                 'text-red-600': getRemainingAmount(item) < 0,
                 'text-green-600': getRemainingAmount(item) >= 0
             }">
            {{ formatCurrency(getRemainingAmount(item)) }}
            <span v-if="getRemainingAmount(item) < 0" class="text-xs">💸</span>
            <span v-else-if="getRemainingAmount(item) > 0" class="text-xs">💪</span>
        </div>
        <div class="text-xs text-gray-500 mt-1">Sisa</div>
    </div>
</template>

<!-- Custom column for progress -->
<template #column-usage_percentage="{ item }">
    <div class="space-y-2 min-w-[120px]">
        <!-- Progress Bar -->
        <div class="flex justify-between items-center text-xs">
            <span class="font-semibold text-gray-700">{{ getUsagePercentage(item).toFixed(1) }}%</span>
            <span class="text-gray-500">{{ Math.min(getUsagePercentage(item), 100).toFixed(1) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5 shadow-inner">
            <div 
                class="h-2.5 rounded-full transition-all duration-500 shadow-md"
                :class="{
                    'bg-gradient-to-r from-red-500 to-red-600': getUsagePercentage(item) >= 100,
                    'bg-gradient-to-r from-orange-400 to-orange-500': getUsagePercentage(item) >= 80 && getUsagePercentage(item) < 100,
                    'bg-gradient-to-r from-yellow-400 to-yellow-500': getUsagePercentage(item) >= 50 && getUsagePercentage(item) < 80,
                    'bg-gradient-to-r from-green-500 to-emerald-500': getUsagePercentage(item) < 50
                }"
                :style="{ width: Math.min(getUsagePercentage(item), 100) + '%' }"
            ></div>
        </div>
        
        <!-- Progress Text -->
        <div class="flex justify-between items-center text-xs text-gray-600">
            <span>Terkumpul</span>
            <span>{{ formatCurrency(getSpentAmount(item)) }}</span>
        </div>
    </div>
</template>

<!-- Custom column for status -->
<template #column-status="{ item }">
    <div class="flex flex-col items-center gap-2">
        <!-- Status Badge -->
        <span 
            class="px-3 py-1.5 rounded-full text-xs font-semibold border shadow-sm flex items-center gap-2 min-w-[120px] justify-center"
            :class="{
                'bg-gradient-to-r from-red-100 to-red-50 text-red-800 border-red-200': getIsOverBudget(item),
                'bg-gradient-to-r from-orange-100 to-orange-50 text-orange-800 border-orange-200': getUsagePercentage(item) >= 80 && getUsagePercentage(item) < 100,
                'bg-gradient-to-r from-yellow-100 to-yellow-50 text-yellow-800 border-yellow-200': getUsagePercentage(item) >= 50 && getUsagePercentage(item) < 80,
                'bg-gradient-to-r from-green-100 to-green-50 text-green-800 border-green-200': getUsagePercentage(item) < 50,
                'bg-gradient-to-r from-gray-100 to-gray-50 text-gray-800 border-gray-200': getIsExpired(item)
            }"
        >
            <span class="text-lg">{{ getBudgetStatusIcon(item) }}</span>
            {{ getBudgetStatusText(item) }}
        </span>

        <!-- Additional Status Info -->
        <div v-if="getIsOverBudget(item)" class="text-xs text-red-600 font-medium flex items-center gap-1">
            <span>💸</span>
            <span>Melebihi {{ formatCurrency(Math.abs(getRemainingAmount(item))) }}</span>
        </div>
        <div v-else-if="getUsagePercentage(item) >= 80" class="text-xs text-orange-600 font-medium flex items-center gap-1">
            <span>⚠️</span>
            <span>Hati-hati!</span>
        </div>
        <div v-else-if="getUsagePercentage(item) < 50" class="text-xs text-green-600 font-medium flex items-center gap-1">
            <span>✅</span>
            <span>Masih aman</span>
        </div>

        <!-- Period Status -->
        <div v-if="getIsExpired(item)" class="text-xs text-gray-500 flex items-center gap-1">
            <span>⌛</span>
            <span>Berakhir</span>
        </div>
        <div v-else-if="!getIsActive(item)" class="text-xs text-blue-500 flex items-center gap-1">
            <span>📅</span>
            <span>Akan datang</span>
        </div>
        <div v-else class="text-xs text-green-500 flex items-center gap-1">
            <span>🟢</span>
            <span>Aktif</span>
        </div>
    </div>
</template>

<!-- Custom actions slot -->
<template #actions="{ item }">
    <div class="flex flex-col gap-2 min-w-[140px]">
        <!-- Update Spent Amount -->
        <BaseButton
            @click="updateSpentAmount(item)"
            variant="primary"
            size="sm"
            class="!px-3 !py-2 w-full justify-center"
            title="Update data pengeluaran terkini"
            :loading="item.updating"
        >
            <template #icon>🔄</template>
            Update Real-time
        </BaseButton>

        <!-- Quick Actions Row -->
        <div class="flex gap-1">
            <BaseButton
                @click="createNextMonthBudget(item)"
                variant="secondary"
                size="xs"
                class="!px-2 !py-1 flex-1"
                title="Buat budget untuk bulan depan"
            >
                <template #icon>📅</template>
                Next
            </BaseButton>
            
            <BaseButton
                @click="openEditModal(item)"
                variant="secondary"
                size="xs"
                class="!px-2 !py-1 flex-1"
                title="Edit budget"
            >
                <template #icon>✏️</template>
                Edit
            </BaseButton>
            
            <BaseButton
                @click="openDeleteModal(item)"
                variant="danger"
                size="xs"
                class="!px-2 !py-1 flex-1"
                title="Hapus budget"
            >
                <template #icon>🗑️</template>
                Hapus
            </BaseButton>
        </div>
    </div>
</template>
                </BaseTable>

                <!-- Empty State CTA -->
                <div v-if="budgets.length === 0 && !loading" class="text-center mt-6">
                    <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-lg p-8 border border-gray-100">
                        <div class="text-6xl mb-4">🎯</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada budget</h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            Mulai dengan membuat budget pertama untuk merencanakan pengeluaran dan tabungan {{ displayNames }}
                        </p>
                        <BaseButton
                            @click="openCreateModal"
                            class="px-6 py-3"
                        >
                            <template #icon>➕</template>
                            Buat Budget Pertama
                        </BaseButton>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <BaseModal
                    v-model:show="showModal"
                    :title="editingBudget ? 'Edit Budget' : 'Buat Budget Baru'"
                    :description="editingBudget ? 
                        'Perbarui informasi budget pengeluaran/tabungan kita' : 
                        'Buat budget baru untuk merencanakan pengeluaran dan tabungan bersama'"
                    :icon="editingBudget ? '✏️' : '➕'"
                    :confirm-text="editingBudget ? 'Perbarui' : 'Simpan'"
                    :confirm-loading="form.processing"
                    @confirm="saveBudget"
                    @close="closeModal"
                    size="lg"
                >
                    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                        <!-- Category -->
                        <SelectInput
                            v-model="form.category_id"
                            label="Kategori"
                            placeholder="Pilih kategori untuk budget"
                            :options="categories.map(cat => ({ 
                                value: cat.id, 
                                label: `${cat.name} ${cat.type === 'joint' ? '👥' : '👤'} (${formatBudgetType(cat.budget_type)})`
                            }))"
                            :error="form.errors.category_id"
                            icon="📁"
                            required
                            :disabled="form.processing || categories.length === 0"
                        />

                        <!-- Period Start -->
                        <DateInput
                            v-model="form.period_start"
                            label="Periode Mulai"
                            :error="form.errors.period_start"
                            icon="📅"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Period End -->
                        <DateInput
                            v-model="form.period_end"
                            label="Periode Berakhir"
                            :error="form.errors.period_end"
                            icon="📅"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Target Amount -->
                        <AccountInput
                            v-model="form.target_amount"
                            label="Target Budget"
                            placeholder="Contoh: 5000000 (5 juta)"
                            :error="form.errors.target_amount"
                            icon="🎯"
                            account-type="account_number"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Info Box -->
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200">
                            <p class="text-sm text-blue-700 flex items-start gap-2">
                                <span class="text-lg mt-0.5">💡</span>
                                <span>
                                    <strong class="block">Tips Budgeting:</strong>
                                    • Budget bisa dibuat untuk <strong>Pengeluaran</strong> (makan, transport, dll) dan <strong>Tabungan</strong> (rumah, liburan, dll)<br>
                                    • Buat budget realistis berdasarkan kebutuhan sebelumnya<br>
                                    • Monitor progress secara berkala<br>
                                    • Sesuaikan budget jika diperlukan
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
                    title="Hapus Budget"
                    :description="budgetToDelete ? `Apakah Anda yakin ingin menghapus budget untuk ${budgetToDelete.category?.name}?` : 'Apakah Anda yakin ingin menghapus budget ini?'"
                    icon="🗑️"
                    confirm-text="Hapus"
                    cancel-text="Batal"
                    confirm-variant="danger"
                    :confirm-loading="deleting"
                    @confirm="deleteBudget"
                    @close="showDeleteModal = false"
                >
                    <div class="space-y-4">
                        <div class="p-4 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-2xl">
                            <p class="text-sm text-red-600 flex items-start gap-2">
                                <span class="text-lg mt-0.5">⚠️</span>
                                <span>
                                    <strong class="block">Tindakan ini tidak dapat dibatalkan!</strong>
                                    Data progress pengeluaran untuk budget ini akan hilang.
                                </span>
                            </p>
                        </div>

                        <div v-if="budgetToDelete" class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-2xl">
                            <h4 class="font-semibold text-gray-800 mb-2">Detail Budget:</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Kategori:</span>
                                    <p class="font-medium text-gray-800">{{ budgetToDelete.category?.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Jenis:</span>
                                    <p class="font-medium text-gray-800">{{ formatBudgetType(budgetToDelete.category?.budget_type) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Periode:</span>
                                    <p class="font-medium text-gray-800">{{ formatPeriod(budgetToDelete.period_start, budgetToDelete.period_end) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Target:</span>
                                    <p class="font-bold text-gray-800">{{ formatCurrency(getTargetAmount(budgetToDelete)) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Terpakai:</span>
                                    <p class="font-bold text-orange-600">{{ formatCurrency(getSpentAmount(budgetToDelete)) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Sisa:</span>
                                    <p class="font-bold" :class="getRemainingAmount(budgetToDelete) < 0 ? 'text-red-600' : 'text-green-600'">
                                        {{ formatCurrency(getRemainingAmount(budgetToDelete)) }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Progress:</span>
                                    <p class="font-medium text-gray-800">{{ getUsagePercentage(budgetToDelete).toFixed(1) }}%</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <p class="font-medium text-gray-800">{{ getBudgetStatusText(budgetToDelete) }}</p>
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