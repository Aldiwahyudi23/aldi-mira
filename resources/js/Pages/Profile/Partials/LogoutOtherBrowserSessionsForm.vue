<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const { props } = usePage()
const user = props.auth.user

// Parse theme dari user
const userTheme = user?.theme ? JSON.parse(user.theme) : null

defineProps({
    sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

// Computed untuk mendapatkan class tombol berdasarkan theme
const buttonClasses = computed(() => {
  const buttonTheme = userTheme?.button
  
  // Jika ada setting tombol yang diisi, gunakan theme tersebut
  if (buttonTheme?.from && buttonTheme?.to && buttonTheme?.shade_from && buttonTheme?.shade_to) {
    const gradient = `bg-gradient-to-r from-${buttonTheme.from}-${buttonTheme.shade_from} to-${buttonTheme.to}-${buttonTheme.shade_to}`
    const textColor = buttonTheme.text_color || 'text-white'
    return `${gradient} ${textColor}`
  }
  
  // Default: pink-300 to sky-300
  return 'bg-gradient-to-r from-pink-300 to-sky-300 text-white'
})

// Computed untuk tombol secondary
const secondaryButtonClasses = computed(() => {
  return 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
})

// Computed untuk tombol danger
const dangerButtonClasses = computed(() => {
  return 'bg-gradient-to-r from-red-400 to-red-500 text-white hover:from-red-500 hover:to-red-600'
})

// Computed untuk background romantis
const romanticBackground = computed(() => {
  return 'bg-gradient-to-br from-orange-50 via-white to-amber-50'
})

const confirmLogout = () => {
    confirmingLogout.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const logoutOtherBrowserSessions = () => {
    form.delete(route('other-browser-sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingLogout.value = false;
    form.reset();
};

// Helper untuk menentukan ikon device
const getDeviceIcon = (session) => {
    if (session.agent.is_desktop) {
        return '💻';
    } else {
        return '📱';
    }
};

// Helper untuk menentukan warna status
const getStatusColor = (session) => {
    if (session.is_current_device) {
        return 'text-green-500';
    } else {
        return 'text-gray-500';
    }
};

// Helper untuk menentukan badge status
const getStatusBadge = (session) => {
    if (session.is_current_device) {
        return 'bg-green-100 text-green-800 border-green-200';
    } else {
        return 'bg-blue-100 text-blue-800 border-blue-200';
    }
};
</script>

<template>
      <!-- Header dengan sentuhan keamanan -->
      <div class="text-center mb-2">
        <div class="inline-flex items-center justify-center w-12 h-12 md:w-16 md:h-16 bg-gradient-to-r from-orange-400 to-amber-400 rounded-full mb-3 md:mb-4 shadow-lg">
          <span class="text-xl md:text-2xl text-white">🌐</span>
        </div>
        <h1 class="text-xl md:text-3xl font-bold text-gray-800 mb-2">Sesi Browser Aktif</h1>
        <p class="text-sm md:text-lg text-gray-600 max-w-2xl mx-auto">
          Kelola dan pantau sesi aktif kita di berbagai perangkat dengan penuh perhatian
        </p>
      </div>

      <div class="bg-white/80 backdrop-blur-sm rounded-2xl md:rounded-3xl shadow-xl border border-white/50 overflow-hidden">
        <!-- Security Section Header -->
        <div class="bg-gradient-to-r from-orange-500/10 to-amber-500/10 py-3 md:py-4 px-3 md:px-4 border-b border-orange-100">
          <div class="text-center">
            <h2 class="text-lg md:text-2xl font-bold text-gray-800 mb-2">Pantau Keamanan Sesi Kita</h2>
            <p class="text-gray-600 text-sm md:text-base flex items-center justify-center gap-1 md:gap-2">
              <span class="text-orange-400 text-sm md:text-base">🔍</span>
              Kelola semua sesi browser aktif untuk memastikan keamanan akun kita
            </p>
          </div>
        </div>

        <!-- Content Section -->
        <div class="p-3 md:p-4">
          <!-- Information Section -->
          <div class="mb-3 md:mb-4 p-2 md:p-3 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl md:rounded-2xl border border-blue-200">
            <p class="text-gray-700 leading-relaxed text-xs md:text-sm flex items-start gap-2 md:gap-3">
              <span class="text-blue-500 text-base md:text-lg mt-0.5">💡</span>
              <span>
                Jika diperlukan, Anda dapat keluar dari semua sesi browser lain di semua perangkat Anda. Beberapa sesi terbaru Anda tercantum di bawah; namun, daftar ini mungkin tidak lengkap. Jika Anda merasa akun Anda telah disusupi, Anda juga harus memperbarui kata sandi Anda.
              </span>
            </p>
          </div>

          <!-- Other Browser Sessions -->
          <div v-if="sessions.length > 0" class="mt-2 space-y-2">
            <div 
              v-for="(session, i) in sessions" 
              :key="i" 
              class="flex items-center p-2 md:p-3 bg-white rounded-xl md:rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300"
            >
              <!-- Device Icon -->
              <div class="flex-shrink-0 mr-3 md:mr-4">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-orange-100 to-amber-100 rounded-lg md:rounded-xl flex items-center justify-center border border-orange-200">
                  <span class="text-xl md:text-2xl">{{ getDeviceIcon(session) }}</span>
                </div>
              </div>

              <!-- Session Details -->
              <div class="flex-1 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 md:gap-3 mb-1 md:mb-2">
                  <h3 class="text-xs md:text-sm font-semibold text-gray-800 truncate">
                    {{ session.agent.platform ? session.agent.platform : 'Tidak Diketahui' }} - {{ session.agent.browser ? session.agent.browser : 'Tidak Diketahui' }}
                  </h3>
                  <span 
                    class="px-2 py-1 text-xs font-medium rounded-full border w-fit"
                    :class="getStatusBadge(session)"
                  >
                    {{ session.is_current_device ? 'Perangkat Ini' : 'Aktif' }}
                  </span>
                </div>
                
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 md:gap-4 text-xs text-gray-600">
                  <span class="flex items-center gap-1">
                    <span class="text-gray-400 text-xs">🌐</span>
                    {{ session.ip_address }}
                  </span>
                  <span v-if="!session.is_current_device" class="flex items-center gap-1">
                    <span class="text-gray-400 text-xs">🕒</span>
                    Terakhir aktif {{ session.last_active }}
                  </span>
                </div>
              </div>

              <!-- Current Device Indicator -->
              <div v-if="session.is_current_device" class="flex-shrink-0 ml-2 md:ml-4">
                <div class="w-2 h-2 md:w-3 md:h-3 bg-green-400 rounded-full animate-pulse"></div>
              </div>
            </div>
          </div>

          <!-- No Sessions Message -->
          <div v-else class="text-center py-6 md:py-8">
            <div class="text-gray-400 text-4xl md:text-6xl mb-3 md:mb-4">🔒</div>
            <p class="text-gray-500 text-base md:text-lg">Tidak ada sesi browser lain yang aktif</p>
            <p class="text-gray-400 text-xs md:text-sm mt-1 md:mt-2">Semua sesi saat ini aman dan terkendali</p>
          </div>

          <!-- Action Button -->
          <div class="flex flex-col-reverse md:flex-row md:items-center md:justify-between pt-3 md:pt-4 border-t border-gray-100 gap-3 md:gap-0">
            <div class="flex items-center justify-center md:justify-start">
              <div v-if="form.recentlySuccessful" class="flex items-center gap-1 md:gap-2 text-green-600 font-medium text-xs md:text-sm bg-green-50 px-3 md:px-4 py-1.5 md:py-2 rounded-lg md:rounded-xl">
                <span class="text-base md:text-lg">✅</span>
                <span>Semua sesi berhasil dikeluarkan</span>
              </div>
            </div>

            <button
              @click="confirmLogout"
              class="px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-2xl font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 active:scale-95 text-sm md:text-base"
              :class="dangerButtonClasses"
            >
              <span class="flex items-center gap-1 md:gap-2 justify-center">
                <span class="text-sm md:text-base">🚪</span>
                Keluar dari Semua Sesi Lain
              </span>
            </button>
          </div>
        </div>
      </div>

    <!-- Log Out Other Devices Confirmation Modal -->
    <div v-if="confirmingLogout" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-3 md:p-4">
      <div class="bg-white rounded-2xl md:rounded-3xl max-w-md w-full shadow-2xl border border-white/50 overflow-hidden">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-red-500/10 to-orange-500/10 py-4 md:py-6 px-3 md:px-4 border-b border-red-100">
          <div class="text-center">
            <div class="w-12 h-12 md:w-16 md:h-16 bg-gradient-to-r from-red-400 to-orange-400 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
              <span class="text-xl md:text-2xl text-white">⚠️</span>
            </div>
            <h3 class="text-lg md:text-xl font-bold text-gray-800">Konfirmasi Keluar Sesi</h3>
            <p class="text-gray-600 text-sm md:text-base mt-1 md:mt-2">
              Tindakan ini akan mengeluarkan Anda dari semua sesi browser di perangkat lain
            </p>
          </div>
        </div>

        <!-- Modal Content -->
        <div class="p-3 md:p-4">
          <div class="mb-3 md:mb-4">
            <p class="text-gray-700 text-xs md:text-sm leading-relaxed flex items-start gap-2 md:gap-3">
              <span class="text-red-500 text-base md:text-lg mt-0.5">🔒</span>
              <span>
                Masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin keluar dari sesi browser lain di semua perangkat Anda.
              </span>
            </p>
          </div>

          <div class="mb-3 md:mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-1 md:gap-2">
              <span class="text-red-400 text-sm md:text-base">🗝️</span>
              Kata Sandi Saat Ini
            </label>
            <div class="relative">
              <input
                ref="passwordInput"
                v-model="form.password"
                type="password"
                class="w-full border border-gray-200 rounded-xl md:rounded-2xl p-2 md:p-3 pl-10 md:pl-12 focus:ring-2 focus:ring-red-300 focus:border-red-300 transition-all duration-300 bg-white/50 backdrop-blur-sm text-sm md:text-base"
                placeholder="Masukkan kata sandi Anda"
                autocomplete="current-password"
                @keyup.enter="logoutOtherBrowserSessions"
              />
              <div class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm md:text-base">
                🔒
              </div>
            </div>
            <p v-if="form.errors.password" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">
              <span class="text-xs md:text-sm">⚠️</span>
              {{ form.errors.password }}
            </p>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between p-3 md:p-4 bg-gray-50 border-t border-gray-200 gap-2 md:gap-0">
          <button
            @click="closeModal"
            class="px-4 md:px-6 py-2 rounded-lg md:rounded-2xl font-medium transition-all duration-300 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm md:text-base"
            :class="secondaryButtonClasses"
          >
            <span class="flex items-center gap-1 md:gap-2 justify-center">
              <span class="text-sm md:text-base">↩️</span>
              Batal
            </span>
          </button>

          <button
            @click="logoutOtherBrowserSessions"
            class="px-4 md:px-6 py-2 rounded-lg md:rounded-2xl font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl text-sm md:text-base"
            :class="form.processing ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : dangerButtonClasses"
            :disabled="form.processing"
          >
            <span v-if="form.processing" class="flex items-center gap-1 md:gap-2 justify-center">
              <span class="animate-spin text-sm md:text-base">⏳</span>
              Memproses...
            </span>
            <span v-else class="flex items-center gap-1 md:gap-2 justify-center">
              <span class="text-sm md:text-base">🚪</span>
              Keluar dari Sesi Lain
            </span>
          </button>
        </div>
      </div>
    </div>
</template>