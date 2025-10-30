<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Lottie from 'lottie-web'
import { Howl } from 'howler'

const password = ref('')
const errors = ref(null)
const isLoading = ref(false)
const showPassword = ref(false)
const loadingContainer = ref(null)
const hearts = ref([])

// bunyi romantis saat login sukses
const successSound = new Howl({
  src: ['https://cdn.pixabay.com/download/audio/2023/06/24/audio_7ff41a49c3.mp3?filename=success-1-6297.mp3'],
  volume: 0.4,
})

const submit = () => {
  if (password.value.length < 6) return
  isLoading.value = true

  router.post(
    '/login',
    { password: password.value },
    {
      onError: (e) => {
        errors.value = e
        isLoading.value = false
      },
      onSuccess: () => {
        successSound.play()
        isLoading.value = false
      },
    }
  )
}

onMounted(() => {
  // animasi love jatuh
  hearts.value = Array.from({ length: 15 }, (_, i) => i)
})
</script>

<template>
  <div class="relative min-h-screen flex flex-col justify-center items-center overflow-hidden">
    <!-- background gradient -->
    <div class="absolute inset-0 bg-gradient-to-br from-pink-300 via-white to-sky-300 animate-gradient"></div>

    <!-- floating hearts -->
    <div class="absolute inset-0 overflow-hidden">
      <div v-for="n in hearts" :key="n" class="heart"></div>
    </div>

    <!-- box login -->
    <div class="relative z-10 p-8 bg-white/60 backdrop-blur-xl rounded-3xl shadow-2xl max-w-md w-full text-center">
      <h1 class="text-3xl font-bold text-pink-600 mb-6">
        Masuk ALMIR 💕 (Aldi Mira)
      </h1>

      <div class="relative">
        <input
          v-model="password"
          :type="showPassword ? 'text' : 'password'"
          placeholder="Masukkan kode rahasia..."
          class="w-full px-4 py-3 pr-10 rounded-full text-center border border-pink-300 focus:ring-2 focus:ring-sky-300 mb-4"
        />
        <button
          type="button"
          @click="showPassword = !showPassword"
          class="absolute right-4 top-1/2 -translate-y-1/2 text-sky-600 hover:text-pink-500"
        >
          <span v-if="!showPassword">👁️</span>
          <span v-else>🙈</span>
        </button>
      </div>

      <button
        @click="submit"
        :disabled="password.length < 6 || isLoading"
        class="w-full py-3 bg-gradient-to-r from-pink-400 to-sky-400 text-white font-semibold rounded-full transition-all duration-300"
        :class="{
          'opacity-50 cursor-not-allowed': password.length < 6 || isLoading,
          'hover:scale-105': password.length >= 6 && !isLoading
        }"
      >
        <span v-if="!isLoading">Masuk 💫</span>
        <span v-else>Loading...</span>
      </button>

      <p v-if="errors && errors.password" class="text-red-600 mt-3 text-sm">
        {{ errors.password }}
      </p>

      <p class="mt-6 text-sky-700 text-sm italic">
        Gunakan kode cinta kalian untuk masuk 💖
      </p>
    </div>

    <!-- overlay loading -->
    <transition name="fade">
      <div
        v-if="isLoading"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex flex-col items-center justify-center z-50"
      >
        <div ref="loadingContainer" class="w-64 h-64"></div>
        <p class="text-white mt-4 font-semibold text-lg animate-pulse">
          Cinta sedang menghubungkan hati kalian... 💞
        </p>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  mounted() {
    // jalankan animasi pelukan saat loading
    this.$watch('isLoading', (val) => {
      if (val && this.$refs.loadingContainer) {
        Lottie.loadAnimation({
          container: this.$refs.loadingContainer,
          path: 'https://lottie.host/1e5ef88b-6b0f-4d38-bc3a-0b7d5135b841/love-hug.json', // ❤️ Animasi pelukan cinta
          renderer: 'svg',
          loop: true,
          autoplay: true,
        })
      }
    })
  },
}
</script>

<style scoped>
/* gradasi animasi lembut */
@keyframes gradientShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
.animate-gradient {
  background-size: 200% 200%;
  animation: gradientShift 10s ease infinite;
}

/* transisi fade */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* love jatuh */
.heart {
  position: absolute;
  top: -10%;
  width: 20px;
  height: 20px;
  background: pink;
  opacity: 0.6;
  transform: rotate(45deg);
  animation: fall linear infinite;
}
.heart::before,
.heart::after {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  background: pink;
  border-radius: 50%;
}
.heart::before { top: -10px; left: 0; }
.heart::after { left: 10px; top: 0; }

@keyframes fall {
  0% { transform: translateY(0) rotate(45deg); opacity: 1; }
  100% { transform: translateY(110vh) rotate(45deg); opacity: 0; }
}
.heart:nth-child(odd) {
  left: calc(var(--i, 1) * 5%);
  animation-duration: 6s;
}
.heart:nth-child(even) {
  left: calc(var(--i, 2) * 8%);
  animation-duration: 9s;
}
</style>
