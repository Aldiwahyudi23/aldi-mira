<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePage } from '@inertiajs/vue3'

const { props } = usePage()
const user = props.auth.user
const partnerName = user?.partner?.name ?? 'Pasangan'

// Format tampilan nama pasangan (contoh: Mira 💕 Aldi)
const displayNames = `${user?.name ?? ''} 💕 ${partnerName}`
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

    <div class="py-10 min-h-screen relative overflow-hidden">
     

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Hero Section -->
        <div class="text-center mb-10">
          <h1 class="text-4xl md:text-5xl font-extrabold text-rose-600 drop-shadow-md mb-3">
            Selamat Datang, {{ displayNames }}
          </h1>
          <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            Inilah ruang kecil kita untuk menata keuangan, menyusun impian, dan mengingat setiap langkah yang kita lalui bersama.
            Karena cinta bukan hanya tentang perasaan, tapi juga rencana yang kita bangun berdua 💫
          </p>
        </div>

        <!-- Cards Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Keuangan Bersama -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-2xl p-6 hover:scale-[1.02] transition transform border border-rose-100">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-lg text-rose-600">💰 Keuangan {{ displayNames }}</h3>
              <a href="#" class="text-sm text-rose-400 hover:text-rose-500 font-medium">Lihat Detail →</a>
            </div>
            <p class="text-gray-600 mt-2">Pantau pemasukan dan pengeluaran agar langkah kita selalu seirama 💞</p>
            <div class="mt-4 p-4 bg-gradient-to-r from-rose-200 to-pink-100 rounded-xl text-center">
              <span class="block text-sm text-gray-500">Saldo Cinta Saat Ini</span>
              <span class="text-2xl font-bold text-gray-800">Rp 12.450.000</span>
            </div>
          </div>

          <!-- Tabungan Impian -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-2xl p-6 hover:scale-[1.02] transition transform border border-sky-100">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-lg text-sky-600">🌈 Tabungan Impian Kita</h3>
              <a href="#" class="text-sm text-sky-400 hover:text-sky-500 font-medium">Lihat Detail →</a>
            </div>
            <p class="text-gray-600 mt-2">Rencana kecil menuju mimpi besar kita — rumah, perjalanan, dan masa depan bersama 💍</p>
            <div class="mt-4">
              <div class="flex justify-between text-sm text-gray-600">
                <span>Progres</span>
                <span>75%</span>
              </div>
              <div class="w-full h-3 bg-gray-200 rounded-full mt-1">
                <div class="h-3 bg-gradient-to-r from-sky-400 to-blue-400 rounded-full" style="width: 75%"></div>
              </div>
            </div>
          </div>

          <!-- Kenangan -->
          <div class="bg-white/90 backdrop-blur-md shadow-lg rounded-2xl p-6 hover:scale-[1.02] transition transform border border-purple-100">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-lg text-purple-600">📸 Kenangan {{ displayNames }}</h3>
              <a href="#" class="text-sm text-purple-400 hover:text-purple-500 font-medium">Lihat Galeri →</a>
            </div>
            <p class="text-gray-600 mt-2">Kumpulan momen indah yang mengingatkan kita, kenapa kita berjuang bersama 💖</p>
            <div class="mt-4 grid grid-cols-3 gap-2">
              <div class="h-16 bg-gradient-to-br from-pink-200 to-rose-200 rounded-lg"></div>
              <div class="h-16 bg-gradient-to-br from-sky-200 to-blue-200 rounded-lg"></div>
              <div class="h-16 bg-gradient-to-br from-purple-200 to-pink-100 rounded-lg"></div>
            </div>
          </div>
        </div>

        <!-- Statistik / Rangkuman -->
        <div class="mt-12 bg-white/80 backdrop-blur-md rounded-3xl shadow-lg p-6 border border-gray-100">
          <h3 class="text-rose-500 font-semibold text-lg mb-4">💹 Rangkuman Keuangan Cinta Bulan Ini</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 rounded-2xl bg-gradient-to-r from-pink-100 to-rose-50 text-center">
              <p class="text-gray-600">Total Pemasukan</p>
              <p class="text-2xl font-bold text-gray-800 mt-1">Rp 7.800.000</p>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-100 to-sky-50 text-center">
              <p class="text-gray-600">Total Pengeluaran</p>
              <p class="text-2xl font-bold text-gray-800 mt-1">Rp 3.200.000</p>
            </div>
            <div class="p-4 rounded-2xl bg-gradient-to-r from-green-100 to-emerald-50 text-center">
              <p class="text-gray-600">Saldo Akhir</p>
              <p class="text-2xl font-bold text-gray-800 mt-1">Rp 4.600.000</p>
            </div>
          </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-14">
          <h4 class="text-lg text-gray-700 font-medium mb-3">Yuk, tambah rencana baru bersama 💕</h4>
          <a href="#"
            class="inline-block px-6 py-3 bg-gradient-to-r from-rose-400 to-pink-400 text-white font-semibold rounded-full shadow-lg hover:scale-105 transition">
            ✨ Buat Rencana Keuangan {{ displayNames }}
          </a>
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
