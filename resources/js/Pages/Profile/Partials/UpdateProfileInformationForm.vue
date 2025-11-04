<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';

const { props } = usePage()
const user = props.auth.user

// Parse theme dari user
const userTheme = user?.theme ? JSON.parse(user.theme) : null

const form = useForm({
    _method: 'PUT',
    name: user.name,
    email: user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);
const showPhotoDropdown = ref(false);
const showPhotoModal = ref(false);

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
  return 'bg-gradient-to-br from-pink-50 via-white to-rose-50'
})

const updateProfileInformation = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            clearPhotoFileInput()
            showPhotoDropdown.value = false
        },
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
    showPhotoDropdown.value = false;
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
            showPhotoDropdown.value = false;
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};

const togglePhotoDropdown = () => {
    showPhotoDropdown.value = !showPhotoDropdown.value;
};

const showFullPhoto = () => {
    showPhotoModal.value = true;
    showPhotoDropdown.value = false;
};

const closePhotoModal = () => {
    showPhotoModal.value = false;
};
</script>

<template>
      <!-- Header dengan sentuhan romantis -->
      <div class="text-center mb-2">
        <div class="inline-flex items-center justify-center w-12 h-12 md:w-16 md:h-16 bg-gradient-to-r from-pink-400 to-rose-400 rounded-full mb-3 md:mb-4 shadow-lg">
          <span class="text-xl md:text-2xl text-white">💝</span>
        </div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800 mb-2">Profil Kita</h1>
        <p class="text-sm md:text-lg text-gray-600 max-w-2xl mx-auto">
          Rawat dan perbarui informasi khusus kita berdua dengan penuh cinta
        </p>
      </div>

      <div class="bg-white/80 backdrop-blur-sm rounded-2xl md:rounded-3xl shadow-xl border border-white/50 overflow-hidden">
        <!-- Profile Photo Section - Tengah dan Menarik -->
        <div class="relative bg-gradient-to-r from-pink-500/10 to-rose-500/10 py-4 md:py-6 px-3 md:px-4 text-center border-b border-pink-100">
          <div class="relative inline-block">
            <!-- Profile Photo -->
            <div class="relative group cursor-pointer" @click="togglePhotoDropdown">
              <!-- Photo Container dengan efek romantis -->
              <div class="relative">
                <img 
                  :src="photoPreview || user.profile_photo_url" 
                  :alt="user.name" 
                  class="w-24 h-24 md:w-40 md:h-40 rounded-full object-cover border-4 border-white shadow-2xl mx-auto transition-all duration-300 group-hover:scale-105 group-hover:border-pink-200"
                />
                <!-- Overlay Hover -->
                <div class="absolute inset-0 bg-black/20 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                  <span class="text-white text-xs md:text-sm font-medium bg-black/50 px-2 md:px-3 py-1 rounded-full">Ubah Foto</span>
                </div>
              </div>
              
              <!-- Decorative Elements -->
              <div class="absolute -top-1 -right-1 md:-top-2 md:-right-2 w-6 h-6 md:w-8 md:h-8 bg-pink-400 rounded-full flex items-center justify-center shadow-lg">
                <span class="text-white text-xs md:text-sm">✨</span>
              </div>
              <div class="absolute -bottom-1 -left-1 md:-bottom-2 md:-left-2 w-5 h-5 md:w-6 md:h-6 bg-rose-300 rounded-full flex items-center justify-center shadow-lg">
                <span class="text-white text-xs">❤️</span>
              </div>
            </div>

            <!-- User Name -->
            <h2 class="text-lg md:text-2xl font-bold text-gray-800 mt-4 md:mt-6 mb-1 md:mb-2">{{ user.name }}</h2>
            <p class="text-gray-600 text-sm md:text-base flex items-center justify-center gap-1 md:gap-2">
              <span class="text-pink-400">📧</span>
              {{ user.email }}
              <span v-if="user.email_verified_at" class="text-green-500 text-xs md:text-sm flex items-center gap-1">
                ✓ Terverifikasi
              </span>
            </p>

            <!-- Photo Dropdown Menu -->
            <div 
              v-if="showPhotoDropdown" 
              class="absolute top-full left-1/2 transform -translate-x-1/2 mt-3 md:mt-4 w-40 md:w-48 bg-white rounded-xl md:rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
            >
              <button 
                @click="selectNewPhoto"
                class="w-full px-3 md:px-4 py-2 md:py-3 text-left hover:bg-pink-50 transition-colors flex items-center gap-2 md:gap-3 text-gray-700 text-sm md:text-base"
                :disabled="form.processing"
              >
                <span class="text-base md:text-lg">🖼️</span>
                <span>Ganti Foto</span>
              </button>
              <button 
                @click="showFullPhoto"
                class="w-full px-3 md:px-4 py-2 md:py-3 text-left hover:bg-pink-50 transition-colors flex items-center gap-2 md:gap-3 text-gray-700 border-t border-gray-100 text-sm md:text-base"
              >
                <span class="text-base md:text-lg">👁️</span>
                <span>Lihat Foto</span>
              </button>
              <button 
                v-if="user.profile_photo_path"
                @click="deletePhoto"
                class="w-full px-3 md:px-4 py-2 md:py-3 text-left hover:bg-red-50 transition-colors flex items-center gap-2 md:gap-3 text-red-600 border-t border-gray-100 text-sm md:text-base"
                :disabled="form.processing"
              >
                <span class="text-base md:text-lg">🗑️</span>
                <span>Hapus Foto</span>
              </button>
            </div>
          </div>

          <!-- Hidden File Input -->
          <input
            id="photo"
            ref="photoInput"
            type="file"
            class="hidden"
            @change="updatePhotoPreview"
            accept="image/*"
          />
        </div>

        <!-- Form Section -->
        <div class="p-3 md:p-4">
          <!-- Name Field -->
          <div class="mb-3 md:mb-4">
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-2">
              <span class="text-pink-400 text-sm md:text-base">👤</span>
              Nama Lengkap
            </label>
            <div class="relative">
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="w-full border border-gray-200 rounded-xl md:rounded-2xl p-2 md:p-3 pl-10 md:pl-12 focus:ring-2 focus:ring-pink-300 focus:border-pink-300 transition-all duration-300 bg-white/50 backdrop-blur-sm text-sm md:text-base"
                required
                autocomplete="name"
                :disabled="form.processing"
                placeholder="Masukkan nama lengkap Anda"
              />
              <div class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm md:text-base">
                💕
              </div>
            </div>
            <p v-if="form.errors.name" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">
              <span class="text-xs md:text-sm">⚠️</span>
              {{ form.errors.name }}
            </p>
          </div>

          <!-- Email Field -->
          <div class="mb-3 md:mb-4">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2 md:mb-3 flex items-center gap-2">
              <span class="text-pink-400 text-sm md:text-base">📧</span>
              Alamat Email
            </label>
            <div class="relative">
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="w-full border border-gray-200 rounded-xl md:rounded-2xl p-2 md:p-3 pl-10 md:pl-12 focus:ring-2 focus:ring-pink-300 focus:border-pink-300 transition-all duration-300 bg-white/50 backdrop-blur-sm text-sm md:text-base"
                required
                autocomplete="username"
                :disabled="form.processing"
                placeholder="email@contoh.com"
              />
              <div class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm md:text-base">
                ✉️
              </div>
            </div>
            <p v-if="form.errors.email" class="mt-1 md:mt-2 text-xs md:text-sm text-red-500 flex items-center gap-1 md:gap-2">
              <span class="text-xs md:text-sm">⚠️</span>
              {{ form.errors.email }}
            </p>

            <!-- Email Verification -->
            <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null" class="mt-3 md:mt-4 p-3 md:p-4 bg-amber-50/80 border border-amber-200 rounded-xl md:rounded-2xl">
              <div class="flex items-start gap-2 md:gap-3">
                <span class="text-amber-500 text-lg md:text-xl mt-0.5">⚠️</span>
                <div class="flex-1">
                  <p class="text-amber-800 font-medium text-sm md:text-base mb-1 md:mb-2">Email Belum Terverifikasi</p>
                  <p class="text-amber-700 text-xs md:text-sm mb-2 md:mb-3">
                    Untuk keamanan dan kenyamanan kita berdua, mohon verifikasi alamat email Anda.
                  </p>
                  <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="inline-flex items-center gap-1 md:gap-2 px-3 md:px-4 py-1.5 md:py-2 bg-amber-100 text-amber-800 rounded-lg md:rounded-xl hover:bg-amber-200 transition-all duration-300 font-medium text-xs md:text-sm border border-amber-300"
                    @click.prevent="sendEmailVerification"
                    :disabled="form.processing"
                  >
                    <span class="text-sm md:text-base">📨</span>
                    Kirim Ulang Email Verifikasi
                  </Link>
                </div>
              </div>

              <div v-show="verificationLinkSent" class="mt-2 p-2 bg-green-100 border border-green-300 rounded-lg md:rounded-xl">
                <p class="text-green-700 text-xs md:text-sm flex items-center gap-1 md:gap-2">
                  <span class="text-sm md:text-base">✅</span>
                  Tautan verifikasi baru telah dikirim ke alamat email Anda
                </p>
              </div>
            </div>
          </div>

          <!-- Photo Error -->
          <p v-if="form.errors.photo" class="mb-4 md:mb-6 p-3 md:p-4 bg-red-50 border border-red-200 rounded-xl md:rounded-2xl text-red-600 text-xs md:text-sm flex items-center gap-1 md:gap-2">
            <span class="text-sm md:text-base">❌</span>
            {{ form.errors.photo }}
          </p>

          <!-- Actions -->
          <div class="flex flex-col-reverse md:flex-row md:items-center md:justify-between pt-3 md:pt-4 border-t border-gray-100 gap-3 md:gap-0">
            <div class="flex items-center justify-center md:justify-start">
              <div v-if="form.recentlySuccessful" class="flex items-center gap-1 md:gap-2 text-green-600 font-medium text-xs md:text-sm bg-green-50 px-3 md:px-4 py-1.5 md:py-2 rounded-lg md:rounded-xl">
                <span class="text-base md:text-lg">✅</span>
                <span>Perubahan tersimpan dengan penuh cinta 💖</span>
              </div>
            </div>

            <button
              type="submit"
              @click.prevent="updateProfileInformation"
              class="px-3 md:px-4 py-2 md:py-2.5 rounded-lg font-medium text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 active:scale-95 text-sm md:text-base"
              :class="form.processing ? disabledButtonClasses : buttonClasses"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="flex items-center gap-1">
                <span class="animate-spin text-sm md:text-base">⏳</span>
                Menyimpan...
              </span>
              <span v-else class="flex items-center gap-1">
                <span class="text-sm md:text-base">💾</span>
                Simpan Perubahan
              </span>
            </button>
          </div>
        </div>
      </div>

    <!-- Photo Modal -->
    <div v-if="showPhotoModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-3 md:p-4">
      <div class="bg-white rounded-2xl md:rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-hidden shadow-2xl">
        <div class="relative">
          <button 
            @click="closePhotoModal"
            class="absolute top-2 md:top-4 right-2 md:right-4 w-8 h-8 md:w-10 md:h-10 bg-black/50 text-white rounded-full flex items-center justify-center hover:bg-black/70 transition-colors z-10 text-sm md:text-base"
          >
            ✕
          </button>
          <img 
            :src="photoPreview || user.profile_photo_url" 
            :alt="user.name" 
            class="w-full h-auto max-h-[70vh] md:max-h-[80vh] object-contain"
          />
        </div>
        <div class="p-4 md:p-6 bg-gradient-to-r from-pink-50 to-rose-50 text-center">
          <p class="text-gray-600 text-sm md:text-base">Foto Profil {{ user.name }}</p>
        </div>
      </div>
    </div>
</template>