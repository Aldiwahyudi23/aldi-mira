<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePage, Link } from '@inertiajs/vue3'

const { props } = usePage()
const user = props.auth.user
const dashboardData = props.dashboardData
const partnerName = user?.partner?.name ?? 'Pasangan'

// Format tampilan nama pasangan
const displayNames = `${user?.name ?? ''} 💕 ${partnerName}`

// Helper function untuk format currency
const formatCurrency = (amount) => {
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount)
}

// Helper untuk status project
const getStatusText = (status) => {
  const statusMap = {
    'planning': '📋 Planning',
    'on_going': '🚀 Berjalan', 
    'completed': '✅ Selesai',
    'cancelled': '❌ Dibatalkan'
  }
  return statusMap[status] || status
}

// Helper untuk type transaksi
const getTransactionTypeIcon = (type) => {
  const iconMap = {
    'income': '⬆️',
    'expense': '⬇️',
    'savings': '💰'
  }
  return iconMap[type] || '🔹'
}

const getTransactionTypeColor = (type) => {
  const colorMap = {
    'income': 'text-green-600',
    'expense': 'text-red-600',
    'savings': 'text-blue-600'
  }
  return colorMap[type] || 'text-gray-600'
}
</script>

<template>
  <AppLayout title="Beranda">
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
        <h2 class="font-semibold text-xl md:text-2xl text-gray-800 leading-tight flex items-center gap-2">
          💞 Dashboard {{ displayNames }}
        </h2>
        <span class="text-xs md:text-sm text-gray-500 italic text-center md:text-right">
          Menata masa depan bersama 💍
        </span>
      </div>
    </template>

    <div class="py-2 min-h-screen relative overflow-hidden">
      <!-- =============================================================== -->
      <!-- PERBAIKAN: Padding lebih kecil untuk mobile -->
      <div class="w-full px-3 sm:px-4 lg:px-6 relative z-10">
        <!-- =============================================================== -->

        <!-- Hero Section -->
        <div class="text-center mb-4">
          <h1 class="text-xl md:text-2xl lg:text-3xl font-extrabold text-rose-600 drop-shadow-md mb-2 md:mb-3">
            Selamat Datang, {{ displayNames }}
          </h1>
          <!-- =============================================================== -->
          <p class="text-gray-600 text-xs md:text-sm leading-relaxed px-2">
            Inilah ruang kecil kita untuk menata keuangan, menyusun impian, dan mengingat setiap langkah yang kita lalui bersama.
          </p>
          <!-- =============================================================== -->
        </div>

        <!-- Cards Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
          <!-- Keuangan Bersama -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-rose-100">
            <div class="flex items-center justify-between">
              <!-- =============================================================== -->
              <h3 class="font-semibold text-sm md:text-base text-rose-600 truncate flex-1 mr-2">💰 Keuangan</h3>
              <!-- =============================================================== -->
              <Link :href="route('master-data.accounts.index')" class="text-xs md:text-sm text-rose-400 hover:text-rose-500 font-medium whitespace-nowrap">
                Detail →
              </Link>
            </div>
            <!-- =============================================================== -->
            <p class="text-gray-600 text-xs md:text-sm mt-2 leading-relaxed line-clamp-2">
              Pantau pemasukan dan pengeluaran agar langkah kita selalu seirama 💞
            </p>
            <!-- =============================================================== -->
            <div class="mt-3 md:mt-4 p-3 md:p-4 bg-gradient-to-r from-rose-200 to-pink-100 rounded-lg md:rounded-xl text-center">
              <!-- =============================================================== -->
              <span class="block text-xs md:text-sm text-gray-500">Saldo Saat Ini</span>
              <span class="text-lg md:text-xl lg:text-2xl font-bold text-gray-800 block mt-1">
                {{ dashboardData.totalJointBalance.formatted_amount }}
              </span>
              <!-- =============================================================== -->
              <p class="text-xs text-gray-600 mt-1">
                Dari {{ dashboardData.totalJointBalance.accounts_count }} akun
              </p>
            </div>
          </div>

          <!-- Tabungan Impian -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-sky-100">
            <div class="flex items-center justify-between">
              <!-- =============================================================== -->
              <h3 class="font-semibold text-sm md:text-base text-sky-600 truncate flex-1 mr-2">🌈 Tabungan Impian</h3>
              <!-- =============================================================== -->
              <Link :href="route('projects.index')" class="text-xs md:text-sm text-sky-400 hover:text-sky-500 font-medium whitespace-nowrap">
                Detail →
              </Link>
            </div>
            <!-- =============================================================== -->
            <p class="text-gray-600 text-xs md:text-sm mt-2 leading-relaxed line-clamp-2">
              Rencana kecil menuju mimpi besar kita — rumah, perjalanan, dan masa depan bersama 💍
            </p>
            <!-- =============================================================== -->
            <div class="mt-3 md:mt-4">
              <div class="flex justify-between text-xs md:text-sm text-gray-600">
                <span>Progres</span>
                <span>{{ dashboardData.dreamSavings.progress_percentage }}%</span>
              </div>
              <div class="w-full h-2 md:h-3 bg-gray-200 rounded-full mt-1">
                <div 
                  class="h-2 md:h-3 bg-gradient-to-r from-sky-400 to-blue-400 rounded-full transition-all duration-500" 
                  :style="`width: ${dashboardData.dreamSavings.progress_percentage}%`"
                ></div>
              </div>
              <!-- =============================================================== -->
              <div class="mt-2 text-xs text-gray-600 flex flex-col md:flex-row md:justify-between gap-1">
                <span>Terkumpul: {{ dashboardData.dreamSavings.formatted_actual }}</span>
                <span>Target: {{ dashboardData.dreamSavings.formatted_planned }}</span>
              </div>
              <!-- =============================================================== -->
            </div>
          </div>

          <!-- Kenangan -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-purple-100">
            <div class="flex items-center justify-between">
              <!-- =============================================================== -->
              <h3 class="font-semibold text-sm md:text-base text-purple-600 truncate flex-1 mr-2">📸 Kenangan</h3>
              <!-- =============================================================== -->
              <a href="#" class="text-xs md:text-sm text-purple-400 hover:text-purple-500 font-medium whitespace-nowrap">
                Detail →
              </a>
            </div>
            <!-- =============================================================== -->
            <p class="text-gray-600 text-xs md:text-sm mt-2 leading-relaxed line-clamp-2">
              Kumpulan momen indah yang mengingatkan kita, kenapa kita berjuang bersama 💖
            </p>
            <!-- =============================================================== -->
            <div class="mt-3 md:mt-4 grid grid-cols-3 gap-2">
              <!-- =============================================================== -->
              <div class="h-12 md:h-16 bg-gradient-to-br from-pink-200 to-rose-200 rounded-lg flex items-center justify-center">
                <span class="text-lg md:text-2xl">📷</span>
              </div>
              <div class="h-12 md:h-16 bg-gradient-to-br from-sky-200 to-blue-200 rounded-lg flex items-center justify-center">
                <span class="text-lg md:text-2xl">🎉</span>
              </div>
              <div class="h-12 md:h-16 bg-gradient-to-br from-purple-200 to-pink-100 rounded-lg flex items-center justify-center">
                <span class="text-lg md:text-2xl">💑</span>
              </div>
              <!-- =============================================================== -->
            </div>
          </div>
        </div>

        <!-- Statistik / Rangkuman -->
        <!-- =============================================================== -->
        <div class="mt-4 bg-white/80 backdrop-blur-md rounded-xl md:rounded-2xl shadow-lg p-3 md:p-4 border border-gray-100">
          <h3 class="text-rose-500 font-semibold text-base md:text-lg mb-3 md:mb-4">💹 Rangkuman Bulan Ini</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-3">
            <div class="p-2 md:p-3 rounded-lg md:rounded-xl bg-gradient-to-r from-green-100 to-emerald-50 text-center">
              <!-- =============================================================== -->
              <p class="text-gray-600 text-xs md:text-sm">Pemasukan</p>
              <p class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_income }}
              </p>
              <!-- =============================================================== -->
            </div>
            <div class="p-2 md:p-3 rounded-lg md:rounded-xl bg-gradient-to-r from-red-100 to-rose-50 text-center">
              <!-- =============================================================== -->
              <p class="text-gray-600 text-xs md:text-sm">Pengeluaran</p>
              <p class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_expense }}
              </p>
              <!-- =============================================================== -->
            </div>
            <div class="p-2 md:p-3 rounded-lg md:rounded-xl bg-gradient-to-r from-blue-100 to-sky-50 text-center">
              <!-- =============================================================== -->
              <p class="text-gray-600 text-xs md:text-sm">Tabungan</p>
              <p class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_savings }}
              </p>
              <!-- =============================================================== -->
            </div>
            <div class="p-2 md:p-3 rounded-lg md:rounded-xl bg-gradient-to-r from-purple-100 to-pink-50 text-center">
              <!-- =============================================================== -->
              <p class="text-gray-600 text-xs md:text-sm">Saldo Akhir</p>
              <p class="text-base md:text-lg lg:text-xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_net_balance }}
              </p>
              <!-- =============================================================== -->
            </div>
          </div>
          <!-- =============================================================== -->
          <div class="mt-3 text-center text-xs md:text-sm text-gray-600">
            {{ dashboardData.monthlyStats.transaction_count }} transaksi bulan ini
          </div>
          <!-- =============================================================== -->
        </div>

        <!-- Informasi Tambahan -->
        <!-- =============================================================== -->
        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-4">
          <!-- Transaksi Terbaru -->
          <div class="bg-white/80 backdrop-blur-md rounded-xl md:rounded-2xl p-3 md:p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-700 text-sm md:text-base mb-3 md:mb-4">💫 Transaksi Terbaru</h4>
            <div class="space-y-2 md:space-y-3 max-h-48 md:max-h-60 overflow-y-auto">
              <!-- =============================================================== -->
              <div v-for="transaction in dashboardData.additionalData.recent_transactions" :key="transaction.id" 
                   class="flex items-center justify-between p-2 md:p-3 bg-gray-50 rounded-lg text-xs md:text-sm">
                <div class="flex items-center space-x-2 md:space-x-3 min-w-0 flex-1">
                  <!-- =============================================================== -->
                  <span :class="{
                    'text-green-500': transaction.type === 'income',
                    'text-red-500': transaction.type === 'expense',
                    'text-blue-500': transaction.type === 'savings'
                  }" class="text-base flex-shrink-0">
                    {{ transaction.type === 'income' ? '⬆️' : transaction.type === 'expense' ? '⬇️' : '💰' }}
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="font-medium text-gray-800 truncate">{{ transaction.description }}</p>
                    <p class="text-gray-500 truncate text-xs">{{ transaction.account?.name }}</p>
                  </div>
                  <!-- =============================================================== -->
                </div>
                <span :class="{
                  'text-green-600': transaction.type === 'income',
                  'text-red-600': transaction.type === 'expense',
                  'text-blue-600': transaction.type === 'savings'
                }" class="font-semibold whitespace-nowrap ml-2 text-xs md:text-sm">
                  {{ formatCurrency(transaction.amount) }}
                </span>
              </div>
              <!-- =============================================================== -->
              <div v-if="dashboardData.additionalData.recent_transactions.length === 0" class="text-center text-gray-500 py-4 text-sm">
                Belum ada transaksi bulan ini
              </div>
            </div>
          </div>

          <!-- Project Mendatang -->
          <div class="bg-white/80 backdrop-blur-md rounded-xl md:rounded-2xl p-3 md:p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-700 text-sm md:text-base mb-2 md:mb-4">🎯 Project Mendatang</h4>
            <div class="space-y-2 md:space-y-3 max-h-48 md:max-h-60 overflow-y-auto">
              <!-- =============================================================== -->
              <div v-for="project in dashboardData.additionalData.upcoming_projects" :key="project.id" 
                   class="p-2 md:p-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg text-xs md:text-sm">
                <!-- =============================================================== -->
                <div class="flex justify-between items-start gap-2">
                  <div class="min-w-0 flex-1">
                    <p class="font-medium text-gray-800 truncate">{{ project.name }}</p>
                    <p class="text-gray-500 mt-1 truncate">
                      Target: {{ formatCurrency(project.target_amount) }}
                    </p>
                  </div>
                  <span :class="{
                    'bg-yellow-100 text-yellow-800': project.status === 'planning',
                    'bg-blue-100 text-blue-800': project.status === 'on_going'
                  }" class="px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap flex-shrink-0">
                    {{ project.status === 'planning' ? '📋' : '🚀' }}
                  </span>
                </div>
                <!-- =============================================================== -->
              </div>
              <div v-if="dashboardData.additionalData.upcoming_projects.length === 0" class="text-center text-gray-500 py-4 text-sm">
                Tidak ada project aktif
              </div>
            </div>
          </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-4">
          <!-- =============================================================== -->
          <h4 class="text-sm md:text-base text-gray-700 font-medium mb-2">Yuk, tambah rencana baru bersama 💕</h4>
          <Link :href="route('projects.index')"
            class="inline-block px-4 py-2 md:px-6 md:py-3 bg-gradient-to-r from-rose-400 to-pink-400 text-white font-semibold rounded-full shadow-lg hover:scale-105 transition text-sm md:text-base whitespace-nowrap">
            ✨ Buat Rencana
          </Link>
          <!-- =============================================================== -->
        </div>
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

/* =============================================================== */
/* PERBAIKAN: Tambahan utility classes untuk mobile */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Improve scrolling on mobile */
@media (max-width: 640px) {
  .overflow-y-auto {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  
  .overflow-y-auto::-webkit-scrollbar {
    display: none;
  }
}
/* =============================================================== */
</style>