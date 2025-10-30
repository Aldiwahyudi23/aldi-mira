<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'

const { props } = usePage()
const user = props.auth.user
</script>

<template>
  <AppLayout title="Profil Saya">
    <template #header>
      <h2 class="font-semibold text-xl text-pink-700 leading-tight">
        💖 Profil Saya
      </h2>
    </template>

    <div class="py-10  min-h-screen relative overflow-hidden">
      <!-- HEADER PROFIL -->
      <div class="bg-white border border-pink-100 shadow-sm rounded-2xl text-center py-4">
        <div class="relative inline-block">
          <img
            :src="user.profile_photo_url"
            alt="Foto Profil"
            class="w-28 h-28 rounded-full object-cover border-4 border-pink-200 shadow-md mx-auto"
          />
          <span class="absolute bottom-1 right-1 bg-green-400 border border-white rounded-full w-4 h-4"></span>
        </div>
        <h1 class="text-2xl font-semibold text-pink-700 mt-4">{{ user.name }}</h1>
        <p class="text-gray-400 text-sm">{{ user.email }}</p>
      </div>

      <!-- MENU UTAMA -->
      <div class="px-6 py-10">
        <h2 class="text-xl font-semibold text-pink-600 mb-4 flex items-center gap-2">
          <span class="text-2xl">🌷</span> Menu Profil
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
          <!-- Edit Profil -->
          <Link
            href="/user/profile"
            class="group bg-white p-5 rounded-2xl shadow-md hover:shadow-lg border border-pink-100 hover:border-pink-200 transition text-center"
          >
            <div class="text-pink-500 text-3xl mb-2">👩‍❤️‍👨</div>
            <div class="font-semibold text-pink-700 group-hover:text-pink-800">
              Edit Profil
            </div>
            <p class="text-xs text-gray-400 mt-1">Ubah nama, email, & foto</p>
          </Link>

          <!-- Reset Password -->
          <Link
            href="/user/password"
            class="group bg-white p-5 rounded-2xl shadow-md hover:shadow-lg border border-pink-100 hover:border-pink-200 transition text-center"
          >
            <div class="text-pink-500 text-3xl mb-2">🔒</div>
            <div class="font-semibold text-pink-700 group-hover:text-pink-800">
              Reset Password
            </div>
            <p class="text-xs text-gray-400 mt-1">Perbarui kata sandi akun</p>
          </Link>

          <!-- Keluarga -->
          <Link
            href="#"
            class="group bg-white p-5 rounded-2xl shadow-md hover:shadow-lg border border-pink-100 hover:border-pink-200 transition text-center"
          >
            <div class="text-pink-500 text-3xl mb-2">🏡</div>
            <div class="font-semibold text-pink-700 group-hover:text-pink-800">
              Keluarga
            </div>
            <p class="text-xs text-gray-400 mt-1">Lihat & edit data keluarga</p>
          </Link>

          <!-- Rencana Hidup -->
          <Link
            href="#"
            class="group bg-white p-5 rounded-2xl shadow-md hover:shadow-lg border border-pink-100 hover:border-pink-200 transition text-center"
          >
            <div class="text-pink-500 text-3xl mb-2">🧠</div>
            <div class="font-semibold text-pink-700 group-hover:text-pink-800">
              Rencana Hidup
            </div>
            <p class="text-xs text-gray-400 mt-1">Impian dan tujuan bersama</p>
          </Link>
        </div>
      </div>

      <!-- DATA KELUARGA -->
      <div class="px-6 pb-16">
        <h2 class="text-xl font-semibold text-pink-600 mb-4 flex items-center gap-2">
          <span class="text-2xl">💞</span> Data Keluarga
        </h2>

        <div class="bg-white rounded-2xl shadow-md border border-pink-100 p-6">
          <template v-if="user.family">
            <div class="flex justify-between items-center mb-3">
              <span class="font-medium text-gray-600">Nama Keluarga</span>
              <span class="font-semibold text-pink-700">{{ user.family.name }}</span>
            </div>
            <div class="flex justify-between items-center mb-3">
              <span class="font-medium text-gray-600">Kode Undangan</span>
              <span class="text-pink-500 font-mono">*********</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="font-medium text-gray-600">Status</span>
              <span
                class="font-semibold"
                :class="user.family.is_active ? 'text-green-500' : 'text-red-500'"
              >
                {{ user.family.is_active ? 'Aktif' : 'Tidak Aktif' }}
              </span>
            </div>
          </template>

          <template v-else>
            <div class="text-center text-gray-400 italic">
              Belum ada data keluarga 💔  
              <br />
              <Link href="#" class="text-pink-500 hover:underline">Buat keluarga baru</Link>
            </div>
          </template>
        </div>
      </div>

      <!-- FOOTER ROMANTIS -->
      <footer class="text-center py-6 border-t border-pink-100 bg-white/60 text-sm text-gray-500 italic">
        🌸 “Cinta sejati bukan tentang kesempurnaan,  
        tapi tentang bertumbuh bersama dalam setiap kekurangan.” 🌸
      </footer>
    </div>
  </AppLayout>
</template>
