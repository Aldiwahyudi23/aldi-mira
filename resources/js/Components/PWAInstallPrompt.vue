<template>
  <div v-if="showInstallPrompt" class="pwa-romantic-prompt">
    <div class="pwa-romantic-content">
      <!-- Background decorative elements -->
      <div class="romantic-bg-elements">
        <div class="floating-heart">💖</div>
        <div class="floating-star">✨</div>
        <div class="floating-sparkle">🌟</div>
      </div>
      
      <div class="pwa-romantic-header">
        <div class="romantic-icon-wrapper">
          <div class="romantic-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
          </div>
        </div>
        <button @click="dismissPrompt" class="romantic-close-btn">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
          </svg>
        </button>
      </div>
      
      <div class="pwa-romantic-body">
        <h3 class="romantic-title">Jadikan Kami Bagian dari Harimu 💫</h3>
        <p class="romantic-description">Install aplikasi untuk pengalaman yang lebih intim dan personal, selalu ada di genggamanmu</p>
      </div>
      
      <div class="romantic-features-grid">
        <div class="romantic-feature">
          <div class="feature-emoji">🌹</div>
          <div class="feature-text">
            <span class="feature-title">Eksklusif</span>
            <span class="feature-desc">Hanya untukmu</span>
          </div>
        </div>
        <div class="romantic-feature">
          <div class="feature-emoji">💌</div>
          <div class="feature-text">
            <span class="feature-title">Personal</span>
            <span class="feature-desc">Disesuaikan untukmu</span>
          </div>
        </div>
        <div class="romantic-feature">
          <div class="feature-emoji">🕯️</div>
          <div class="feature-text">
            <span class="feature-title">Intim</span>
            <span class="feature-desc">Pengalaman yang hangat</span>
          </div>
        </div>
        <div class="romantic-feature">
          <div class="feature-emoji">🌙</div>
          <div class="feature-text">
            <span class="feature-title">Selalu Ada</span>
            <span class="feature-desc">Kapan pun dibutuhkan</span>
          </div>
        </div>
      </div>
      
      <div class="romantic-actions">
        <button @click="installApp" class="btn-romantic-install">
          <span class="btn-sparkle">✨</span>
          Pasang di Hati
          <span class="btn-sparkle">✨</span>
        </button>
        <button @click="dismissPrompt" class="btn-romantic-dismiss">
          Mungkin Nanti
        </button>
      </div>
      
      <div class="romantic-footer">
        <div class="romantic-note">
          <span class="note-icon">💝</span>
          <small>Kami akan selalu menantimu</small>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
  name: 'PWARomanticPrompt',
  setup() {
    const showInstallPrompt = ref(false);
    let deferredPrompt = null;

    onMounted(() => {
      const promptDismissed = localStorage.getItem('pwaPromptDismissed');
      if (promptDismissed) return;
      
      window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        // Delay sedikit untuk efek dramatis
        setTimeout(() => {
          showInstallPrompt.value = true;
        }, 1000);
      });

      window.addEventListener('appinstalled', () => {
        showInstallPrompt.value = false;
        deferredPrompt = null;
        console.log('PWA was installed with love 💖');
      });
    });

    const installApp = async () => {
      if (!deferredPrompt) return;
      
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      
      deferredPrompt = null;
      showInstallPrompt.value = false;
      
      if (outcome === 'accepted') {
        // Optional: Trigger confetti or celebration
        console.log('User accepted the install prompt with love!');
      }
    };

    const dismissPrompt = () => {
      showInstallPrompt.value = false;
      localStorage.setItem('pwaPromptDismissed', 'true');
      setTimeout(() => {
        localStorage.removeItem('pwaPromptDismissed');
      }, 24 * 60 * 60 * 1000);
    };

    return {
      showInstallPrompt,
      installApp,
      dismissPrompt
    };
  }
};
</script>

<style scoped>
.pwa-romantic-prompt {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 10000;
  background: linear-gradient(135deg, 
    rgba(255, 182, 193, 0.95) 0%, 
    rgba(255, 105, 180, 0.95) 25%, 
    rgba(199, 21, 133, 0.95) 50%, 
    rgba(147, 112, 219, 0.95) 100%);
  border-radius: 24px;
  box-shadow: 
    0 20px 40px rgba(199, 21, 133, 0.3),
    0 0 0 1px rgba(255, 255, 255, 0.2),
    0 0 60px rgba(255, 182, 193, 0.4);
  padding: 28px;
  max-width: 420px;
  color: white;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  animation: romanticEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  overflow: hidden;
}

@keyframes romanticEntrance {
  0% {
    transform: translateY(100px) scale(0.8) rotate(-5deg);
    opacity: 0;
  }
  70% {
    transform: translateY(-10px) scale(1.02) rotate(2deg);
  }
  100% {
    transform: translateY(0) scale(1) rotate(0);
    opacity: 1;
  }
}

.romantic-bg-elements {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
  overflow: hidden;
}

.floating-heart, .floating-star, .floating-sparkle {
  position: absolute;
  font-size: 24px;
  animation: float 6s ease-in-out infinite;
  opacity: 0.6;
}

.floating-heart {
  top: 10%;
  right: 10%;
  animation-delay: 0s;
}

.floating-star {
  bottom: 20%;
  left: 10%;
  animation-delay: 2s;
}

.floating-sparkle {
  top: 40%;
  left: 15%;
  animation-delay: 4s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
  }
}

.pwa-romantic-content {
  position: relative;
  z-index: 2;
}

.pwa-romantic-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.romantic-icon-wrapper {
  position: relative;
}

.romantic-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.4);
  box-shadow: 0 8px 20px rgba(199, 21, 133, 0.4);
  animation: heartbeat 2s ease-in-out infinite;
}

@keyframes heartbeat {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.romantic-icon svg {
  width: 28px;
  height: 28px;
  color: #ff69b4;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.romantic-close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.romantic-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.romantic-close-btn svg {
  width: 20px;
  height: 20px;
  color: white;
}

.pwa-romantic-body {
  text-align: center;
  margin-bottom: 24px;
}

.romantic-title {
  margin: 0 0 12px 0;
  font-size: 22px;
  font-weight: 700;
  background: linear-gradient(45deg, #fff, #ffe4e9);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.romantic-description {
  margin: 0;
  font-size: 15px;
  opacity: 0.9;
  line-height: 1.6;
  font-weight: 400;
}

.romantic-features-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 28px;
}

.romantic-feature {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: transform 0.3s ease, background 0.3s ease;
}

.romantic-feature:hover {
  transform: translateY(-2px);
  background: rgba(255, 255, 255, 0.15);
}

.feature-emoji {
  font-size: 20px;
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.feature-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.feature-title {
  font-size: 14px;
  font-weight: 600;
}

.feature-desc {
  font-size: 12px;
  opacity: 0.8;
}

.romantic-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.btn-romantic-install {
  background: linear-gradient(135deg, #fff, #ffe4e9);
  color: #c71585;
  border: none;
  padding: 16px 24px;
  border-radius: 16px;
  cursor: pointer;
  font-weight: 700;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 8px 20px rgba(199, 21, 133, 0.4);
  position: relative;
  overflow: hidden;
}

.btn-romantic-install::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  transition: left 0.5s;
}

.btn-romantic-install:hover::before {
  left: 100%;
}

.btn-romantic-install:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 12px 30px rgba(199, 21, 133, 0.6);
}

.btn-sparkle {
  font-size: 18px;
  animation: sparkle 2s ease-in-out infinite;
}

@keyframes sparkle {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.2);
  }
}

.btn-romantic-dismiss {
  background: transparent;
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.4);
  padding: 14px 24px;
  border-radius: 16px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.btn-romantic-dismiss:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.6);
  transform: translateY(-1px);
}

.romantic-footer {
  text-align: center;
}

.romantic-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  opacity: 0.8;
}

.note-icon {
  font-size: 14px;
  animation: gentlePulse 3s ease-in-out infinite;
}

@keyframes gentlePulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.romantic-note small {
  font-size: 12px;
  font-style: italic;
}

/* Responsive design dengan sentuhan romantis */
@media (max-width: 640px) {
  .pwa-romantic-prompt {
    bottom: 0;
    right: 0;
    left: 0;
    max-width: none;
    border-radius: 24px 24px 0 0;
    margin: 0;
    padding: 24px;
  }
  
  .romantic-features-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .romantic-title {
    font-size: 20px;
  }
  
  .romantic-description {
    font-size: 14px;
  }
}

/* Animasi untuk close dengan sentuhan dramatis */
.pwa-romantic-prompt.leaving {
  animation: romanticExit 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes romanticExit {
  from {
    transform: translateY(0) scale(1) rotate(0);
    opacity: 1;
  }
  to {
    transform: translateY(100px) scale(0.8) rotate(5deg);
    opacity: 0;
  }
}
</style>