<script setup>
// ... kode existing tetap ...
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
      <div class="w-full px-3 sm:px-4 lg:px-8 relative z-10">

        <!-- Hero Section - PERBAIKI FONT SIZE -->
        <div class="text-center mb-4">
          <h1 class="text-xl md:text-3xl lg:text-4xl font-extrabold text-rose-600 drop-shadow-md mb-2 md:mb-3">
            Selamat Datang, {{ displayNames }}
          </h1>
          <p class="text-gray-600 text-sm md:text-lg max-w-2xl mx-auto leading-relaxed">
            Inilah ruang kecil kita untuk menata keuangan dan menyusun impian bersama 💫
          </p>
        </div>

        <!-- Cards Section - PERBAIKI GAP DAN PADDING -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
          <!-- Keuangan Bersama -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-xl md:rounded-2xl p-3 md:p-4 hover:scale-[1.02] transition transform border border-rose-100">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-base md:text-lg text-rose-600">💰 Keuangan</h3>
              <Link :href="route('master-data.accounts.index')" class="text-xs md:text-sm text-rose-400 hover:text-rose-500 font-medium">
                Detail →
              </Link>
            </div>
            <p class="text-gray-600 text-xs md:text-sm mt-2 leading-relaxed">
              Pantau pemasukan dan pengeluaran 💞
            </p>
            <div class="mt-3 md:mt-4 p-3 md:p-4 bg-gradient-to-r from-rose-200 to-pink-100 rounded-lg md:rounded-xl text-center">
              <span class="block text-xs md:text-sm text-gray-500">Saldo Saat Ini</span>
              <span class="text-lg md:text-2xl font-bold text-gray-800 block mt-1">
                {{ dashboardData.totalJointBalance.formatted_amount }}
              </span>
              <p class="text-xs text-gray-600 mt-1">
                Dari {{ dashboardData.totalJointBalance.accounts_count }} akun
              </p>
            </div>
          </div>

          <!-- ... other cards dengan penyesuaian serupa ... -->
        </div>

        <!-- Statistik / Rangkuman - PERBAIKI FONT SIZE -->
        <div class="mt-4 bg-white/80 backdrop-blur-md rounded-xl md:rounded-3xl shadow-lg p-3 md:p-4 border border-gray-100">
          <h3 class="text-rose-500 font-semibold text-base md:text-lg mb-3 md:mb-4">💹 Rangkuman Bulan Ini</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <div class="p-2 rounded-xl bg-gradient-to-r from-green-100 to-emerald-50 text-center">
              <p class="text-gray-600 text-xs md:text-sm">Pemasukan</p>
              <p class="text-base md:text-2xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_income }}
              </p>
            </div>
            <div class="p-2 rounded-xl bg-gradient-to-r from-red-100 to-rose-50 text-center">
              <p class="text-gray-600 text-xs md:text-sm">Pengeluaran</p>
              <p class="text-base md:text-2xl font-bold text-gray-800 mt-1">
                {{ dashboardData.monthlyStats.formatted_expense }}
              </p>
            </div>
            <!-- ... other stat items ... -->
          </div>
          <div class="mt-3 text-center text-xs md:text-sm text-gray-600">
            {{ dashboardData.monthlyStats.transaction_count }} transaksi bulan ini
          </div>
        </div>

        <!-- Informasi Tambahan - PERBAIKI LAYOUT -->
        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-4">
          <!-- Transaksi Terbaru -->
          <div class="bg-white/80 backdrop-blur-md rounded-xl md:rounded-2xl p-3 md:p-4 border border-gray-100">
            <h4 class="font-semibold text-gray-700 text-sm md:text-base mb-3 md:mb-4">💫 Transaksi Terbaru</h4>
            <div class="space-y-2 md:space-y-3 max-h-60 overflow-y-auto">
              <div v-for="transaction in dashboardData.additionalData.recent_transactions" :key="transaction.id" 
                   class="flex items-center justify-between p-2 md:p-3 bg-gray-50 rounded-lg text-xs md:text-sm">
                <div class="flex items-center space-x-2 md:space-x-3">
                  <span :class="{
                    'text-green-500': transaction.type === 'income',
                    'text-red-500': transaction.type === 'expense',
                    'text-blue-500': transaction.type === 'savings'
                  }" class="text-base">
                    {{ transaction.type === 'income' ? '⬆️' : transaction.type === 'expense' ? '⬇️' : '💰' }}
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="font-medium text-gray-800 truncate">{{ transaction.description }}</p>
                    <p class="text-gray-500 truncate">{{ transaction.account?.name }}</p>
                  </div>
                </div>
                <span :class="{
                  'text-green-600': transaction.type === 'income',
                  'text-red-600': transaction.type === 'expense',
                  'text-blue-600': transaction.type === 'savings'
                }" class="font-semibold whitespace-nowrap ml-2">
                  {{ formatCurrency(transaction.amount) }}
                </span>
              </div>
            </div>
          </div>

          <!-- ... other sections dengan penyesuaian serupa ... -->
        </div>

        <!-- CTA - PERBAIKI BUTTON SIZE -->
        <div class="text-center mt-4">
          <h4 class="text-sm md:text-lg text-gray-700 font-medium mb-2">Yuk, tambah rencana baru 💕</h4>
          <Link :href="route('projects.index')"
            class="inline-block px-4 py-2 md:px-6 md:py-3 bg-gradient-to-r from-rose-400 to-pink-400 text-white font-semibold rounded-full shadow-lg hover:scale-105 transition text-sm md:text-base">
            ✨ Buat Rencana
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Tambahkan responsive text */
@media (max-width: 768px) {
  .text-4xl { font-size: 1.5rem; }
  .text-3xl { font-size: 1.25rem; }
  .text-2xl { font-size: 1.125rem; }
}

/* Improve scrolling on mobile */
@media (max-width: 640px) {
  .overflow-y-auto {
    -webkit-overflow-scrolling: touch;
  }
}
</style>