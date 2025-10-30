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

const page = usePage();
const user = page.props.auth.user;
const partnerName = user?.partner?.name ?? 'Pasangan';

// Format tampilan nama pasangan
const displayNames = `${user?.name ?? ''} 💕 ${partnerName}`;

// Data dan state
const accounts = ref([]);
const loading = ref(false);
const deleting = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const showBalanceModal = ref(false);
const editingAccount = ref(null);
const accountToDelete = ref(null);
const accountToUpdateBalance = ref(null);
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

// Forms
const form = useForm({
    name: '',
    type: 'personal',
    current_balance: 0,
});

const balanceForm = useForm({
    current_balance: 0,
});

// Columns untuk tabel
const columns = [
    {
        key: 'name',
        label: 'Nama Akun',
        icon: '🏦',
        sortable: true
    },
    {
        key: 'type',
        label: 'Tipe',
        type: 'badge',
        icon: '👥',
        sortable: true,
        badgeVariant: 'romantic'
    },
    {
        key: 'current_balance',
        label: 'Saldo',
        icon: '💰',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'user.name',
        label: 'Dibuat Oleh',
        icon: '👤',
        sortable: true
    },
    {
        key: 'created_at',
        label: 'Dibuat',
        icon: '📅',
        sortable: true,
        type: 'date'
    }
];

// Type options (personal/joint)
const typeOptions = [
    { value: 'personal', label: '👤 Personal' },
    { value: 'joint', label: '👥 Joint' }
];

// Load data
const loadAccounts = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('master-data.accounts.index'));
        accounts.value = response.data.accounts || response.data;
    } catch (error) {
        console.error('Error loading accounts:', error);
        flashMessage.value = {
            message: 'Gagal memuat data akun',
            type: 'error'
        };
    } finally {
        loading.value = false;
    }
};

// Actions
const openCreateModal = () => {
    editingAccount.value = null;
    form.reset();
    form.type = 'personal';
    form.current_balance = 0;
    showModal.value = true;
};

const openEditModal = (account) => {
    // Cek apakah user adalah creator akun
    if (account.user_id !== user.id) {
        flashMessage.value = {
            message: 'Anda hanya dapat mengedit akun yang Anda buat',
            type: 'error'
        };
        return;
    }

    editingAccount.value = account;
    form.name = account.name;
    form.type = account.type;
    form.current_balance = account.current_balance;
    showModal.value = true;
};

const openDeleteModal = (account) => {
    // Cek apakah user adalah creator akun
    if (account.user_id !== user.id) {
        flashMessage.value = {
            message: 'Anda hanya dapat menghapus akun yang Anda buat',
            type: 'error'
        };
        return;
    }

    accountToDelete.value = account;
    showDeleteModal.value = true;
};

const openBalanceModal = (account) => {
    // Cek apakah user adalah creator akun
    if (account.user_id !== user.id) {
        flashMessage.value = {
            message: 'Anda hanya dapat mengubah saldo akun yang Anda buat',
            type: 'error'
        };
        return;
    }

    accountToUpdateBalance.value = account;
    balanceForm.current_balance = account.current_balance;
    showBalanceModal.value = true;
};

const saveAccount = () => {
    if (editingAccount.value) {
        form.put(route('master-data.accounts.update', editingAccount.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadAccounts();
                form.reset();
                editingAccount.value = null;
            },
            onError: (errors) => {
                console.error('Update error:', errors);
            }
        });
    } else {
        form.post(route('master-data.accounts.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadAccounts();
                form.reset();
            },
            onError: (errors) => {
                console.error('Create error:', errors);
            }
        });
    }
};

const updateBalance = () => {
    if (!accountToUpdateBalance.value) return;

    balanceForm.put(route('master-data.accounts.update-balance', accountToUpdateBalance.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showBalanceModal.value = false;
            loadAccounts();
            balanceForm.reset();
            accountToUpdateBalance.value = null;
        },
        onError: (errors) => {
            console.error('Balance update error:', errors);
        }
    });
};

const deleteAccount = () => {
    if (!accountToDelete.value) return;

    deleting.value = true;

    router.delete(route('master-data.accounts.destroy', accountToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            loadAccounts();
            accountToDelete.value = null;
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
    editingAccount.value = null;
    form.clearErrors();
    form.reset();
};

const closeBalanceModal = () => {
    showBalanceModal.value = false;
    accountToUpdateBalance.value = null;
    balanceForm.clearErrors();
    balanceForm.reset();
};

// Format badge untuk type (personal/joint)
const formatTypeBadge = (type) => {
    const typeMap = {
        personal: 'Personal',
        joint: 'Joint'
    };
    return typeMap[type] || type;
};

const formatTypeIcon = (type) => {
    const iconMap = {
        personal: '👤',
        joint: '👥'
    };
    return iconMap[type] || '🏦';
};

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
};

// Format date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

// Computed untuk stats
const personalAccounts = computed(() => accounts.value.filter(a => a.type === 'personal'));
const jointAccounts = computed(() => accounts.value.filter(a => a.type === 'joint'));
const myAccounts = computed(() => accounts.value.filter(a => a.user_id === user.id));
const partnerAccounts = computed(() => accounts.value.filter(a => a.user_id !== user.id && a.type === 'joint'));

// Total balances
const totalBalance = computed(() => accounts.value.reduce((sum, account) => sum + parseFloat(account.current_balance), 0));
const personalBalance = computed(() => personalAccounts.value.reduce((sum, account) => sum + parseFloat(account.current_balance), 0));
const jointBalance = computed(() => jointAccounts.value.reduce((sum, account) => sum + parseFloat(account.current_balance), 0));

// Cek apakah user bisa edit/hapus akun
const canEditAccount = (account) => account.user_id === user.id;

onMounted(() => {
    loadAccounts();
});
</script>

<template>
    <AppLayout title="Kelola Akun">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    🏦 Kelola Akun Keuangan {{ displayNames }}
                </h2>
                <span class="text-sm text-gray-500 italic">Atur akun untuk mengelola keuangan bersama 💞</span>
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
                        Kelola Akun Keuangan
                    </h1>
                    <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                        Kelola semua akun keuangan 
                        <span class="text-blue-500 font-semibold">personal</span> dan 
                        <span class="text-green-500 font-semibold">joint</span> 
                        kita berdua. Pantau saldo dan atur akses untuk keuangan yang lebih transparan 💫
                    </p>
                </div>

                <!-- Stats Cards - Responsive grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                    <!-- Total Balance -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-rose-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-rose-400 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">💰</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(totalBalance) }}
                            </h3>
                            <p class="text-sm text-gray-600">Total Saldo</p>
                        </div>
                    </BaseCard>

                    <!-- Personal Accounts -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-blue-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">👤</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ personalAccounts.length }}
                            </h3>
                            <p class="text-sm text-gray-600">Personal</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatCurrency(personalBalance) }}</p>
                        </div>
                    </BaseCard>

                    <!-- Joint Accounts -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-green-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">👥</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ jointAccounts.length }}
                            </h3>
                            <p class="text-sm text-gray-600">Joint</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatCurrency(jointBalance) }}</p>
                        </div>
                    </BaseCard>

                    <!-- My Accounts -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-purple-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-400 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">📊</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ myAccounts.length }}
                            </h3>
                            <p class="text-sm text-gray-600">Akun Saya</p>
                        </div>
                    </BaseCard>
                </div>

                <!-- Main Table -->
                <BaseTable
                    title="Data Akun"
                    description="Kelola semua akun keuangan kita berdua"
                    :columns="columns"
                    :data="accounts"
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

                    <!-- Custom column for balance -->
                    <template #column-current_balance="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg" :class="item.current_balance >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ item.current_balance >= 0 ? '💰' : '💸' }}
                            </span>
                            <span class="font-bold text-gray-800 text-sm" :class="item.current_balance >= 0 ? 'text-green-700' : 'text-red-700'">
                                {{ formatCurrency(item.current_balance) }}
                            </span>
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
                            @click="openBalanceModal(item)"
                            variant="primary"
                            size="sm"
                            class="!px-2 !py-2"
                            :disabled="!canEditAccount(item)"
                            :title="!canEditAccount(item) ? 'Hanya dapat mengubah saldo akun yang Anda buat' : 'Update saldo'"
                        >
                            <template #icon>💰</template>
                            Saldo
                        </BaseButton>
                        <BaseButton
                            @click="openEditModal(item)"
                            variant="secondary"
                            size="sm"
                            class="!px-2 !py-2"
                            :disabled="!canEditAccount(item)"
                            :title="!canEditAccount(item) ? 'Hanya dapat mengedit akun yang Anda buat' : 'Edit akun'"
                        >
                            <template #icon>✏️</template>
                            Edit
                        </BaseButton>
                        <BaseButton
                            @click="openDeleteModal(item)"
                            variant="danger"
                            size="sm"
                            class="!px-2 !py-2"
                            :disabled="!canEditAccount(item)"
                            :title="!canEditAccount(item) ? 'Hanya dapat menghapus akun yang Anda buat' : 'Hapus akun'"
                        >
                            <template #icon>🗑️</template>
                            Hapus
                        </BaseButton>
                    </template>
                </BaseTable>

                <!-- Empty State CTA -->
                <div v-if="accounts.length === 0 && !loading" class="text-center mt-6">
                    <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-lg p-8 border border-gray-100">
                        <div class="text-6xl mb-4">🏦</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada akun</h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            Mulai dengan membuat akun pertama untuk mengelola keuangan {{ displayNames }}
                        </p>
                        <BaseButton
                            @click="openCreateModal"
                            class="px-6 py-3"
                        >
                            <template #icon>➕</template>
                            Buat Akun Pertama
                        </BaseButton>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <BaseModal
                    v-model:show="showModal"
                    :title="editingAccount ? 'Edit Akun' : 'Tambah Akun Baru'"
                    :description="editingAccount ? 
                        'Perbarui informasi akun keuangan kita' : 
                        'Tambahkan akun baru untuk mengelola keuangan bersama'"
                    :icon="editingAccount ? '✏️' : '➕'"
                    :confirm-text="editingAccount ? 'Perbarui' : 'Simpan'"
                    :confirm-loading="form.processing"
                    @confirm="saveAccount"
                    @close="closeModal"
                    size="lg"
                >
                    <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-2">
                        <TextInput
                            v-model="form.name"
                            label="Nama Akun"
                            placeholder="Contoh: BCA Pribadi, Mandiri Bersama, Kas Tunai"
                            :error="form.errors.name"
                            icon="🏦"
                            required
                            :disabled="form.processing"
                        />

                        <SelectInput
                            v-model="form.type"
                            label="Tipe Akun"
                            placeholder="Pilih tipe akun"
                            :options="typeOptions"
                            :error="form.errors.type"
                            icon="👥"
                            required
                            :disabled="form.processing"
                        />

                        <AccountInput
                         v-if="!editingAccount"
                            v-model="form.current_balance"
                            label="Saldo Awal"
                            placeholder="Masukkan saldo awal"
                            :error="form.errors.current_balance"
                            icon="💰"
                            account-type="account_number"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Info Box -->
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200">
                            <p class="text-sm text-gray-700 flex items-start gap-2">
                                <span class="text-lg mt-0.5">💡</span>
                                <span>
                                    <strong class="block">Informasi:</strong>
                                    • <strong>Personal:</strong> Hanya Anda yang bisa melihat dan mengelola<br>
                                    • <strong>Joint:</strong> Bisa dilihat dan digunakan oleh Anda dan partner
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

                <!-- Update Balance Modal -->
                <BaseModal
                    v-model:show="showBalanceModal"
                    title="Update Saldo Akun"
                    :description="accountToUpdateBalance ? `Update saldo untuk akun ${accountToUpdateBalance.name}` : 'Update saldo akun'"
                    icon="💰"
                    confirm-text="Update Saldo"
                    cancel-text="Batal"
                    :confirm-loading="balanceForm.processing"
                    @confirm="updateBalance"
                    @close="closeBalanceModal"
                    size="md"
                >
                    <div class="space-y-4">
                        <AccountInput
                            v-model="balanceForm.current_balance"
                            label="Saldo Baru"
                            placeholder="Masukkan saldo baru"
                            :error="balanceForm.errors.current_balance"
                            icon="💰"
                            account-type="account_number"
                            required
                            :disabled="balanceForm.processing"
                        />

                        <div v-if="accountToUpdateBalance" class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-2xl">
                            <h4 class="font-semibold text-gray-800 mb-2">Detail Akun:</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Nama:</span>
                                    <p class="font-medium text-gray-800">{{ accountToUpdateBalance.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Tipe:</span>
                                    <p class="font-medium text-gray-800">{{ formatTypeBadge(accountToUpdateBalance.type) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Saldo Saat Ini:</span>
                                    <p class="font-bold text-green-700">{{ formatCurrency(accountToUpdateBalance.current_balance) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Dibuat:</span>
                                    <p class="font-medium text-gray-800">{{ formatDate(accountToUpdateBalance.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </BaseModal>

                <!-- Delete Confirmation Modal -->
                <BaseModal
                    v-model:show="showDeleteModal"
                    title="Hapus Akun"
                    :description="accountToDelete ? `Apakah Anda yakin ingin menghapus akun ${accountToDelete.name}?` : 'Apakah Anda yakin ingin menghapus akun ini?'"
                    icon="🗑️"
                    confirm-text="Hapus"
                    cancel-text="Batal"
                    confirm-variant="danger"
                    :confirm-loading="deleting"
                    @confirm="deleteAccount"
                    @close="showDeleteModal = false"
                >
                    <div class="space-y-4">
                        <div class="p-4 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-2xl">
                            <p class="text-sm text-red-600 flex items-start gap-2">
                                <span class="text-lg mt-0.5">⚠️</span>
                                <span>
                                    <strong class="block">Tindakan ini tidak dapat dibatalkan!</strong>
                                    Semua data transaksi yang terkait dengan akun ini akan terpengaruh. Pastikan tidak ada transaksi yang menggunakan akun ini.
                                </span>
                            </p>
                        </div>

                        <div v-if="accountToDelete" class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-2xl">
                            <h4 class="font-semibold text-gray-800 mb-2">Detail Akun:</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Nama:</span>
                                    <p class="font-medium text-gray-800">{{ accountToDelete.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Tipe:</span>
                                    <p class="font-medium text-gray-800">{{ formatTypeBadge(accountToDelete.type) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Saldo:</span>
                                    <p class="font-bold text-green-700">{{ formatCurrency(accountToDelete.current_balance) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Dibuat:</span>
                                    <p class="font-medium text-gray-800">{{ formatDate(accountToDelete.created_at) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Dibuat Oleh:</span>
                                    <p class="font-medium text-gray-800">{{ accountToDelete.user_id === user.id ? 'Saya' : (accountToDelete.user?.name || 'Partner') }}</p>
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
@keyframes bounce-slow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(12px); }
}
@keyframes bounce-slow2 {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-15px); }
}
.animate-bounce-slow {
  animation: bounce-slow 7s infinite ease-in-out;
}
.animate-bounce-slow2 {
  animation: bounce-slow2 9s infinite ease-in-out;
}

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