<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'

defineProps({
  title: String,
})

// 🌟 Ambil flash message dari page props
const flashMessage = computed(() => {
  return usePage().props.flash?.message || null
})

// 🌟 Function untuk close flash message
const closeFlashMessage = () => {
  usePage().props.flash.message = null
}

// Ambil data user dari Inertia
const { url, props } = usePage()
const user = props.auth?.user || { 
  name: 'Guest', 
  profile_photo_url: '/images/default-avatar.png',
  theme: null
}

// 🌈 Default theme (kalau user.theme kosong) - SESUAI KODE AWAL
const defaultTheme = {
  background: 'bg-gradient-to-br from-pink-300 via-white to-sky-300',
  navbar: 'bg-white/60',
  text: 'text-gray-700',
  logoGradient: 'bg-gradient-to-r from-pink-500 to-sky-500',
  nameGradient: 'text-gray-700',
  menuActive: 'bg-gradient-to-r from-pink-400 to-sky-400',
  buttonGradient: 'bg-gradient-to-r from-pink-400 to-sky-400 text-white',
  mobileMenu: {
    background: 'bg-white/80',
    text: 'text-gray-500',
    textActive: 'text-pink-500',
    border: 'border-pink-200'
  }
}

// Parsing JSON theme user (jika ada)
const userTheme = computed(() => {
  try {
    return user.theme ? JSON.parse(user.theme) : null
  } catch {
    return null
  }
})

// Computed untuk class CSS berdasarkan theme
const themeClasses = computed(() => {
  // Jika tidak ada theme dari user, gunakan default theme
  if (!userTheme.value) {
    return defaultTheme
  }

  const t = userTheme.value
  
  // Pastikan semua properti ada, jika tidak gunakan default
  return {
    background: t.background ? `bg-gradient-to-br from-${t.background.from}-${t.background.shade_from} via-white to-${t.background.to}-${t.background.shade_to}` : defaultTheme.background,
    navbar: t.navbar ? `bg-gradient-to-r from-${t.navbar.from}-${t.navbar.shade_from} to-${t.navbar.to}-${t.navbar.shade_to}` : defaultTheme.navbar,
    logoGradient: t.navbar ? `bg-gradient-to-r from-${t.navbar.logo_color_from}-${t.navbar.logo_color_shade_from} to-${t.navbar.logo_color_to}-${t.navbar.logo_color_shade_to}` : defaultTheme.logoGradient,
    nameGradient: t.navbar ? `bg-gradient-to-r from-${t.navbar.name_color_from}-${t.navbar.name_color_shade_from} to-${t.navbar.name_color_to}-${t.navbar.name_color_shade_to}` : defaultTheme.nameGradient,
    menuActive: t.menu ? `bg-gradient-to-r from-${t.menu.from}-${t.menu.shade_from} to-${t.menu.to}-${t.menu.shade_to}` : defaultTheme.menuActive,
    buttonGradient: t.button ? `bg-gradient-to-r from-${t.button.from}-${t.button.shade_from} to-${t.button.to}-${t.button.shade_to} ${t.button.text_color}` : defaultTheme.buttonGradient,
    text: t.menu?.text_color || defaultTheme.text,
    mobileMenu: {
      background: t.menu ? `bg-gradient-to-r from-${t.menu.from}-${t.menu.shade_from} to-${t.menu.to}-${t.menu.shade_to}` : defaultTheme.mobileMenu.background,
      text: t.menu?.text_color || defaultTheme.mobileMenu.text,
      textActive: 'text-white font-bold',
      border: 'border-white/20'
    }
  }
})

// Helper untuk mendapatkan class menu desktop (navbar atas)
const getDesktopMenuClass = (menuHref) => {
  const isActive = url.startsWith(menuHref)
  
  if (!userTheme.value) {
    // Gunakan default style dari kode awal
    return [
      defaultTheme.text, 
      isActive ? 'text-pink-600 font-bold' : 'hover:text-pink-600'
    ]
  }
  
  return [
    userTheme.value.menu?.text_color || defaultTheme.text,
    isActive ? 'text-pink-600 font-bold' : 'hover:text-pink-600'
  ]
}

// Helper untuk mendapatkan class menu mobile (bawah)
const getMobileMenuClass = (menuHref) => {
  const isActive = url.startsWith(menuHref)
  
  if (!userTheme.value || !userTheme.value.menu) {
    // Gunakan default style dari kode awal
    return [
      isActive ? 'text-pink-500 font-bold' : 'text-gray-500 hover:text-pink-400'
    ]
  }
  
  return [
    isActive ? themeClasses.value.mobileMenu.textActive : themeClasses.value.mobileMenu.text
  ]
}

const showingDropdown = ref(false)

const toggleDropdown = () => {
  showingDropdown.value = !showingDropdown.value
}

// Menu
const menuItems = [
  { name: 'Home', href: '/dashboard', icon: '🏠' },
  { name: 'Kategory', href: '/master-data/categories', icon: '📝' },
  { name: 'Bank', href: '/master-data/accounts', icon: '📝' },
  { name: 'Transaksi', href: '/transactions', icon: '📝' },
  { name: 'Rencana', href: '/projects', icon: '📝' },
  { name: 'Budget', href: '/budgets', icon: '📝' },
]
</script>

<template>
  <div :class="[themeClasses.background, 'min-h-screen flex flex-col transition-all duration-500 relative overflow-hidden']">
    <Head :title="title" />

    <!-- 🌸 Floating hearts background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <!-- Love 1 -->
      <div class="animate-bounce-slow absolute text-rose-300 text-4xl top-10 left-5 opacity-20">💗</div>
      <!-- Love 2 -->
      <div class="animate-bounce-slow2 absolute text-pink-300 text-5xl bottom-20 right-5 opacity-25">💖</div>
      <!-- Love 3 -->
      <div class="animate-bounce-slow3 absolute text-rose-200 text-3xl top-1/4 right-10 opacity-15">💕</div>
      <!-- Love 4 -->
      <div class="animate-bounce-slow4 absolute text-pink-200 text-6xl bottom-1/3 left-10 opacity-20">💘</div>
      <!-- Love 5 -->
      <div class="animate-bounce-slow5 absolute text-rose-100 text-2xl top-3/4 left-1/4 opacity-10">💝</div>
      <!-- Love 6 -->
      <div class="animate-bounce-slow6 absolute text-pink-100 text-4xl top-1/2 right-1/4 opacity-15">💓</div>

      <div class="animate-bounce-slow absolute text-rose-300 text-7xl top-10 left-10 opacity-25">💗</div>
      
      <div class="animate-bounce-slow2 absolute text-pink-300 text-8xl bottom-20 right-10 opacity-25">💖</div>
    </div>

    <!-- 🌸 Navbar Atas -->
    <nav
      class="fixed top-0 left-0 right-0 backdrop-blur-md shadow-md z-50 flex items-center justify-between px-6 h-16 transition-all"
      :class="themeClasses.navbar"
    >
      <!-- Logo -->
      <div class="flex items-center gap-2">
        <span class="text-2xl">💞</span>
        <h1
          class="text-2xl font-bold text-transparent bg-clip-text select-none tracking-wide"
          :class="themeClasses.logoGradient"
        >
         {{ user.family?.name ?? 'ALMIR' }}
        </h1>
      </div>

      <!-- 🌷 Menu Desktop -->
      <div class="hidden md:flex items-center space-x-2">
        <Link
          v-for="menu in menuItems"
          :key="menu.name"
          :href="menu.href"
          class="relative font-semibold transition-all px-3 py-2 rounded-lg"
          :class="getDesktopMenuClass(menu.href)"
        >
          {{ menu.name }}
          <span
            v-if="url.startsWith(menu.href)"
            class="absolute left-0 bottom-[-6px] w-full h-[3px] rounded-full"
            :class="themeClasses.menuActive"
          />
        </Link>
      </div>

      <!-- 🩷 User Profile Dropdown -->
      <div class="relative hidden md:flex items-center">
        <button
          @click="toggleDropdown"
          class="flex items-center space-x-2 focus:outline-none hover:opacity-80 transition"
        >
          <img
            :src="user.profile_photo_url"
            alt="User"
            class="w-10 h-10 rounded-full border-2 border-pink-400 object-cover"
          />
          <span 
            class="font-semibold"
            :class="userTheme && userTheme.navbar ? 'text-transparent bg-clip-text ' + themeClasses.nameGradient : themeClasses.nameGradient"
          >
            {{ user.name }}
          </span>
        </button>

        <div
          v-if="showingDropdown"
          class="absolute right-0 mt-3 bg-white shadow-xl rounded-lg py-2 w-48 border border-pink-100 animate-fadeIn"
        >
          <Link
            href="/user/profile"
            class="block px-4 py-2 text-gray-700 hover:bg-pink-50 transition flex items-center gap-2"
            @click="showingDropdown = false"
          >
            <span class="text-lg">👤</span>
            Profile
          </Link>
          <Link
            href="/logout"
            method="post"
            as="button"
            class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-pink-50 transition flex items-center gap-2"
          >
            <span class="text-lg">🚪</span>
            Logout
          </Link>
        </div>
      </div>

      <!-- 🌼 Mobile Navbar (atas kanan hanya nama user) -->
      <div class="md:hidden flex justify-end w-full">
        <span 
          class="font-bold text-lg truncate max-w-[120px] text-right"
          :class="userTheme && userTheme.navbar ? 'text-transparent bg-clip-text ' + themeClasses.nameGradient : 'text-pink-600'"
        >
          {{ user.name }}
        </span>
      </div>
    </nav>

    <!-- 🌟 FLASH MESSAGE - DIBAWAH NAVBAR -->
    <div 
      v-if="flashMessage" 
      class="fixed top-16 left-1/2 transform -translate-x-1/2 z-40 mt-4 mx-4 max-w-md w-full transition-all duration-300 animate-fadeIn"
    >
      <div 
        class="p-4 rounded-2xl border backdrop-blur-sm shadow-lg"
        :class="{
          'bg-green-50 border-green-200 text-green-800': flashMessage.type === 'success',
          'bg-red-50 border-red-200 text-red-800': flashMessage.type === 'error',
          'bg-blue-50 border-blue-200 text-blue-800': !flashMessage.type
        }"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-xl">
              {{ flashMessage.type === 'success' ? '✅' : flashMessage.type === 'error' ? '⚠️' : 'ℹ️' }}
            </span>
            <span class="font-medium">{{ flashMessage.message }}</span>
          </div>
          <button 
            @click="closeFlashMessage"
            class="text-gray-500 hover:text-gray-700 transition-colors flex-shrink-0 ml-2"
          >
            ✕
          </button>
        </div>
      </div>
    </div>

    <!-- 🌈 Mobile Bottom Menu dengan lekukan -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 mb-[-4px]">
      <!-- Background melengkung -->
      <div class="relative">
        <svg
          class="absolute bottom-0 left-0 w-full h-16 drop-shadow-md"
          viewBox="0 0 1440 320"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            :class="themeClasses.mobileMenu.background"
            d="M0,288 L480,288 C560,288 640,160 720,160 C800,160 880,288 960,288 L1440,288 L1440,320 L0,320 Z"
          ></path>
        </svg>

        <!-- Menu bawah -->
        <div
          class="absolute bottom-0 left-0 right-0 backdrop-blur-md border-t shadow-lg flex justify-around items-end py-3 rounded-t-3xl"
          :class="[themeClasses.mobileMenu.background, themeClasses.mobileMenu.border]"
        >
          <!-- Home -->
          <Link
            href="/dashboard"
            class="flex flex-col items-center text-xs font-medium transition-all pb-2"
            :class="getMobileMenuClass('/dashboard')"
          >
            <div 
              class="text-2xl transition-transform" 
              :class="url.startsWith('/dashboard') ? 'scale-110' : ''"
            >
              🏠
            </div>
            <span>Home</span>
          </Link>

          <!-- Foto di Tengah -->
          <Link
            href="/user/profile"
            class="relative -mt-10 bg-white rounded-full p-1 border-4 border-white shadow-md hover:scale-110 transition-transform"
          >
            <img
              :src="user.profile_photo_url"
              alt="User"
              class="w-16 h-16 rounded-full object-cover"
            />
          </Link>

          <!-- Rencana -->
          <Link
            href="/transactions"
            class="flex flex-col items-center text-xs font-medium transition-all pb-2"
            :class="getMobileMenuClass('/transactions')"
          >
            <div 
              class="text-2xl transition-transform" 
              :class="url.startsWith('/transactions') ? 'scale-110' : ''"
            >
              📝
            </div>
            <span>Transaksi</span>
          </Link>
        </div>
      </div>
    </nav>

    <!-- 🌺 Konten Halaman -->
    <main class="flex-grow pt-20 pb-24 md:pb-6 transition-all duration-300 relative z-10">
      <slot />
    </main>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px) translateX(-50%);
  }
  to {
    opacity: 1;
    transform: translateY(0) translateX(-50%);
  }
}
.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}

/* Animasi untuk floating hearts */
@keyframes bounce-slow {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(15px) rotate(5deg); }
}
@keyframes bounce-slow2 {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-12px) rotate(-3deg); }
}
@keyframes bounce-slow3 {
  0%, 100% { transform: translateX(0) translateY(0); }
  33% { transform: translateX(10px) translateY(8px); }
  66% { transform: translateX(-5px) translateY(-10px); }
}
@keyframes bounce-slow4 {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(20px) scale(1.1); }
}
@keyframes bounce-slow5 {
  0%, 100% { transform: translateX(0) translateY(0) rotate(0deg); }
  50% { transform: translateX(-8px) translateY(10px) rotate(8deg); }
}
@keyframes bounce-slow6 {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-15px) scale(0.9); }
}

.animate-bounce-slow {
  animation: bounce-slow 8s infinite ease-in-out;
}
.animate-bounce-slow2 {
  animation: bounce-slow2 10s infinite ease-in-out;
  animation-delay: 1s;
}
.animate-bounce-slow3 {
  animation: bounce-slow3 12s infinite ease-in-out;
  animation-delay: 2s;
}
.animate-bounce-slow4 {
  animation: bounce-slow4 9s infinite ease-in-out;
  animation-delay: 0.5s;
}
.animate-bounce-slow5 {
  animation: bounce-slow5 11s infinite ease-in-out;
  animation-delay: 3s;
}
.animate-bounce-slow6 {
  animation: bounce-slow6 7s infinite ease-in-out;
  animation-delay: 1.5s;
}

/* Smooth transitions untuk semua elemen */
* {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>