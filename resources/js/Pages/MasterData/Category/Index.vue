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

const page = usePage();
const user = page.props.auth.user;
const partnerName = user?.partner?.name ?? 'Pasangan';

// Format tampilan nama pasangan
const displayNames = `${user?.name ?? ''} 💕 ${partnerName}`;

// Data dan state
const categories = ref([]);
const loading = ref(false);
const deleting = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingCategory = ref(null);
const categoryToDelete = ref(null);
const flashMessage = ref(null);

// Watch for flash messages - EVEN SAFER VERSION
watch(() => page.props.flash, (newFlash) => {
    if (!newFlash) return; // Langsung return jika undefined/null
    
    const message = newFlash.success || newFlash.error;
    if (message) {
        flashMessage.value = {
            message: message,
            type: newFlash.type || (newFlash.success ? 'success' : 'error')
        };
        
        setTimeout(() => {
            flashMessage.value = null;
        }, 5000);
    }
}, { immediate: true });

// Form
const form = useForm({
    name: '',
    type: '',
    budget_type: '',
    description: '',
    is_active: true
});

// Columns untuk tabel
const columns = [
    {
        key: 'name',
        label: 'Nama Kategori',
        icon: '📁',
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
        key: 'budget_type',
        label: 'Jenis Anggaran',
        type: 'badge',
        icon: '💰',
        sortable: true
    },
    {
        key: 'user.name',
        label: 'Dibuat Oleh',
        icon: '👤',
        sortable: true
    },
    {
        key: 'description',
        label: 'Deskripsi',
        icon: '📝',
        class: 'max-w-xs'
    },
    {
        key: 'is_active',
        label: 'Status',
        type: 'badge',
        icon: '🟢',
        sortable: true
    }
];

// Type options (personal/joint)
const typeOptions = [
    { value: 'personal', label: '👤 Personal' },
    { value: 'joint', label: '👥 Joint' }
];

// Budget type options (income/expense/savings)
const budgetTypeOptions = [
    { value: 'income', label: '💰 Pemasukan' },
    { value: 'expense', label: '💸 Pengeluaran' },
    { value: 'savings', label: '🏦 Tabungan' }
];

// Load data
const loadCategories = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('master-data.categories.index'));
        categories.value = response.data.categories || response.data;
    } catch (error) {
        console.error('Error loading categories:', error);
        flashMessage.value = {
            message: 'Gagal memuat data kategori',
            type: 'error'
        };
    } finally {
        loading.value = false;
    }
};

// Actions
const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    form.type = '';
    form.budget_type = '';
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (category) => {
    // Cek apakah user adalah creator kategori
    if (category.user_id !== user.id) {
        flashMessage.value = {
            message: 'Anda hanya dapat mengedit kategori yang Anda buat',
            type: 'error'
        };
        return;
    }

    editingCategory.value = category;
    form.name = category.name;
    form.type = category.type;
    form.budget_type = category.budget_type;
    form.description = category.description || '';
    form.is_active = category.is_active;
    showModal.value = true;
};

const openDeleteModal = (category) => {
    // Cek apakah user adalah creator kategori
    if (category.user_id !== user.id) {
        flashMessage.value = {
            message: 'Anda hanya dapat menghapus kategori yang Anda buat',
            type: 'error'
        };
        return;
    }

    categoryToDelete.value = category;
    showDeleteModal.value = true;
};

const saveCategory = () => {
    if (editingCategory.value) {
        form.put(route('master-data.categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadCategories();
                form.reset();
                editingCategory.value = null;
            },
            onError: (errors) => {
                console.error('Update error:', errors);
            }
        });
    } else {
        form.post(route('master-data.categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadCategories();
                form.reset();
            },
            onError: (errors) => {
                console.error('Create error:', errors);
            }
        });
    }
};

const deleteCategory = () => {
    if (!categoryToDelete.value) return;

    deleting.value = true;

    router.delete(route('master-data.categories.destroy', categoryToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            loadCategories();
            categoryToDelete.value = null;
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
    editingCategory.value = null;
    form.clearErrors();
    form.reset();
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
    return iconMap[type] || '📁';
};

// Format badge untuk budget type
const formatBudgetTypeBadge = (budgetType) => {
    const typeMap = {
        income: 'Pemasukan',
        expense: 'Pengeluaran',
        savings: 'Tabungan'
    };
    return typeMap[budgetType] || budgetType;
};

const formatBudgetTypeIcon = (budgetType) => {
    const iconMap = {
        income: '💰',
        expense: '💸',
        savings: '🏦'
    };
    return iconMap[budgetType] || '📊';
};

// Computed untuk stats
const personalCategories = computed(() => categories.value.filter(c => c.type === 'personal'));
const jointCategories = computed(() => categories.value.filter(c => c.type === 'joint'));
const incomeCategories = computed(() => categories.value.filter(c => c.budget_type === 'income'));
const expenseCategories = computed(() => categories.value.filter(c => c.budget_type === 'expense'));
const savingsCategories = computed(() => categories.value.filter(c => c.budget_type === 'savings'));
const myCategories = computed(() => categories.value.filter(c => c.user_id === user.id));
const partnerCategories = computed(() => categories.value.filter(c => c.user_id !== user.id && c.type === 'joint'));

// Cek apakah user bisa edit/hapus kategori
const canEditCategory = (category) => category.user_id === user.id;

onMounted(() => {
    loadCategories();
});
</script>

<template>
    <AppLayout title="Kelola Kategori">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
                <h2 class="font-semibold text-xl md:text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    📁 Kelola Kategori {{ displayNames }}
                </h2>
                <span class="text-xs md:text-sm text-gray-500 italic text-center md:text-right">
                    Atur kategori untuk mengorganisir keuangan bersama 💞
                </span>
            </div>
        </template>

        <div class="py-2 min-h-screen relative overflow-hidden">
            <!-- PERBAIKAN: Padding lebih kecil untuk mobile -->
            <div class="w-full px-3 sm:px-4 lg:px-6 relative z-10">

                <!-- Flash Message -->
                <div 
                    v-if="flashMessage" 
                    class="mb-4 md:mb-6 p-3 md:p-4 rounded-xl md:rounded-2xl border backdrop-blur-sm transition-all duration-300"
                    :class="{
                        'bg-green-50 border-green-200 text-green-800': flashMessage.type === 'success',
                        'bg-red-50 border-red-200 text-red-800': flashMessage.type === 'error'
                    }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 md:gap-3">
                            <span class="text-lg md:text-xl">
                                {{ flashMessage.type === 'success' ? '✅' : '⚠️' }}
                            </span>
                            <span class="font-medium text-sm md:text-base">{{ flashMessage.message }}</span>
                        </div>
                        <button 
                            @click="flashMessage = null"
                            class="text-gray-500 hover:text-gray-700 transition-colors text-lg"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Hero Section -->
                <div class="text-center mb-4">
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-extrabold text-rose-600 drop-shadow-md mb-2 md:mb-3">
                        Kelola Kategori Keuangan
                    </h1>
                    <p class="text-gray-600 text-xs md:text-sm leading-relaxed px-2">
                        Atur kategori untuk mengorganisir 
                        <span class="text-rose-500 font-semibold">pemasukan</span>, 
                        <span class="text-sky-500 font-semibold">pengeluaran</span>, dan 
                        <span class="text-purple-500 font-semibold">tabungan</span> 
                        kita berdua. Setiap kategori adalah langkah kecil menuju keuangan yang lebih teratur 💫
                    </p>
                </div>

                <!-- Stats Cards - Responsive grid -->
                <div class="grid grid-cols-3 md:grid-cols-5 gap-2 md:gap-3 mb-4">
                    <!-- Personal Categories -->
                    <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-blue-100">
                        <div class="text-center">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-lg md:rounded-xl flex items-center justify-center mx-auto mb-2 md:mb-3 shadow-md">
                                <span class="text-lg md:text-xl text-white">👤</span>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mb-1">
                                {{ personalCategories.length }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600">Personal</p>
                        </div>
                    </div>

                    <!-- Joint Categories -->
                    <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-green-100">
                        <div class="text-center">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-lg md:rounded-xl flex items-center justify-center mx-auto mb-2 md:mb-3 shadow-md">
                                <span class="text-lg md:text-xl text-white">👥</span>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mb-1">
                                {{ jointCategories.length }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600">Joint</p>
                        </div>
                    </div>

                    <!-- Income Categories -->
                    <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-green-100">
                        <div class="text-center">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-lg md:rounded-xl flex items-center justify-center mx-auto mb-2 md:mb-3 shadow-md">
                                <span class="text-lg md:text-xl text-white">💰</span>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mb-1">
                                {{ incomeCategories.length }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600">Pemasukan</p>
                        </div>
                    </div>

                    <!-- Expense Categories -->
                    <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-orange-100">
                        <div class="text-center">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-orange-400 to-red-500 rounded-lg md:rounded-xl flex items-center justify-center mx-auto mb-2 md:mb-3 shadow-md">
                                <span class="text-lg md:text-xl text-white">💸</span>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mb-1">
                                {{ expenseCategories.length }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600">Pengeluaran</p>
                        </div>
                    </div>

                    <!-- My Categories -->
                    <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-purple-100">
                        <div class="text-center">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-purple-400 to-pink-500 rounded-lg md:rounded-xl flex items-center justify-center mx-auto mb-2 md:mb-3 shadow-md">
                                <span class="text-lg md:text-xl text-white">📊</span>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mb-1">
                                {{ myCategories.length }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600">Kategori Saya</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-2 gap-2 md:gap-3 mb-4">
                    <!-- Akun -->
                    <Link
                        :href="route('master-data.accounts.index')"
                        class="flex items-center justify-center gap-2 px-3 md:px-4 py-2 md:py-3 rounded-lg md:rounded-xl
                            bg-gradient-to-r from-pink-300 via-rose-400 to-rose-500
                            text-white font-semibold shadow-md hover:shadow-lg 
                            hover:from-pink-400 hover:to-rose-600
                            transition-all duration-300 ease-in-out transform hover:-translate-y-0.5 active:scale-95 text-xs md:text-sm"
                    >
                        <span class="text-base md:text-lg">🌸</span>
                        <span>Akun Baru</span>
                    </Link>

                    <!-- Kelola Anggaran -->
                    <Link 
                        :href="route('budgets.index')"
                        class="flex items-center justify-center gap-2 px-3 md:px-4 py-2 md:py-3 rounded-lg md:rounded-xl
                            bg-gradient-to-r from-pink-200 via-rose-300 to-rose-400
                            text-white font-semibold shadow-md hover:shadow-lg 
                            hover:from-pink-300 hover:to-rose-500
                            transition-all duration-300 ease-in-out transform hover:-translate-y-0.5 active:scale-95 text-xs md:text-sm"
                    >
                        <span class="text-base md:text-lg">💰</span>
                        <span>Kelola Anggaran</span>
                    </Link>
                </div>

                <!-- Main Table - TETAP MENGGUNAKAN BaseTable -->
                <BaseTable
                    title="Data Kategori"
                    description="Kelola semua kategori keuangan kita berdua"
                    :columns="columns"
                    :data="categories"
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
                            <span class="text-base md:text-lg">{{ formatTypeIcon(item.type) }}</span>
                            <span class="font-medium text-gray-700 text-xs md:text-sm">{{ formatTypeBadge(item.type) }}</span>
                        </div>
                    </template>

                    <!-- Custom column for budget type -->
                    <template #column-budget_type="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-base md:text-lg">{{ formatBudgetTypeIcon(item.budget_type) }}</span>
                            <span class="font-medium text-gray-700 text-xs md:text-sm">{{ formatBudgetTypeBadge(item.budget_type) }}</span>
                        </div>
                    </template>

                    <!-- Custom column for created by -->
                    <template #column-user.name="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-base md:text-lg">{{ item.user_id === user.id ? '👤' : '👥' }}</span>
                            <span class="font-medium text-gray-700 text-xs md:text-sm" :class="item.user_id === user.id ? 'text-blue-600' : 'text-green-600'">
                                {{ item.user_id === user.id ? 'Saya' : (item.user?.name || 'Partner') }}
                            </span>
                        </div>
                    </template>

                    <!-- Custom column for status -->
                    <template #column-is_active="{ item }">
                        <span 
                            class="px-2 py-1 rounded-full text-xs font-medium border shadow-sm"
                            :class="item.is_active ? 
                                'bg-green-100 text-green-800 border-green-200' : 
                                'bg-red-100 text-red-800 border-red-200'"
                        >
                            {{ item.is_active ? '🟢 Aktif' : '🔴 Nonaktif' }}
                        </span>
                    </template>

                    <!-- Custom actions slot -->
                    <template #actions="{ item }">
                            <BaseButton
                                @click="openEditModal(item)"
                                variant="secondary"
                                size="sm"
                                class="!px-2 !py-1 text-xs"
                                :disabled="!canEditCategory(item)"
                                :title="!canEditCategory(item) ? 'Hanya dapat mengedit kategori yang Anda buat' : 'Edit kategori'"
                            >
                                <template #icon>✏️</template>
                                <span class="hidden xs:inline">Edit</span>
                            </BaseButton>
                            <BaseButton
                                @click="openDeleteModal(item)"
                                variant="danger"
                                size="sm"
                                class="!px-2 !py-1 text-xs"
                                :disabled="!canEditCategory(item)"
                                :title="!canEditCategory(item) ? 'Hanya dapat menghapus kategori yang Anda buat' : 'Hapus kategori'"
                            >
                                <template #icon>🗑️</template>
                                <span class="hidden xs:inline">Hapus</span>
                            </BaseButton>
                    </template>
                </BaseTable>

                <!-- Empty State CTA -->
                <div v-if="categories.length === 0 && !loading" class="text-center mt-6">
                    <div class="bg-white/80 backdrop-blur-md rounded-xl md:rounded-2xl shadow-lg p-6 md:p-8 border border-gray-100">
                        <div class="text-4xl md:text-6xl mb-4">📁</div>
                        <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-2">Belum ada kategori</h3>
                        <p class="text-gray-600 mb-6 text-sm md:text-base max-w-md mx-auto">
                            Mulai dengan membuat kategori pertama untuk mengorganisir keuangan {{ displayNames }}
                        </p>
                        <BaseButton
                            @click="openCreateModal"
                            class="px-4 py-2 md:px-6 md:py-3 text-sm md:text-base"
                        >
                            <template #icon>➕</template>
                            Buat Kategori Pertama
                        </BaseButton>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <BaseModal
                    v-model:show="showModal"
                    :title="editingCategory ? 'Edit Kategori' : 'Tambah Kategori Baru'"
                    :description="editingCategory ? 
                        'Perbarui informasi kategori keuangan kita' : 
                        'Tambahkan kategori baru untuk mengorganisir keuangan bersama'"
                    :icon="editingCategory ? '✏️' : '➕'"
                    :confirm-text="editingCategory ? 'Perbarui' : 'Simpan'"
                    :confirm-loading="form.processing"
                    @confirm="saveCategory"
                    @close="closeModal"
                    size="lg"
                >
                    <div class="space-y-3 max-h-[50vh] md:max-h-[60vh] overflow-y-auto pr-2">
                        <TextInput
                            v-model="form.name"
                            label="Nama Kategori"
                            placeholder="Contoh: Gaji Bulanan, Belanja Bulanan, Tabungan Pendidikan"
                            :error="form.errors.name"
                            icon="📁"
                            required
                            :disabled="form.processing"
                        />

                        <SelectInput
                            v-model="form.type"
                            label="Tipe Kategori"
                            placeholder="Pilih tipe kategori"
                            :options="typeOptions"
                            :error="form.errors.type"
                            icon="👥"
                            required
                            :disabled="form.processing"
                        />

                        <SelectInput
                            v-model="form.budget_type"
                            label="Jenis Anggaran"
                            placeholder="Pilih jenis anggaran"
                            :options="budgetTypeOptions"
                            :error="form.errors.budget_type"
                            icon="💰"
                            required
                            :disabled="form.processing"
                        />

                        <TextAreaInput
                            v-model="form.description"
                            label="Deskripsi Kategori"
                            placeholder="Tambahkan deskripsi untuk kategori ini (opsional)"
                            :error="form.errors.description"
                            icon="📝"
                            :rows="3"
                            :show-counter="true"
                            :max-length="100"
                            :disabled="form.processing"
                        />

                        <div class="flex items-center gap-3 p-3 md:p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl md:rounded-2xl border border-blue-200">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                :true-value="true"
                                :false-value="false"
                                class="w-5 h-5 text-pink-500 bg-gray-100 border-gray-300 rounded focus:ring-pink-400 focus:ring-2"
                                :disabled="form.processing"
                            >
                            <label for="is_active" class="text-xs md:text-sm font-medium text-gray-700 flex items-center gap-2 cursor-pointer">
                                <span class="text-base md:text-lg">🟢</span>
                                <span>
                                    Kategori Aktif
                                    <span class="text-gray-500 text-xs block">Nonaktifkan untuk menyembunyikan kategori</span>
                                </span>
                            </label>
                        </div>

                        <!-- Info Box -->
                        <div class="p-3 md:p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl md:rounded-2xl border border-blue-200">
                            <p class="text-xs md:text-sm text-gray-700 flex items-start gap-2">
                                <span class="text-base md:text-lg mt-0.5">💡</span>
                                <span>
                                    <strong class="block">Informasi:</strong>
                                    • <strong>Personal:</strong> Hanya Anda yang bisa melihat dan mengelola<br>
                                    • <strong>Joint:</strong> Bisa dilihat dan digunakan oleh Anda dan partner
                                </span>
                            </p>
                        </div>

                        <!-- Error Summary -->
                        <div v-if="form.hasErrors" class="p-2 bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-xl md:rounded-2xl">
                            <p class="text-xs md:text-sm text-red-600 flex items-center gap-2">
                                <span>⚠️</span>
                                Terdapat kesalahan dalam pengisian form. Silakan periksa kembali input Anda.
                            </p>
                        </div>
                    </div>
                </BaseModal>

                <!-- Delete Confirmation Modal -->
                <BaseModal
                    v-model:show="showDeleteModal"
                    title="Hapus Kategori"
                    :description="categoryToDelete ? `Apakah Anda yakin ingin menghapus kategori ${categoryToDelete.name}?` : 'Apakah Anda yakin ingin menghapus kategori ini?'"
                    icon="🗑️"
                    confirm-text="Hapus"
                    cancel-text="Batal"
                    confirm-variant="danger"
                    :confirm-loading="deleting"
                    @confirm="deleteCategory"
                    @close="showDeleteModal = false"
                >
                    <div class="space-y-4">
                        <div class="p-3 md:p-4 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-xl md:rounded-2xl">
                            <p class="text-xs md:text-sm text-red-600 flex items-start gap-2">
                                <span class="text-base md:text-lg mt-0.5">⚠️</span>
                                <span>
                                    <strong class="block">Tindakan ini tidak dapat dibatalkan!</strong>
                                    Semua data yang terkait dengan kategori ini akan terpengaruh. Pastikan tidak ada transaksi atau anggaran yang menggunakan kategori ini.
                                </span>
                            </p>
                        </div>

                        <div v-if="categoryToDelete" class="p-3 md:p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-xl md:rounded-2xl">
                            <h4 class="font-semibold text-gray-800 text-sm md:text-base mb-2">Detail Kategori:</h4>
                            <div class="grid grid-cols-1 xs:grid-cols-2 gap-3 md:gap-4 text-xs md:text-sm">
                                <div>
                                    <span class="text-gray-600">Nama:</span>
                                    <p class="font-medium text-gray-800 truncate">{{ categoryToDelete.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Tipe:</span>
                                    <p class="font-medium text-gray-800">{{ formatTypeBadge(categoryToDelete.type) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Jenis Anggaran:</span>
                                    <p class="font-medium text-gray-800">{{ formatBudgetTypeBadge(categoryToDelete.budget_type) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <p class="font-medium" :class="categoryToDelete.is_active ? 'text-green-600' : 'text-red-600'">
                                        {{ categoryToDelete.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Dibuat:</span>
                                    <p class="font-medium text-gray-800">{{ new Date(categoryToDelete.created_at).toLocaleDateString('id-ID') }}</p>
                                </div>
                                <div class="xs:col-span-2">
                                    <span class="text-gray-600">Dibuat Oleh:</span>
                                    <p class="font-medium text-gray-800">{{ categoryToDelete.user_id === user.id ? 'Saya' : (categoryToDelete.user?.name || 'Partner') }}</p>
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
/* Utility classes untuk mobile */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Improve scrolling on mobile */
@media (max-width: 640px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .overflow-x-auto::-webkit-scrollbar {
        display: none;
    }
}

/* Breakpoint untuk screen sangat kecil */
@media (max-width: 475px) {
    .xs\:inline {
        display: inline !important;
    }
    
    .xs\:hidden {
        display: none !important;
    }
    
    .xs\:col-span-2 {
        grid-column: span 2 / span 2;
    }
    
    .xs\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Custom scrollbar untuk modal */
.max-h-\[50vh\]::-webkit-scrollbar,
.max-h-\[60vh\]::-webkit-scrollbar {
    width: 4px;
}

.max-h-\[50vh\]::-webkit-scrollbar-track,
.max-h-\[60vh\]::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.max-h-\[50vh\]::-webkit-scrollbar-thumb,
.max-h-\[60vh\]::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f472b6, #60a5fa);
    border-radius: 8px;
}

.max-h-\[50vh\]::-webkit-scrollbar-thumb:hover,
.max-h-\[60vh\]::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ec4899, #3b82f6);
}
</style>