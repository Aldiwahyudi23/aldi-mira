<script setup>
import { ref, onMounted } from 'vue'
import Lottie from 'lottie-web'

const animationContainer = ref(null)
const props = defineProps({
  message: {
    type: String,
    default: 'Akses ditolak 😢',
  },
})

onMounted(() => {
  if (animationContainer.value) {
    Lottie.loadAnimation({
      container: animationContainer.value,
      renderer: 'svg',
      loop: true,
      autoplay: true,
      // Animasi love 3D aman & stabil dari CDN
      path: 'https://assets2.lottiefiles.com/packages/lf20_j1adxtyb.json',
    })
  }
})
</script>

<template>
  <div
    class="min-h-screen flex flex-col justify-center items-center text-center px-6 bg-gradient-to-br from-pink-400 via-pink-200 to-sky-400"
  >
    <transition name="fade-slide" appear>
      <div
        class="p-8 rounded-3xl bg-white/40 shadow-2xl backdrop-blur-xl max-w-md w-full"
      >
        <div ref="animationContainer" class="w-72 h-72 mx-auto mb-6"></div>

        <h1 class="text-4xl font-extrabold text-pink-700 mb-4 drop-shadow-md">
          403 - Akses Ditolak 💔
        </h1>

        <p class="text-sky-700 font-medium mb-4 leading-relaxed">
          {{ message }}
        </p>

        <p class="text-sm text-pink-700 italic">
          Tapi jangan sedih ya 💕 — kadang kita cuma butuh izin yang tepat untuk melangkah.
        </p>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.6s cubic-bezier(.2,.8,.2,1);
}

/* efek lembut mengambang */
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
.w-72.h-72 {
  animation: float 3s ease-in-out infinite;
}
</style>
