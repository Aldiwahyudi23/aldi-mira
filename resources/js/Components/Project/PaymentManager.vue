<template>
  <div class="payment-manager bg-gradient-to-br from-white via-blue-50 to-indigo-50 rounded-3xl shadow-sm p-4 border border-blue-100">
    <!-- Header dengan Progress -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-2">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-md">
          <span class="text-lg text-white">💰</span>
        </div>
        <div>
          <h3 class="font-bold text-gray-800 text-lg">Tabungan Proyek</h3>
          <p class="text-sm text-gray-600">
            Progress tabungan untuk
            <span class="font-semibold text-blue-600">{{ projectItem.item_name }}</span>
          </p>
        </div>
      </div>

      <!-- Progress Info -->
      <div class="flex items-center gap-4">
          <div class="text-right">
              <!-- Tampilkan informasi sisa/kurang -->
              <div v-if="projectItem.status === 'complete'" class="space-y-1">
                  <div v-if="projectItem.actual_spent > 0" class="text-xs text-green-600 font-medium">
                      💰 Sisa: {{ formatCurrency(projectItem.actual_spent) }}
                  </div>
                  <div v-else-if="projectItem.actual_spent < 0" class="text-xs text-red-600 font-medium">
                      ⚠️ Kurang: {{ formatCurrency(Math.abs(projectItem.actual_spent)) }}
                  </div>
                  <div v-else class="text-xs text-gray-500">
                      ✅ Terpakai semua
                  </div>
              </div>
              <div v-else>
                <div class="text-sm font-semibold text-gray-700 mb-1">
                  {{ formatCurrency(projectItem.actual_spent) }} / {{ formatCurrency(projectItem.planned_amount) }}
              </div>
                  <!-- Progress bar hanya untuk status selain complete -->
                  <div class="w-32 bg-gray-200 rounded-full h-2">
                      <div class="h-2 rounded-full transition-all duration-500"
                          :class="progressBarClass"
                          :style="{ width: Math.min(projectItem.savings_progress, 100) + '%' }"></div>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                      {{ Math.round(projectItem.savings_progress) }}% tercapai
                  </div>
              </div>
          </div>
          
          <!-- Status Badge -->
          <div :class="statusClass" class="px-3 py-1 rounded-full text-sm font-semibold">
              {{ statusText }}
          </div>
      </div>
    </div>

    <!-- Loading State untuk Accounts -->
    <div v-if="loadingAccounts" class="mb-4">
      <div class="bg-white/70 backdrop-blur-sm border border-blue-100 rounded-2xl p-6 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-gray-600">Memuat data akun...</p>
      </div>
    </div>

    <!-- Savings Form (Tampil jika belum ready) -->
    <div v-if="!projectItem.is_ready_for_purchase && projectItem.status !== 'complete' && !loadingAccounts" class="mb-4">
      <div class="bg-white/70 backdrop-blur-sm border border-green-100 rounded-2xl p-4">
        <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <span>💰</span> Tambah Tabungan
        </h4>
        
        <div class="grid grid-cols-2 md:grid-cols-2 gap-2 mb-4">
          <!-- Amount -->
          <AccountInput
            v-model="savingsForm.amount"
            label="Jumlah Tabungan *"
            :placeholder="`Contoh: ${Math.round(projectItem.remaining_amount / 1000) * 1000}`"
            icon="💰"
            account-type="account_number"
            :max="projectItem.remaining_amount"
            :error="savingsErrors.amount"
            required
          />

          <!-- Account Selection -->
          <SelectInput
            v-model="savingsForm.account_id"
            label="Akun Tujuan *"
            :placeholder="accountOptions.length > 0 ? 'Pilih akun untuk menabung' : 'Memuat akun...'"
            :options="accountOptions"
            :error="savingsErrors.account_id"
            icon="🏦"
            :disabled="accountOptions.length === 0"
            required
          />

          <!-- Payment Method -->
          <SelectInput
            v-model="savingsForm.payment_method"
            label="Metode Pembayaran *"
            :options="paymentMethodOptions"
            :error="savingsErrors.payment_method"
            icon="💳"
            required
          />

          <!-- Transaction Date -->
          <DateInput
            v-model="savingsForm.transaction_date"
            label="Tanggal Transaksi *"
            icon="📅"
            :error="savingsErrors.transaction_date"
            required
          />
        </div>

        <!-- Note -->
        <TextAreaInput
          v-model="savingsForm.note"
          label="Catatan (Opsional)"
          placeholder="Contoh: Tabungan gaji bulanan, Bonus, dll..."
          icon="📝"
          :rows="2"
          :show-counter="true"
          :max-length="255"
        />

        <!-- Actions -->
        <div class="flex justify-end mt-4">
          <BaseButton
            @click="addSavings"
            :loading="adding"
            :disabled="!isValidSavings || accountOptions.length === 0"
            class="!px-6 !py-3 bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow-md hover:shadow-lg"
          >
            <template #icon>💰</template>
            Simpan Tabungan
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Purchase Form (Tampil jika ready) -->
    <div v-if="projectItem.is_ready_for_purchase && projectItem.status !== 'complete' && !loadingAccounts" class="mb-6">
      <div class="bg-white/70 backdrop-blur-sm border border-orange-100 rounded-2xl p-4">
        <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <span>🛍️</span> Pembelian Item
        </h4>
        <p class="text-sm text-gray-600 mb-4">
          Tabungan sudah mencukupi! Silakan lakukan pembelian untuk item <strong>{{ projectItem.item_name }}</strong>.
        </p>
        
        <div class="grid grid-cols-2 md:grid-cols-2 gap-2 mb-4">
          <!-- Amount -->
          <AccountInput
            v-model="purchaseForm.amount"
            label="Jumlah Pembelian *"
            :placeholder="`Maksimal: ${formatCurrency(projectItem.actual_spent)}`"
            icon="💰"
            account-type="account_number"
            :max="projectItem.actual_spent"
            :error="purchaseErrors.amount"
            required
          />

          <!-- Account Selection -->
          <SelectInput
            v-model="purchaseForm.account_id"
            label="Akun Sumber *"
            :placeholder="accountOptions.length > 0 ? 'Pilih akun untuk pembelian' : 'Memuat akun...'"
            :options="accountOptions"
            :error="purchaseErrors.account_id"
            icon="🏦"
            :disabled="accountOptions.length === 0"
            required
          />

          <!-- Payment Method -->
          <SelectInput
            v-model="purchaseForm.payment_method"
            label="Metode Pembayaran *"
            :options="paymentMethodOptions"
            :error="purchaseErrors.payment_method"
            icon="💳"
            required
          />

          <!-- Transaction Date -->
          <DateInput
            v-model="purchaseForm.transaction_date"
            label="Tanggal Pembelian *"
            icon="📅"
            :error="purchaseErrors.transaction_date"
            required
          />
        </div>

        <!-- Note -->
        <TextAreaInput
          v-model="purchaseForm.note"
          label="Catatan Pembelian"
          placeholder="Contoh: Beli material, Bayar vendor, dll..."
          icon="📝"
          :rows="2"
          :show-counter="true"
          :max-length="255"
        />

        <!-- Actions -->
        <div class="flex justify-end mt-4">
          <BaseButton
            @click="makePurchase"
            :loading="purchasing"
            :disabled="!isValidPurchase || accountOptions.length === 0"
            class="!px-6 !py-3 bg-gradient-to-r from-orange-400 to-red-500 text-white shadow-md hover:shadow-lg"
          >
            <template #icon>🛍️</template>
            Proses Pembelian
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- complete State -->
    <div v-if="projectItem.status === 'complete'" class="mb-4">
      <div class="bg-white/70 backdrop-blur-sm border border-green-100 rounded-2xl p-6 text-center">
        <div class="text-4xl mb-4">🎉</div>
        <h4 class="text-xl font-bold text-gray-800 mb-2">Pembelian Selesai!</h4>
        <p class="text-gray-600 mb-4">
          Item <strong>{{ projectItem.item_name }}</strong> telah berhasil dibeli dengan total 
          <strong>{{ formatCurrency(projectItem.actual_spent) }}</strong>.
        </p>
        <div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg">
          <p class="text-sm">Status: <strong>Completed</strong></p>
        </div>
      </div>
    </div>

    <!-- Payments History -->
    <div class="mt-4">
      <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <span>📊</span> Riwayat Transaksi
      </h4>

      <!-- Empty State -->
      <div
        v-if="payments.length === 0"
        class="text-center py-4 bg-white/60 backdrop-blur-sm rounded-2xl border border-blue-100"
      >
        <div class="text-6xl mb-2">💸</div>
        <h4 class="text-lg font-semibold text-gray-700 mb-2">Belum ada transaksi</h4>
        <p class="text-gray-600 max-w-md mx-auto">
          Mulai menabung untuk proyek ini 💖
        </p>
      </div>

      <!-- Payments List -->
      <div v-else class="space-y-3">
        <div
          v-for="payment in payments"
          :key="payment.id"
          class="group bg-white/80 backdrop-blur-sm rounded-2xl p-4 border-2 transition-all duration-300 hover:shadow-lg"
          :class="payment.amount > 0 ? 'border-green-100' : 'border-red-100'"
        >
          <div
            class="flex flex-row items-center justify-between gap-3 bg-white/70 border border-pink-100 rounded-2xl p-3 group transition-all flex-wrap sm:flex-nowrap"
          >
            <!-- 💖 Payment Info -->
            <div class="flex items-center gap-3 flex-1 min-w-[200px]">
              <div 
                class="w-8 h-8 rounded-lg flex items-center justify-center text-white flex-shrink-0"
                :class="payment.amount > 0 ? 'bg-green-500' : 'bg-red-500'"
              >
                <span class="text-sm">{{ payment.amount > 0 ? '💰' : '🛍️' }}</span>
              </div>

              <div class="flex flex-col justify-center truncate">
                <div class="flex items-center flex-wrap gap-2 mb-1">
                  <h4 class="font-semibold text-gray-800 truncate max-w-[160px] sm:max-w-[200px]">
                    {{ payment.amount > 0 ? 'Tabungan' : 'Pembelian' }}
                  </h4>
                  <span
                    class="px-2 py-1 text-xs rounded-full font-medium whitespace-nowrap"
                    :class="payment.amount > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  >
                    {{ formatPaymentMethod(payment.payment_method) }}
                  </span>
                </div>

                <div class="text-sm text-gray-600 space-y-1 truncate">
                  <p v-if="payment.transaction">
                    <span class="font-medium">Akun:</span> {{ payment.transaction.account.name }}
                  </p>
                  <p v-if="payment.note" class="text-blue-600 truncate">📝 {{ payment.note }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(payment.created_at) }}</p>
                </div>
              </div>
            </div>

            <!-- 💸 Amount & Actions -->
            <div class="flex items-center gap-3 flex-shrink-0">
              <div class="text-right">
                <div
                  class="text-lg font-bold"
                  :class="payment.amount > 0 ? 'text-green-600' : 'text-red-600'"
                >
                  {{ payment.amount > 0 ? '+' : '' }}{{ formatCurrency(payment.amount) }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ formatDate(payment.created_at, 'time') }}
                </div>
              </div>

              <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                <BaseButton
                  @click="deletePayment(payment)"
                  class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                  title="Hapus transaksi"
                >
                  🗑️
                </BaseButton>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <BaseModal
      v-model:show="showDeleteModal"
      title="Hapus Transaksi"
      :description="paymentToDelete ? `Apakah Anda yakin ingin menghapus transaksi ini?` : 'Apakah Anda yakin ingin menghapus transaksi ini?'"
      icon="🗑️"
      confirm-text="Hapus"
      cancel-text="Batal"
      confirm-variant="danger"
      :confirm-loading="deleting"
      @confirm="confirmDelete"
      @close="showDeleteModal = false"
    >
      <div class="space-y-4">
        <div class="p-4 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-2xl">
          <p class="text-sm text-red-600 flex items-start gap-2">
            <span class="text-lg mt-0.5">⚠️</span>
            <span>
              <strong class="block">Tindakan ini tidak dapat dibatalkan!</strong>
              Transaksi yang dihapus akan mempengaruhi saldo akun dan progress tabungan.
            </span>
          </p>
        </div>

        <div v-if="paymentToDelete" class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-2xl">
          <h4 class="font-semibold text-gray-800 mb-2">Detail Transaksi:</h4>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-600">Jenis:</span>
              <p class="font-medium text-gray-800">
                {{ paymentToDelete.amount > 0 ? 'Tabungan' : 'Pembelian' }}
              </p>
            </div>
            <div>
              <span class="text-gray-600">Jumlah:</span>
              <p class="font-bold" :class="paymentToDelete.amount > 0 ? 'text-green-600' : 'text-red-600'">
                {{ formatCurrency(paymentToDelete.amount) }}
              </p>
            </div>
            <div>
              <span class="text-gray-600">Akun:</span>
              <p class="font-medium text-gray-800">{{ paymentToDelete.transaction?.account?.name }}</p>
            </div>
            <div>
              <span class="text-gray-600">Metode:</span>
              <p class="font-medium text-gray-800">{{ formatPaymentMethod(paymentToDelete.payment_method) }}</p>
            </div>
            <div>
              <span class="text-gray-600">Tanggal:</span>
              <p class="font-medium text-gray-800">{{ formatDate(paymentToDelete.created_at) }}</p>
            </div>
            <div v-if="paymentToDelete.note">
              <span class="text-gray-600">Catatan:</span>
              <p class="font-medium text-gray-800">{{ paymentToDelete.note }}</p>
            </div>
          </div>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3';
import BaseButton from '../Base/BaseButton.vue'
import BaseModal from '../Base/BaseModal.vue'
import AccountInput from '../Form/AccountInput.vue'
import SelectInput from '../Form/SelectInput.vue'
import TextAreaInput from '../Form/TextAreaInput.vue'
import DateInput from '../Form/DateInput.vue'

const props = defineProps({
  projectItem: Object,
})

const emits = defineEmits(['payment-updated'])

// Data dan state
const payments = ref([])
const accounts = ref([])
const loadingAccounts = ref(false)
const adding = ref(false)
const purchasing = ref(false)
const deleting = ref(false)
const showDeleteModal = ref(false)
const paymentToDelete = ref(null)

// Forms
const savingsForm = ref({
  amount: '',
  account_id: '',
  payment_method: 'transfer',
  transaction_date: new Date().toISOString().split('T')[0],
  note: ''
})

const purchaseForm = ref({
  amount: '',
  account_id: '',
  payment_method: 'transfer',
  transaction_date: new Date().toISOString().split('T')[0],
  note: ''
})

// Computed properties
const statusText = computed(() => {
  const statusMap = {
    'pending': 'Belum Dimulai',
    'in_progress': 'Dalam Progress',
    'ready': 'Siap Belanja',
    'complete': 'Selesai'
  }
  return statusMap[props.projectItem.status] || props.projectItem.status
})

const statusClass = computed(() => {
  const classMap = {
    'pending': 'bg-gray-100 text-gray-800',
    'in_progress': 'bg-blue-100 text-blue-800',
    'ready': 'bg-orange-100 text-orange-800',
    'complete': 'bg-green-100 text-green-800'
  }
  return classMap[props.projectItem.status] || 'bg-gray-100 text-gray-800'
})

const progressBarClass = computed(() => {
  if (props.projectItem.status === 'complete') return 'bg-green-500'
  if (props.projectItem.status === 'ready') return 'bg-orange-500'
  return 'bg-blue-500'
})

// Account options untuk select input
const accountOptions = computed(() => {
  if (!accounts.value.length) return []

  return accounts.value.map(account => ({
    value: account.id,
    label: `${account.name} - ${account.formatted_balance}${account.type === 'joint' ? ' 👥' : ' 👤'}`,
    // Optional: tambahkan metadata untuk styling khusus
    metadata: {
      type: account.type,
      balance: account.current_balance,
      balance_status: account.balance_status,
      balance_status_color: account.balance_status_color,
      balance_status_text: account.balance_status_text
    }
  }))
})

// Payment method options
const paymentMethodOptions = computed(() => [
  { value: 'transfer', label: 'Transfer Bank' },
  { value: 'cash', label: 'Tunai' }
])

// Validation
const savingsErrors = computed(() => {
  const errors = {}
  if (!savingsForm.value.amount || savingsForm.value.amount <= 0) {
    errors.amount = 'Jumlah tabungan harus lebih dari 0'
  } else if (savingsForm.value.amount > props.projectItem.remaining_amount) {
    errors.amount = `Jumlah tabungan tidak boleh melebihi ${formatCurrency(props.projectItem.remaining_amount)}`
  }
  if (!savingsForm.value.account_id) {
    errors.account_id = 'Akun tujuan harus dipilih'
  }
  if (!savingsForm.value.payment_method) {
    errors.payment_method = 'Metode pembayaran harus dipilih'
  }
  if (!savingsForm.value.transaction_date) {
    errors.transaction_date = 'Tanggal transaksi harus diisi'
  }
  return errors
})

const purchaseErrors = computed(() => {
  const errors = {}
  if (!purchaseForm.value.amount || purchaseForm.value.amount <= 0) {
    errors.amount = 'Jumlah pembelian harus lebih dari 0'
  } else if (purchaseForm.value.amount > props.projectItem.actual_spent) {
    errors.amount = `Jumlah pembelian tidak boleh melebihi ${formatCurrency(props.projectItem.actual_spent)}`
  }
  if (!purchaseForm.value.account_id) {
    errors.account_id = 'Akun sumber harus dipilih'
  }
  if (!purchaseForm.value.payment_method) {
    errors.payment_method = 'Metode pembayaran harus dipilih'
  }
  if (!purchaseForm.value.transaction_date) {
    errors.transaction_date = 'Tanggal pembelian harus diisi'
  }
  return errors
})

const isValidSavings = computed(() => {
  return Object.keys(savingsErrors.value).length === 0 && 
         savingsForm.value.amount > 0 &&
         savingsForm.value.account_id &&
         savingsForm.value.payment_method &&
         savingsForm.value.transaction_date
})

const isValidPurchase = computed(() => {
  return Object.keys(purchaseErrors.value).length === 0 && 
         purchaseForm.value.amount > 0 &&
         purchaseForm.value.account_id &&
         purchaseForm.value.payment_method &&
         purchaseForm.value.transaction_date
})

// Methods
const loadPayments = async () => {
  try {
    const response = await axios.get(route('project-items.payments.index', {
      projectItem: props.projectItem.id
    }))
    if (response.data.success) {
      payments.value = response.data.payments
      Object.assign(props.projectItem, response.data.project_item) // ⬅️ penting
    }
  } catch (error) {
    console.error('Error loading payments:', error)
  }
}

// Load accounts dari API
const loadAccounts = async () => {
  loadingAccounts.value = true
  try {
    // Coba panggil route khusus project payments dulu
    const response = await axios.get(route('project-items.api.accounts'))
    accounts.value = response.data
    console.log('Accounts loaded from project API:', accounts.value)
  } catch (error) {
    console.error('Error loading accounts from project API:', error)
    // Fallback ke API umum
    try {
      const fallbackResponse = await axios.get('/api/accounts')
      accounts.value = fallbackResponse.data
      console.log('Accounts loaded from fallback API:', accounts.value)
    } catch (fallbackError) {
      console.error('Fallback also failed:', fallbackError)
      // Tampilkan error message
      alert('Gagal memuat data akun. Silakan refresh halaman.')
    }
  } finally {
    loadingAccounts.value = false
  }
}

const addSavings = async () => {
  if (!isValidSavings.value) return
  adding.value = true
  try {
    const response = await axios.post(route('project-items.payments.store', {
      projectItem: props.projectItem.id
    }), savingsForm.value)

    if (response.data.success) {
      payments.value.unshift(response.data.payment)
      Object.assign(props.projectItem, response.data.project_item)
      resetSavingsForm()
      emits('payment-updated')
    }
  } catch (error) {
    console.error('Error adding savings:', error)
    alert(error.response?.data?.error || 'Gagal menambah tabungan')
  } finally {
    adding.value = false
  }
}

const makePurchase = async () => {
  if (!isValidPurchase.value) return
  purchasing.value = true
  try {
    const response = await axios.post(route('project-items.payments.purchase', {
      projectItem: props.projectItem.id
    }), purchaseForm.value)

    if (response.data.success) {
      payments.value.unshift(response.data.payment)
      Object.assign(props.projectItem, response.data.project_item)
      resetPurchaseForm()
      emits('payment-updated')
    }
  } catch (error) {
    console.error('Error making purchase:', error)
    alert(error.response?.data?.error || 'Gagal melakukan pembelian')
  } finally {
    purchasing.value = false
  }
}

const deletePayment = (payment) => {
  paymentToDelete.value = payment
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  if (!paymentToDelete.value) return
  
  deleting.value = true
  try {
    const response = await axios.delete(route('project-items.payments.destroy', {
      projectItem: props.projectItem.id,
      payment: paymentToDelete.value.id
    }))
    
    if (response.data.success) {
      payments.value = payments.value.filter(p => p.id !== paymentToDelete.value.id)
      // Reload project item data
      await loadPayments()
      emits('payment-updated')
    }
  } catch (error) {
    console.error('Error deleting payment:', error)
    alert('Gagal menghapus transaksi')
  } finally {
    deleting.value = false
    showDeleteModal.value = false
    paymentToDelete.value = null
  }
}

const resetSavingsForm = () => {
  savingsForm.value = {
    amount: '',
    account_id: '',
    payment_method: 'transfer',
    transaction_date: new Date().toISOString().split('T')[0],
    note: ''
  }
}

const resetPurchaseForm = () => {
  purchaseForm.value = {
    amount: '',
    account_id: '',
    payment_method: 'transfer',
    transaction_date: new Date().toISOString().split('T')[0],
    note: ''
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Math.abs(amount))
}

const formatDate = (date, type = 'full') => {
  const dateObj = new Date(date)
  if (type === 'time') {
    return dateObj.toLocaleTimeString('id-ID', {
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  return dateObj.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPaymentMethod = (method) => {
  const methods = {
    'transfer': 'Transfer',
    'cash': 'Tunai'
  }
  return methods[method] || method
}

// Computed properties
const remainingAmount = computed(() => {
    return props.projectItem.planned_amount - props.projectItem.actual_spent
})



// Lifecycle
onMounted(() => {
  loadPayments()
  loadAccounts()
})
</script>

<style scoped>
.payment-manager::-webkit-scrollbar {
  width: 6px;
}
.payment-manager::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #60a5fa, #4f46e5);
  border-radius: 10px;
}
</style>