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
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight flex items-center gap-2">
          💞 Dashboard Keuangan {{ displayNames }}
        </h2>
        <span class="text-sm text-gray-500 italic">Menata masa depan bersama, selangkah demi selangkah 💍</span>
      </div>
    </template>

    <div class="py-2 min-h-screen relative overflow-hidden">
      <div class="w-full px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Hero Section -->
        <div class="text-center mb-4">
          <h1 class="text-3xl md:text-4xl font-extrabold text-rose-600 drop-shadow-md mb-3">
            Selamat Datang, {{ displayNames }}
          </h1>
          <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            Inilah ruang kecil kita untuk menata keuangan, menyusun impian, dan mengingat setiap langkah yang kita lalui bersama.
            Karena cinta bukan hanya tentang perasaan, tapi juga rencana yang kita bangun berdua 💫
          </p>
        </div>

        <!-- Cards Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Keuangan Bersama -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-2xl p-4 hover:scale-[1.02] transition transform border border-rose-100">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-lg text-rose-600">💰 Keuangan {{ displayNames }}</h3>
              <Link :href="route('master-data.accounts.index')" class="text-sm text-rose-400 hover:text-rose-500 font-medium">
                Lihat Detail →
              </Link>
            </div>
            <p class="text-gray-600 mt-2">Pantau pemasukan dan pengeluaran agar langkah kita selalu seirama 💞</p>
            <div class="mt-4 p-4 bg-gradient-to-r from-rose-200 to-pink-100 rounded-xl text-center">
              <span class="block text-sm text-gray-500">Saldo Cinta Saat Ini</span>
              <span class="text-2xl font-bold text-gray-800">
                {{ dashboardData.totalJointBalance.formatted_amount }}
              </span>
              <p class="text-xs text-gray-600 mt-1">
                Dari {{ dashboardData.totalJointBalance.accounts_count }} akun bersama
              </p>
            </div>
          </div>

          <!-- Tabungan Impian -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-2xl p-4 hover:scale-[1.02] transition transform border border-sky-100">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-lg text-sky-600">🌈 Tabungan Impian Kita</h3>
              <Link :href="route('projects.index') " class="text-sm text-sky-400 hover:text-sky-500 font-medium">
                Lihat Detail →
              </Link>
            </div>
            <p class="text-gray-600 mt-2">Rencana kecil menuju mimpi besar kita — rumah, perjalanan, dan masa depan bersama 💍</p>
            <div class="mt-4">
              <div class="flex justify-between text-sm text-gray-600">
                <span>Progres</span>
                <span>{{ dashboardData.dreamSavings.progress_percentage }}%</span>
              </div>
              <div class="w-full h-3 bg-gray-200 rounded-full mt-1">
                <div 
                  class="h-3 bg-gradient-to-r from-sky-400 to-blue-400 rounded-full transition-all duration-500" 
                  :style="`width: ${dashboardData.dreamSavings.progress_percentage}%`"
                ></div>
              </div>
              <div class="mt-2 text-xs text-gray-600 flex justify-between">
                <span>Terkumpul: {{ dashboardData.dreamSavings.formatted_actual }}</span>
                <span>Target: {{ dashboardData.dreamSavings.formatted_planned }}</span>
              </div>
            </div>
          </div>

          <!-- Kenangan -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-2xl p-4 hover:scale-[1.02] transition transform border border-purple-100">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-lg text-purple-600">📸 Kenangan {{ displayNames }}</h3>
              <a href="#" class="text-sm text-purple-400 hover:text-purple-500 font-medium">Lihat Galeri →</a>
            </div>
            <p class="text-gray-600 mt-2">Kumpulan momen indah yang mengingatkan kita, kenapa kita berjuang bersama 💖</p>
            <div class="mt-4 grid grid-cols-3 gap-2">
              <div class="h-16 bg-gradient-to-br from-pink-200 to-rose-200 rounded-lg flex items-center justify-center">
                <span class="text-2xl">📷</span>
              </div>
              <div class="h-16 bg-gradient-to-br from-sky-200 to-blue-200 rounded-lg flex items-center justify-center">
                <span class="text-2xl">🎉</span>
              </div>
              <div class="h-16 bg-gradient-to-br from-purple-200 to-pink-100 rounded-lg flex items-center justify-center">
                <span class="text-2xl">💑</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Statistik / Rangkuman -->
        <div class="mt-4 bg-white/80 backdrop-blur-md rounded-3xl shadow-lg p-4 border border-gray-100">
          <h3 class="text-rose-500 font-semibold text-lg mb-4">💹 Rangkuman Keuangan Cinta Bulan Ini</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
            <div class="p-2 rounded-2xl bg-gradient-to-r from-green-100 to-emerald-50 text-center">
              <p class="text-gray-600">Total Pemasukan</p>
              <p class="text-2xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_income }}
              </p>
            </div>
            <div class="p-2 rounded-2xl bg-gradient-to-r from-red-100 to-rose-50 text-center">
              <p class="text-gray-600">Total Pengeluaran</p>
              <p class="text-2xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_expense }}
              </p>
            </div>
            <div class="p-2 rounded-2xl bg-gradient-to-r from-blue-100 to-sky-50 text-center">
              <p class="text-gray-600">Total Tabungan</p>
              <p class="text-2xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_savings }}
              </p>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-r from-purple-100 to-pink-50 text-center">
              <p class="text-gray-600">Saldo Akhir</p>
              <p class="text-2xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_net_balance }}
              </p>
            </div>
          </div>
          <div class="mt-4 text-center text-sm text-gray-600">
            {{ dashboardData.monthlyStats.transaction_count }} transaksi bersama bulan ini
          </div>
        </div>

        <!-- Informasi Tambahan -->
        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
          <!-- Transaksi Terbaru -->
          <div class="bg-white/80 backdrop-blur-md rounded-2xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-700 mb-4">💫 Transaksi Terbaru</h4>
            <div class="space-y-3">
              <div v-for="transaction in dashboardData.additionalData.recent_transactions" :key="transaction.id" 
                   class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                  <span :class="{
                    'text-green-500': transaction.type === 'income',
                    'text-red-500': transaction.type === 'expense',
                    'text-blue-500': transaction.type === 'savings'
                  }">
                    {{ transaction.type === 'income' ? '⬆️' : transaction.type === 'expense' ? '⬇️' : '💰' }}
                  </span>
                  <div>
                    <p class="font-medium text-gray-800">{{ transaction.description }}</p>
                    <p class="text-xs text-gray-500">{{ transaction.account?.name }}</p>
                  </div>
                </div>
                <span :class="{
                  'text-green-600': transaction.type === 'income',
                  'text-red-600': transaction.type === 'expense',
                  'text-blue-600': transaction.type === 'savings'
                }" class="font-semibold">
                  {{ formatCurrency(transaction.amount) }}
                </span>
              </div>
              <div v-if="dashboardData.additionalData.recent_transactions.length === 0" class="text-center text-gray-500 py-4">
                Belum ada transaksi bulan ini
              </div>
            </div>
          </div>

          <!-- Project Mendatang -->
          <div class="bg-white/80 backdrop-blur-md rounded-2xl p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-700 mb-2">🎯 Project Mendatang</h4>
            <div class="space-y-3">
              <div v-for="project in dashboardData.additionalData.upcoming_projects" :key="project.id" 
                   class="p-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium text-gray-800">{{ project.name }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                      Target: {{ formatCurrency(project.target_amount) }}
                    </p>
                  </div>
                  <span :class="{
                    'bg-yellow-100 text-yellow-800': project.status === 'planning',
                    'bg-blue-100 text-blue-800': project.status === 'on_going'
                  }" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ project.status === 'planning' ? '📋 Planning' : '🚀 Berjalan' }}
                  </span>
                </div>
              </div>
              <div v-if="dashboardData.additionalData.upcoming_projects.length === 0" class="text-center text-gray-500 py-4">
                Tidak ada project aktif
              </div>
            </div>
          </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-4">
          <h4 class="text-lg text-gray-700 font-medium mb-2">Yuk, tambah rencana baru bersama 💕</h4>
          <Link :href="route('projects.index')"
            class="inline-block px-4 py-3 bg-gradient-to-r from-rose-400 to-pink-400 text-white font-semibold rounded-full shadow-lg hover:scale-105 transition">
            ✨ Buat Rencana Keuangan {{ displayNames }}
          </Link>
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
</style>