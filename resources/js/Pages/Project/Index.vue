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
const projects = ref([]);
const categories = ref([]);
const projectSummary = ref({});
const loading = ref(false);
const deleting = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingProject = ref(null);
const projectToDelete = ref(null);
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
    category_id: '',
    name: '',
    target_total_amount: 0,
    target_completion_date: '',
    status: 'planning',
});

// Columns untuk tabel
const columns = [
    {
        key: 'name',
        label: 'Nama Proyek',
        icon: '💼',
        sortable: true
    },
    {
        key: 'category',
        label: 'Kategori Tabungan',
        icon: '📁',
        sortable: true
    },
    {
        key: 'target_total_amount',
        label: 'Target Dana',
        icon: '💰',
        sortable: true,
        type: 'currency'
    },
    {
        key: 'target_completion_date',
        label: 'Target Selesai',
        icon: '📅',
        sortable: true,
        type: 'date'
    },
    {
        key: 'status',
        label: 'Status',
        icon: '📊',
        sortable: true,
        type: 'badge'
    },
    {
        key: 'category_user.name',
        label: 'Pemilik Kategori',
        icon: '👤',
        sortable: true
    },
    {
        key: 'created_at',
        label: 'Dibuat',
        icon: '🕒',
        sortable: true,
        type: 'date'
    }
];

// Status options
const statusOptions = [
    { value: 'planning', label: '📋 Planning' },
    { value: 'on_going', label: '🚀 On Going' },
    { value: 'completed', label: '✅ Completed' }
];

// Load data
const loadProjects = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('projects.index'));
        projects.value = response.data.projects || response.data;
        console.log('Projects loaded:', projects.value);
    } catch (error) {
        console.error('Error loading projects:', error);
        flashMessage.value = {
            message: 'Gagal memuat data proyek',
            type: 'error'
        };
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const response = await axios.get(route('projects.api.categories'));
        categories.value = response.data;
        console.log('Categories loaded:', categories.value);
    } catch (error) {
        console.error('Error loading categories:', error);
        flashMessage.value = {
            message: 'Gagal memuat data kategori',
            type: 'error'
        };
    }
};

const loadProjectSummary = async () => {
    try {
        const response = await axios.get(route('projects.api.summary'));
        projectSummary.value = response.data;
        console.log('Project summary loaded:', projectSummary.value);
    } catch (error) {
        console.error('Error loading project summary:', error);
    }
};

// Set default completion date (30 hari dari sekarang)
const setDefaultCompletionDate = () => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 30);
    form.target_completion_date = futureDate.toISOString().split('T')[0];
};

// Actions
const openCreateModal = async () => {
    editingProject.value = null;
    form.reset();
    form.status = 'planning';
    form.target_total_amount = 0;
    setDefaultCompletionDate();
    showModal.value = true;
    
    await loadCategories();
};

const openEditModal = async (project) => {
    // Cek apakah user adalah pemilik kategori
    if (!project.is_owner) {
        flashMessage.value = {
            message: 'Anda hanya dapat mengedit proyek pada kategori yang Anda buat',
            type: 'error'
        };
        return;
    }

    editingProject.value = project;
    form.category_id = project.category_id;
    form.name = project.name;
    form.target_total_amount = project.target_total_amount;
    form.target_completion_date = project.target_completion_date;
    form.status = project.status;
    showModal.value = true;
    
    await loadCategories();
};

const openDeleteModal = (project) => {
    // Cek apakah user adalah pemilik kategori
    if (!project.is_owner) {
        flashMessage.value = {
            message: 'Anda hanya dapat menghapus proyek pada kategori yang Anda buat',
            type: 'error'
        };
        return;
    }

    projectToDelete.value = project;
    showDeleteModal.value = true;
};

const openEncryptedProject = async (project) => {
    try {
    // Belum di gunakan ada error 
        // // Generate encrypted ID via API
        // const response = await axios.post(route('projects.api.encrypt-id'), {
        //     project_id: project.id
        // });
        
        // const encryptedId = response.data.encrypted_id;
        
        // Redirect ke halaman items dengan encrypted ID
        router.visit(route('projects.items.index', { project }));
        
    } catch (error) {
        console.error('Error generating encrypted link:', error);
        flashMessage.value = {
            message: 'Gagal membuka project',
            type: 'error'
        };
    }
};


const saveProject = () => {
    if (editingProject.value) {
        form.put(route('projects.update', editingProject.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadProjects();
                form.reset();
                editingProject.value = null;
                setDefaultCompletionDate();
            },
            onError: (errors) => {
                console.error('Update error:', errors);
            }
        });
    } else {
        form.post(route('projects.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                loadProjects();
                form.reset();
                setDefaultCompletionDate();
            },
            onError: (errors) => {
                console.error('Create error:', errors);
            }
        });
    }
};

const deleteProject = () => {
    if (!projectToDelete.value) return;

    deleting.value = true;

    router.delete(route('projects.destroy', projectToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            loadProjects();
            projectToDelete.value = null;
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
    editingProject.value = null;
    form.clearErrors();
    form.reset();
    setDefaultCompletionDate();
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

const formatStatusBadge = (status) => {
    const statusMap = {
        planning: 'Planning',
        on_going: 'On Going',
        completed: 'Completed'
    };
    return statusMap[status] || status;
};

const formatStatusIcon = (status) => {
    const iconMap = {
        planning: '📋',
        on_going: '🚀',
        completed: '✅'
    };
    return iconMap[status] || '💼';
};

const formatCategoryType = (type) => {
    const typeMap = {
        personal: 'Personal',
        joint: 'Joint'
    };
    return typeMap[type] || type;
};

// Computed untuk stats dari API summary
const totalBudget = computed(() => projectSummary.value.total_budget || 0);
const planningBudget = computed(() => projectSummary.value.planning_budget || 0);
const ongoingBudget = computed(() => projectSummary.value.ongoing_budget || 0);
const completedBudget = computed(() => projectSummary.value.completed_budget || 0);

// Cek apakah user bisa edit/hapus proyek
const canEditProject = (project) => project.is_owner;

onMounted(() => {
    loadProjects();
    loadProjectSummary();
    setDefaultCompletionDate();
});
</script>

<template>
    <AppLayout title="Manajemen Proyek Tabungan">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    💰 Manajemen Proyek Tabungan {{ displayNames }}
                </h2>
                <span class="text-sm text-gray-500 italic">Kelola proyek dan target tabungan bersama 💞</span>
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
                        Manajemen Proyek Tabungan
                    </h1>
                    <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                        Kelola semua proyek tabungan dan rencana keuangan masa depan 
                        <span class="text-blue-500 font-semibold">bersama pasangan</span>. 
                        Pantau progress dan target dana untuk mencapai tujuan finansial 💫
                    </p>
                </div>

                <!-- Stats Cards - Responsive grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                    <!-- Total Budget -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-rose-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-rose-400 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">💰</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ formatCurrency(totalBudget) }}
                            </h3>
                            <p class="text-sm text-gray-600">Total Target Dana</p>
                            <p class="text-xs text-gray-500 mt-1">{{ projectSummary.total_projects || 0 }} Proyek</p>
                        </div>
                    </BaseCard>

                    <!-- Planning Projects -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-blue-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">📋</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ projectSummary.planning_projects || 0 }}
                            </h3>
                            <p class="text-sm text-gray-600">Dalam Perencanaan</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatCurrency(planningBudget) }}</p>
                        </div>
                    </BaseCard>

                    <!-- Ongoing Projects -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-yellow-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">🚀</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ projectSummary.ongoing_projects || 0 }}
                            </h3>
                            <p class="text-sm text-gray-600">Sedang Berjalan</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatCurrency(ongoingBudget) }}</p>
                        </div>
                    </BaseCard>

                    <!-- Completed Projects -->
                    <BaseCard class="text-center hover:scale-[1.02] transition transform border border-green-100">
                        <div class="p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                <span class="text-xl text-white">✅</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                {{ projectSummary.completed_projects || 0 }}
                            </h3>
                            <p class="text-sm text-gray-600">Selesai</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatCurrency(completedBudget) }}</p>
                        </div>
                    </BaseCard>
                </div>

                <!-- Main Table -->
                <BaseTable
                    title="Data Proyek Tabungan"
                    description="Kelola semua proyek tabungan dan target keuangan kita berdua"
                    :columns="columns"
                    :data="projects"
                    :loading="loading"
                    :show-actions="true"
                    :pagination="true"
                    :items-per-page="10"
                    @create="openCreateModal"
                    @edit="openEditModal"
                    @delete="openDeleteModal"
                >
                    <!-- Custom column for status with icon -->
                    <template #column-status="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ formatStatusIcon(item.status) }}</span>
                            <span 
                                class="px-2 py-1 rounded-full text-xs font-medium"
                                :class="item.status_badge"
                            >
                                {{ formatStatusBadge(item.status) }}
                            </span>
                        </div>
                    </template>

                    <!-- Custom column for budget -->
                    <template #column-target_total_amount="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg text-green-600">💰</span>
                            <span class="font-bold text-gray-800 text-sm text-green-700">
                                {{ formatCurrency(item.target_total_amount) }}
                            </span>
                        </div>
                    </template>

                    <!-- Custom column for completion date -->
                    <template #column-target_completion_date="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📅</span>
                            <span class="font-medium text-gray-700 text-sm">{{ formatDate(item.target_completion_date) }}</span>
                        </div>
                    </template>

                    <!-- Custom column for category owner -->
                    <template #column-category_user.name="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ item.is_owner ? '👤' : '👥' }}</span>
                            <span class="font-medium text-gray-700 text-sm" :class="item.is_owner ? 'text-blue-600' : 'text-green-600'">
                                {{ item.is_owner ? 'Saya' : (item.category_user?.name || 'Partner') }}
                            </span>
                            <span class="text-xs px-2 py-1 rounded-full" 
                                  :class="item.is_owner ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">
                                {{ formatCategoryType(item.category_user?.type || 'personal') }}
                            </span>
                        </div>
                    </template>

                    <!-- Custom column for date -->
                    <template #column-created_at="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🕒</span>
                            <span class="font-medium text-gray-700 text-sm">{{ item.created_at }}</span>
                        </div>
                    </template>

                    <!-- Custom actions slot -->
                    <template #actions="{ item }">
                        <BaseButton
                            @click="openEncryptedProject(item)"
                            variant="primary"
                            size="sm"
                            class="!px-3 !py-2"
                            title="Buka project dengan link aman"
                        >
                            <template #icon>🔐</template>
                            Buka Items
                        </BaseButton>
                        <BaseButton
                            @click="openEditModal(item)"
                            variant="secondary"
                            size="sm"
                            class="!px-2 !py-2"
                            :disabled="!canEditProject(item)"
                            :title="!canEditProject(item) ? 'Hanya dapat mengedit proyek pada kategori yang Anda buat' : 'Edit proyek'"
                        >
                            <template #icon>✏️</template>
                            Edit
                        </BaseButton>
                        <BaseButton
                            @click="openDeleteModal(item)"
                            variant="danger"
                            size="sm"
                            class="!px-2 !py-2"
                            :disabled="!canEditProject(item)"
                            :title="!canEditProject(item) ? 'Hanya dapat menghapus proyek pada kategori yang Anda buat' : 'Hapus proyek'"
                        >
                            <template #icon>🗑️</template>
                            Hapus
                        </BaseButton>
                    </template>
                </BaseTable>

                <!-- Empty State CTA -->
                <div v-if="projects.length === 0 && !loading" class="text-center mt-6">
                    <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-lg p-8 border border-gray-100">
                        <div class="text-6xl mb-4">💰</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada proyek tabungan</h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            Mulai dengan membuat proyek tabungan pertama untuk mengelola target keuangan {{ displayNames }}
                        </p>
                        <BaseButton
                            @click="openCreateModal"
                            class="px-6 py-3"
                        >
                            <template #icon>➕</template>
                            Buat Proyek Tabungan Pertama
                        </BaseButton>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <BaseModal
                    v-model:show="showModal"
                    :title="editingProject ? 'Edit Proyek Tabungan' : 'Tambah Proyek Tabungan Baru'"
                    :description="editingProject ? 
                        'Perbarui informasi proyek tabungan kita' : 
                        'Tambahkan proyek tabungan baru untuk mengelola target keuangan bersama'"
                    :icon="editingProject ? '✏️' : '➕'"
                    :confirm-text="editingProject ? 'Perbarui' : 'Simpan'"
                    :confirm-loading="form.processing"
                    @confirm="saveProject"
                    @close="closeModal"
                    size="lg"
                >
                    <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-2">
                        <SelectInput
                            v-model="form.category_id"
                            label="Kategori Tabungan"
                            placeholder="Pilih kategori tabungan"
                            :options="categories.map(cat => ({ 
                                value: cat.id, 
                                label: `${cat.name} ${cat.type === 'joint' ? '👥' : '👤'}`
                            }))"
                            :error="form.errors.category_id"
                            icon="📁"
                            required
                            :disabled="form.processing || categories.length === 0"
                        />

                        <TextInput
                            v-model="form.name"
                            label="Nama Proyek"
                            placeholder="Contoh: Tabungan Pernikahan, Dana DP Rumah, Liburan Keluarga"
                            :error="form.errors.name"
                            icon="💼"
                            required
                            :disabled="form.processing"
                        />

                        <AccountInput
                            v-model="form.target_total_amount"
                            label="Target Total Dana"
                            placeholder="Masukkan target total dana"
                            :error="form.errors.target_total_amount"
                            icon="💰"
                            account-type="account_number"
                            required
                            :disabled="form.processing"
                        />

                        <TextInput
                            v-model="form.target_completion_date"
                            label="Target Tanggal Selesai"
                            type="date"
                            :error="form.errors.target_completion_date"
                            icon="📅"
                            required
                            :disabled="form.processing"
                        />

                        <SelectInput
                            v-model="form.status"
                            label="Status Proyek"
                            placeholder="Pilih status proyek"
                            :options="statusOptions"
                            :error="form.errors.status"
                            icon="📊"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Info Box -->
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200">
                            <p class="text-sm text-gray-700 flex items-start gap-2">
                                <span class="text-lg mt-0.5">💡</span>
                                <span>
                                    <strong class="block">Informasi Proyek Tabungan:</strong>
                                    • Hanya kategori dengan type <strong>savings</strong> yang bisa digunakan<br>
                                    • <strong>Planning:</strong> Proyek dalam tahap perencanaan<br>
                                    • <strong>On Going:</strong> Proyek sedang berjalan dan menabung<br>
                                    • <strong>Completed:</strong> Target tabungan tercapai
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
                    title="Hapus Proyek Tabungan"
                    :description="projectToDelete ? `Apakah Anda yakin ingin menghapus proyek tabungan ${projectToDelete.name}?` : 'Apakah Anda yakin ingin menghapus proyek tabungan ini?'"
                    icon="🗑️"
                    confirm-text="Hapus"
                    cancel-text="Batal"
                    confirm-variant="danger"
                    :confirm-loading="deleting"
                    @confirm="deleteProject"
                    @close="showDeleteModal = false"
                >
                    <div class="space-y-4">
                        <div class="p-4 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-2xl">
                            <p class="text-sm text-red-600 flex items-start gap-2">
                                <span class="text-lg mt-0.5">⚠️</span>
                                <span>
                                    <strong class="block">Tindakan ini tidak dapat dibatalkan!</strong>
                                    Semua data item yang terkait dengan proyek tabungan ini akan ikut terhapus. Pastikan Anda sudah membackup data penting.
                                </span>
                            </p>
                        </div>

                        <div v-if="projectToDelete" class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-2xl">
                            <h4 class="font-semibold text-gray-800 mb-2">Detail Proyek Tabungan:</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Nama:</span>
                                    <p class="font-medium text-gray-800">{{ projectToDelete.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Kategori:</span>
                                    <p class="font-medium text-gray-800">{{ projectToDelete.category }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Target Dana:</span>
                                    <p class="font-bold text-green-700">{{ formatCurrency(projectToDelete.target_total_amount) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Target Selesai:</span>
                                    <p class="font-medium text-gray-800">{{ formatDate(projectToDelete.target_completion_date) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <p class="font-medium text-gray-800">{{ formatStatusBadge(projectToDelete.status) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Pemilik Kategori:</span>
                                    <p class="font-medium text-gray-800">{{ projectToDelete.is_owner ? 'Saya' : (projectToDelete.category_user?.name || 'Partner') }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Item:</span>
                                    <p class="font-medium text-gray-800">{{ projectToDelete.items_count }} item</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Progress:</span>
                                    <p class="font-medium text-gray-800">{{ Math.round(projectToDelete.progress_percentage) }}%</p>
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