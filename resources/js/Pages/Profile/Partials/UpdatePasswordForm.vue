<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const { props } = usePage()
const user = props.auth.user

// Parse theme dari user
const userTheme = user?.theme ? JSON.parse(user.theme) : null

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
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

// Computed untuk tombol disabled
const disabledButtonClasses = computed(() => {
  return 'bg-gray-300 text-gray-500 cursor-not-allowed'
})

// Computed untuk background romantis
const romanticBackground = computed(() => {
  return 'bg-gradient-to-br from-blue-50 via-white to-indigo-50'
})

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
      <!-- Header dengan sentuhan keamanan -->
      <div class="text-center mb-2">
        <div class="inline-flex items-center justify-center w-12 h-12 md:w-16 md:h-16 bg-gradient-to-r from-blue-400 to-indigo-400 rounded-full mb-3 md:mb-4 shadow-lg">
          <span class="text-xl md:text-2xl text-white">🔒</span>
        </div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800 mb-2">Keamanan Akun Kita</h1>
        <p class="text-sm md:text-lg text-gray-600 max-w-2xl mx-auto">
          Lindungi kenangan indah kita dengan kata sandi yang kuat dan aman
        </p>
      </div>

      <div class="bg-white/80 backdrop-blur-sm rounded-2xl md:rounded-3xl shadow-xl border border-white/50 overflow-hidden">
        <!-- Security Section Header -->
        <div class="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 py-4 md:py-6 px-3 md:px-4 border-b border-blue-100">
          <div class="text-center">
            <h2 class="text-lg md:text-2xl font-bold text-gray-800 mb-2">Perbarui Kata Sandi</h2>
            <p class="text-gray-600 text-sm md:text-base flex items-center justify-center gap-1 md:gap-2">
              <span class="text-blue-400 text-sm md:text-base">🛡️</span>
              Pastikan akun kita menggunakan kata sandi yang panjang dan acak untuk tetap aman
            </p>
          </div>
        </div>

        <!-- Form Section -->
        <div class="p-3 md:p-4">
          <!-- Current Password Field -->
          <div class="mb-3 md:mb-4">
            <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-2">
              <span class="text-blue-400 text-sm md:text-base">🔑</span>
              Kata Sandi Saat Ini
            </label>
            <div class="relative">
              <input
                id="current_password"
                ref="currentPasswordInput"
                v-model="form.current_password"
                type="password"
                class="w-full border border-gray-200 rounded-xl md:rounded-2xl p-2 md:p-3 pl-10 md:pl-12 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 transition-all duration-300 bg-white/50 backdrop-blur-sm text-sm md:text-base"
                autocomplete="current-password"
                :disabled="form.processing"
                placeholder="Masukkan kata sandi saat ini"
              />
              <div class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm md:text-base">
                🗝️
              </div>
            </div>
            <p v-if="form.errors.current_password" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">
              <span class="text-xs md:text-sm">⚠️</span>
              {{ form.errors.current_password }}
            </p>
          </div>

          <!-- New Password Field -->
          <div class="mb-3 md:mb-4">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-2">
              <span class="text-green-400 text-sm md:text-base">🆕</span>
              Kata Sandi Baru
            </label>
            <div class="relative">
              <input
                id="password"
                ref="passwordInput"
                v-model="form.password"
                type="password"
                class="w-full border border-gray-200 rounded-xl md:rounded-2xl p-2 md:p-3 pl-10 md:pl-12 focus:ring-2 focus:ring-green-300 focus:border-green-300 transition-all duration-300 bg-white/50 backdrop-blur-sm text-sm md:text-base"
                autocomplete="new-password"
                :disabled="form.processing"
                placeholder="Buat kata sandi baru yang kuat"
              />
              <div class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm md:text-base">
                🔄
              </div>
            </div>
            <p v-if="form.errors.password" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">
              <span class="text-xs md:text-sm">⚠️</span>
              {{ form.errors.password }}
            </p>
            <div class="mt-1 md:mt-2 text-xs text-gray-500 flex items-center gap-1 md:gap-2">
              <span class="text-xs md:text-sm">💡</span>
              Gunakan kombinasi huruf, angka, dan simbol untuk keamanan maksimal
            </div>
          </div>

          <!-- Confirm Password Field -->
          <div class="mb-3 md:mb-4">
            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-2">
              <span class="text-purple-400 text-sm md:text-base">✅</span>
              Konfirmasi Kata Sandi Baru
            </label>
            <div class="relative">
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="w-full border border-gray-200 rounded-xl md:rounded-2xl p-2 md:p-3 pl-10 md:pl-12 focus:ring-2 focus:ring-purple-300 focus:border-purple-300 transition-all duration-300 bg-white/50 backdrop-blur-sm text-sm md:text-base"
                autocomplete="new-password"
                :disabled="form.processing"
                placeholder="Ketik ulang kata sandi baru Anda"
              />
              <div class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm md:text-base">
                🔁
              </div>
            </div>
            <p v-if="form.errors.password_confirmation" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">
              <span class="text-xs md:text-sm">⚠️</span>
              {{ form.errors.password_confirmation }}
            </p>
          </div>

          <!-- Security Tips -->
          <div class="mb-3 md:mb-4 p-2 md:p-3 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl md:rounded-2xl">
            <div class="flex items-start gap-2 md:gap-3">
              <span class="text-amber-500 text-lg md:text-xl mt-0.5">💡</span>
              <div class="flex-1">
                <p class="text-amber-800 font-medium text-sm md:text-base mb-1 md:mb-2">Tips Keamanan Kata Sandi</p>
                <ul class="text-amber-700 text-xs md:text-sm space-y-0.5 md:space-y-1">
                  <li class="flex items-start gap-1 md:gap-2">
                    <span class="mt-0.5">•</span>
                    <span>Minimal 8 karakter dengan kombinasi huruf besar/kecil</span>
                  </li>
                  <li class="flex items-start gap-1 md:gap-2">
                    <span class="mt-0.5">•</span>
                    <span>Gunakan angka dan simbol khusus</span>
                  </li>
                  <li class="flex items-start gap-1 md:gap-2">
                    <span class="mt-0.5">•</span>
                    <span>Hindari menggunakan informasi pribadi yang mudah ditebak</span>
                  </li>
                  <li class="flex items-start gap-1 md:gap-2">
                    <span class="mt-0.5">•</span>
                    <span>Jangan gunakan kata sandi yang sama untuk akun lain</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col-reverse md:flex-row md:items-center md:justify-between pt-3 md:pt-4 border-t border-gray-100 gap-3 md:gap-0">
            <div class="flex items-center justify-center md:justify-start">
              <div v-if="form.recentlySuccessful" class="flex items-center gap-1 md:gap-2 text-green-600 font-medium text-xs md:text-sm bg-green-50 px-3 md:px-4 py-1.5 md:py-2 rounded-lg md:rounded-xl">
                <span class="text-base md:text-lg">✅</span>
                <span>Kata sandi berhasil diperbarui dengan aman 🛡️</span>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 md:gap-3">
              <button
                type="button"
                @click="form.reset()"
                class="px-3 md:px-4 py-2 rounded-lg md:rounded-xl font-medium transition-all duration-300 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm md:text-base"
                :disabled="form.processing"
                :class="form.processing ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white'"
              >
                <span class="flex items-center gap-1 md:gap-2 justify-center">
                  <span class="text-sm md:text-base">🔄</span>
                  Reset Form
                </span>
              </button>

              <button
                type="submit"
                @click.prevent="updatePassword"
                class="px-3 md:px-4 py-2 rounded-lg font-medium text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 active:scale-95 text-sm md:text-base"
                :class="form.processing ? disabledButtonClasses : buttonClasses"
                :disabled="form.processing"
              >
                <span v-if="form.processing" class="flex items-center gap-1 justify-center">
                  <span class="animate-spin text-sm md:text-base">⏳</span>
                  Memperbarui...
                </span>
                <span v-else class="flex items-center gap-1 justify-center">
                  <span class="text-sm md:text-base">💾</span>
                  Perbarui Kata Sandi
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>
</template>