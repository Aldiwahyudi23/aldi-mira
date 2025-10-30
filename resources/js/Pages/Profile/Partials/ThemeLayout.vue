<script setup>
import { ref, watch, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'

const { props } = usePage()
const user = props.auth.user

// Warna dasar yang bisa dipilih
const colorBases = ['pink', 'sky', 'green', 'purple', 'indigo', 'rose', 'gray', 'blue', 'emerald', 'red', 'yellow', 'teal']

// Shades yang umum digunakan
const shades = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900']

// Parse theme dari user
const userTheme = user?.theme ? JSON.parse(user.theme) : null

// Default values untuk reset - SEMUA DIKOSONGKAN
const defaultTheme = {
  navbar: {
    from: '',
    to: '',
    shade_from: '',
    shade_to: '',
    name_color: '',
    logo_color_from: '',
    logo_color_to: '',
    logo_color_shade_from: '',
    logo_color_shade_to: '',
    name_color_from: '',
    name_color_to: '',
    name_color_shade_from: '',
    name_color_shade_to: '',
  },
  background: {
    from: '',
    to: '',
    shade_from: '',
    shade_to: '',
  },
  menu: {
    from: '',
    to: '',
    shade_from: '',
    shade_to: '',
    text_color: '',
  },
  button: {
    from: '',
    to: '',
    shade_from: '',
    shade_to: '',
    text_color: '',
  }
}

// Form tema - HANYA AMBIL DATA YANG ADA DI DATABASE, JIKA TIDAK ADA DIKOSONGKAN
const form = useForm({
  theme: {
    navbar: {
      from: userTheme?.navbar?.from || '',
      to: userTheme?.navbar?.to || '',
      shade_from: userTheme?.navbar?.shade_from || '',
      shade_to: userTheme?.navbar?.shade_to || '',
      name_color: userTheme?.navbar?.name_color || '',
      logo_color_from: userTheme?.navbar?.logo_color_from || '',
      logo_color_to: userTheme?.navbar?.logo_color_to || '',
      logo_color_shade_from: userTheme?.navbar?.logo_color_shade_from || '',
      logo_color_shade_to: userTheme?.navbar?.logo_color_shade_to || '',
      name_color_from: userTheme?.navbar?.name_color_from || '',
      name_color_to: userTheme?.navbar?.name_color_to || '',
      name_color_shade_from: userTheme?.navbar?.name_color_shade_from || '',
      name_color_shade_to: userTheme?.navbar?.name_color_shade_to || '',
    },
    background: {
      from: userTheme?.background?.from || '',
      to: userTheme?.background?.to || '',
      shade_from: userTheme?.background?.shade_from || '',
      shade_to: userTheme?.background?.shade_to || '',
    },
    menu: {
      from: userTheme?.menu?.from || '',
      to: userTheme?.menu?.to || '',
      shade_from: userTheme?.menu?.shade_from || '',
      shade_to: userTheme?.menu?.shade_to || '',
      text_color: userTheme?.menu?.text_color || '',
    },
    button: {
      from: userTheme?.button?.from || '',
      to: userTheme?.button?.to || '',
      shade_from: userTheme?.button?.shade_from || '',
      shade_to: userTheme?.button?.shade_to || '',
      text_color: userTheme?.button?.text_color || '',
    },
  }
})

// Computed untuk mendapatkan class tombol berdasarkan theme
const buttonClasses = computed(() => {
  const buttonTheme = form.theme.button
  
  // Jika ada setting tombol yang diisi, gunakan theme tersebut
  if (buttonTheme.from && buttonTheme.to && buttonTheme.shade_from && buttonTheme.shade_to) {
    const gradient = `bg-gradient-to-r from-${buttonTheme.from}-${buttonTheme.shade_from} to-${buttonTheme.to}-${buttonTheme.shade_to}`
    const textColor = buttonTheme.text_color || 'text-white'
    return `${gradient} ${textColor}`
  }
  
  // Default: pink-300 to sky-300
  return 'bg-gradient-to-r from-pink-300 to-sky-300 text-white'
})

// Computed untuk tombol disabled
const disabledButtonClasses = computed(() => {
  return 'bg-gray-300 text-gray-500 cursor-not-allowed'
})

// Computed untuk tombol reset (warna merah tetap)
const resetButtonClasses = computed(() => {
  return 'bg-gradient-to-r from-red-400 to-red-500 text-white'
})

// Computed untuk tombol hapus semua (warna merah gelap)
const deleteButtonClasses = computed(() => {
  return 'bg-gradient-to-r from-red-500 to-red-600 text-white'
})

// Reset section - HAPUS DARI DATABASE dan FORM
const resetSection = (section) => {
  if (confirm(`Yakin ingin menghapus tema ${section} dari database?`)) {
    // Hapus dari database
    router.delete(route('user-theme.destroy-section'), {
      data: { section: section },
      preserveScroll: true,
      onSuccess: () => {
        // Reset form juga setelah database direset
        form.theme[section] = { ...defaultTheme[section] }
      }
    })
  }
}

// Reset semua theme - HAPUS SEMUA DARI DATABASE
const resetAll = () => {
  if (confirm('Yakin ingin menghapus semua tema dari database?')) {
    router.delete(route('user-theme.destroy-all'), {
      preserveScroll: true,
      onSuccess: () => {
        // Reset semua form setelah database direset
        form.theme = { ...defaultTheme }
      }
    })
  }
}

// Preview warna dinamis - HANYA JIKA ADA NILAI
const getGradient = (section) => {
  const s = form.theme[section]
  if (s.from && s.to && s.shade_from && s.shade_to) {
    return `bg-gradient-to-r from-${s.from}-${s.shade_from} to-${s.to}-${s.shade_to}`
  }
  return 'bg-gray-200'
}

// Preview untuk logo gradient - HANYA JIKA ADA NILAI
const getLogoGradient = () => {
  const n = form.theme.navbar
  if (n.logo_color_from && n.logo_color_to && n.logo_color_shade_from && n.logo_color_shade_to) {
    return `bg-gradient-to-r from-${n.logo_color_from}-${n.logo_color_shade_from} to-${n.logo_color_to}-${n.logo_color_shade_to}`
  }
  return 'bg-gradient-to-r from-pink-500 to-sky-500' // Default fallback
}

// Preview untuk name gradient - HANYA JIKA ADA NILAI
const getNameGradient = () => {
  const n = form.theme.navbar
  if (n.name_color_from && n.name_color_to && n.name_color_shade_from && n.name_color_shade_to) {
    return `bg-gradient-to-r from-${n.name_color_from}-${n.name_color_shade_from} to-${n.name_color_to}-${n.name_color_shade_to}`
  }
  return 'text-gray-700' // Default fallback
}

// Simpan tema ke server - HANYA KIRIM DATA YANG DIISI
const saveTheme = () => {
  // Filter hanya data yang diisi
  const filteredTheme = {
    navbar: {},
    background: {},
    menu: {},
    button: {}
  }

  // Filter navbar
  Object.keys(form.theme.navbar).forEach(key => {
    if (form.theme.navbar[key]) {
      filteredTheme.navbar[key] = form.theme.navbar[key]
    }
  })

  // Filter background
  Object.keys(form.theme.background).forEach(key => {
    if (form.theme.background[key]) {
      filteredTheme.background[key] = form.theme.background[key]
    }
  })

  // Filter menu
  Object.keys(form.theme.menu).forEach(key => {
    if (form.theme.menu[key]) {
      filteredTheme.menu[key] = form.theme.menu[key]
    }
  })

  // Filter button
  Object.keys(form.theme.button).forEach(key => {
    if (form.theme.button[key]) {
      filteredTheme.button[key] = form.theme.button[key]
    }
  })

  // Hapus section yang kosong
  if (Object.keys(filteredTheme.navbar).length === 0) delete filteredTheme.navbar
  if (Object.keys(filteredTheme.background).length === 0) delete filteredTheme.background
  if (Object.keys(filteredTheme.menu).length === 0) delete filteredTheme.menu
  if (Object.keys(filteredTheme.button).length === 0) delete filteredTheme.button

  form.transform((data) => ({
    ...data,
    theme: filteredTheme
  })).post(route('user-theme.update'), { preserveScroll: true })
}

// Watch untuk auto-save (opsional)
watch(() => form.theme, (newTheme) => {
  // Optional: bisa ditambahkan auto-save di sini jika diperlukan
}, { deep: true, immediate: false })
</script>

<template>
  <div class="space-y-8 px-4 sm:px-0">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">🎨 Pengaturan Tema</h3>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <p class="text-blue-800 text-sm">
        💡 <strong>Tips:</strong> 
        - Kosongkan field yang tidak ingin diubah. Hanya data yang diisi yang akan disimpan.<br>
        - Biarkan kosong untuk menggunakan default system.<br>
        - Tombol <strong>Reset</strong> akan menghapus tema dari database.
      </p>
    </div>

    <!-- ==== NAVBAR ==== -->
    <div class="p-4 rounded-lg shadow bg-white dark:bg-gray-800">
      <div class="flex justify-between items-center mb-4">
        <h4 class="font-semibold text-gray-800">Navbar Settings</h4>
        <button
          @click="resetSection('navbar')"
          class="px-3 py-2 rounded-lg hover:opacity-90 transition text-sm font-medium shadow"
          :class="resetButtonClasses"
          :disabled="form.processing"
        >
          🗑️ Reset Navbar
        </button>
      </div>

      <!-- Background Navbar -->
      <div class="mb-6">
        <h5 class="font-medium mb-3 text-gray-700">Background Navbar</h5>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Warna Dari</label>
            <select v-model="form.theme.navbar.from" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih warna...</option>
              <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
            <select v-model="form.theme.navbar.shade_from" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih shade...</option>
              <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Warna Ke</label>
            <select v-model="form.theme.navbar.to" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih warna...</option>
              <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
            <select v-model="form.theme.navbar.shade_to" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih shade...</option>
              <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
        </div>
        <div class="mt-3 h-12 rounded-lg shadow-inner border flex items-center justify-center text-white font-semibold text-sm" 
             :class="getGradient('navbar')">
          {{ form.theme.navbar.from && form.theme.navbar.to ? 'Background Navbar' : 'Default System' }}
        </div>
      </div>

      <!-- Logo Gradient -->
      <div class="mb-6">
        <h5 class="font-medium mb-3 text-gray-700">Gradient Logo</h5>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Warna Dari</label>
            <select v-model="form.theme.navbar.logo_color_from" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih warna...</option>
              <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
            <select v-model="form.theme.navbar.logo_color_shade_from" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih shade...</option>
              <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Warna Ke</label>
            <select v-model="form.theme.navbar.logo_color_to" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih warna...</option>
              <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
            <select v-model="form.theme.navbar.logo_color_shade_to" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih shade...</option>
              <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
        </div>
        <div class="mt-3 h-12 rounded-lg shadow-inner border flex items-center justify-center text-transparent bg-clip-text font-bold text-2xl" 
             :class="getLogoGradient()">
          ALMIR
        </div>
      </div>

      <!-- Name Gradient -->
      <div class="mb-6">
        <h5 class="font-medium mb-3 text-gray-700">Gradient Nama User</h5>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Warna Dari</label>
            <select v-model="form.theme.navbar.name_color_from" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih warna...</option>
              <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
            <select v-model="form.theme.navbar.name_color_shade_from" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih shade...</option>
              <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Warna Ke</label>
            <select v-model="form.theme.navbar.name_color_to" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih warna...</option>
              <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
            <select v-model="form.theme.navbar.name_color_shade_to" class="w-full border rounded-lg p-2" :disabled="form.processing">
              <option value="">Pilih shade...</option>
              <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
        </div>
        <div class="mt-3 h-12 rounded-lg shadow-inner border flex items-center justify-center font-semibold text-lg" 
             :class="getNameGradient()">
          Nama User
        </div>
      </div>
    </div>

    <!-- ==== BACKGROUND UTAMA ==== -->
    <div class="p-4 rounded-lg shadow bg-white dark:bg-gray-800">
      <div class="flex justify-between items-center mb-4">
        <h4 class="font-semibold text-gray-800">Background Utama</h4>
        <button
          @click="resetSection('background')"
          class="px-3 py-2 rounded-lg hover:opacity-90 transition text-sm font-medium shadow"
          :class="resetButtonClasses"
          :disabled="form.processing"
        >
          🗑️ Reset Background
        </button>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Warna Dari</label>
          <select v-model="form.theme.background.from" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih warna...</option>
            <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
          </select>
          <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
          <select v-model="form.theme.background.shade_from" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih shade...</option>
            <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Warna Ke</label>
          <select v-model="form.theme.background.to" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih warna...</option>
            <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
          </select>
          <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
          <select v-model="form.theme.background.shade_to" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih shade...</option>
            <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
      </div>

      <div class="mt-3 h-12 rounded-lg shadow-inner border flex items-center justify-center text-gray-700 font-semibold text-sm" 
           :class="getGradient('background')">
        {{ form.theme.background.from && form.theme.background.to ? 'Background Utama' : 'Default System' }}
      </div>
    </div>

    <!-- ==== MENU BAWAH ==== -->
    <div class="p-4 rounded-lg shadow bg-white dark:bg-gray-800">
      <div class="flex justify-between items-center mb-4">
        <h4 class="font-semibold text-gray-800">Menu Bawah (Mobile)</h4>
        <button
          @click="resetSection('menu')"
          class="px-3 py-2 rounded-lg hover:opacity-90 transition text-sm font-medium shadow"
          :class="resetButtonClasses"
          :disabled="form.processing"
        >
          🗑️ Reset Menu
        </button>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Warna Dari</label>
          <select v-model="form.theme.menu.from" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih warna...</option>
            <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
          </select>
          <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
          <select v-model="form.theme.menu.shade_from" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih shade...</option>
            <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Warna Ke</label>
          <select v-model="form.theme.menu.to" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih warna...</option>
            <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
          </select>
          <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
          <select v-model="form.theme.menu.shade_to" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih shade...</option>
            <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
      </div>

      <div class="mt-3 flex gap-3">
        <div class="flex flex-col">
          <label class="block text-sm font-medium text-gray-600 mb-1">Warna Teks Menu</label>
          <select v-model="form.theme.menu.text_color" class="border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih warna teks...</option>
            <option value="text-white">Putih</option>
            <option value="text-gray-800">Abu Gelap</option>
            <option value="text-gray-100">Abu Terang</option>
          </select>
        </div>
      </div>

      <div class="mt-3 h-12 rounded-lg shadow-inner border flex items-center justify-center font-semibold text-sm" 
           :class="[getGradient('menu'), form.theme.menu.text_color || 'text-gray-800']">
        {{ form.theme.menu.from && form.theme.menu.to ? 'Menu Bawah' : 'Default System' }}
      </div>
    </div>

    <!-- ==== TOMBOL ==== -->
    <div class="p-4 rounded-lg shadow bg-white dark:bg-gray-800">
      <div class="flex justify-between items-center mb-4">
        <h4 class="font-semibold text-gray-800">Tombol</h4>
        <button
          @click="resetSection('button')"
          class="px-3 py-2 rounded-lg hover:opacity-90 transition text-sm font-medium shadow"
          :class="resetButtonClasses"
          :disabled="form.processing"
        >
          🗑️ Reset Tombol
        </button>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Warna Dari</label>
          <select v-model="form.theme.button.from" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih warna...</option>
            <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
          </select>
          <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
          <select v-model="form.theme.button.shade_from" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih shade...</option>
            <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Warna Ke</label>
          <select v-model="form.theme.button.to" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih warna...</option>
            <option v-for="c in colorBases" :key="c" :value="c">{{ c }}</option>
          </select>
          <label class="block text-sm font-medium text-gray-600 mt-2 mb-1">Shade</label>
          <select v-model="form.theme.button.shade_to" class="w-full border rounded-lg p-2" :disabled="form.processing">
            <option value="">Pilih shade...</option>
            <option v-for="s in shades" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
      </div>

      <div class="mt-3 flex flex-col">
        <label class="block text-sm font-medium text-gray-600 mb-1">Warna Teks Tombol</label>
        <select v-model="form.theme.button.text_color" class="border rounded-lg p-2 w-40" :disabled="form.processing">
          <option value="">Pilih warna teks...</option>
          <option value="text-white">Putih</option>
          <option value="text-gray-800">Abu Gelap</option>
          <option value="text-gray-100">Abu Terang</option>
        </select>
      </div>

      <div class="mt-3 h-12 rounded-lg shadow-inner border flex items-center justify-center font-semibold text-sm"
        :class="[getGradient('button'), form.theme.button.text_color || 'text-white']">
        {{ form.theme.button.from && form.theme.button.to ? 'Contoh Tombol' : 'Default System' }}
      </div>
    </div>

    <!-- Tombol Simpan & Hapus -->
    <div class="mt-8 gap-3 flex justify-end">
      
      <div v-if="form.recentlySuccessful" class="flex items-center gap-2 text-green-600 font-medium text-sm bg-green-50 px-4 py-2 rounded-xl">
        <span class="text-lg">✅</span>
        <span>Tema berhasil diperbarui!</span>
      </div>

      <button
        @click="resetAll"
        class="px-4 py-2 rounded-lg font-medium transition text-sm shadow hover:opacity-90"
        :class="deleteButtonClasses"
        :disabled="form.processing"
      >
        🗑️ Hapus Semua Tema
      </button>

      <button
        @click="saveTheme"
        class="px-4 py-2 rounded-lg font-medium text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 active:scale-95"
              :class="form.processing ? disabledButtonClasses : buttonClasses"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="flex items-center gap-1">
                <span class="animate-spin">⏳</span>
                Menyimpan...
              </span>
              <span v-else class="flex items-center gap-1">
                <span>💾</span>
                Simpan Perubahan
              </span>
      </button>
    </div>
  </div>
</template>