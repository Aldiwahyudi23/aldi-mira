<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import TextInput from '@/Components/Form/TextInput.vue';

const props = defineProps({
    requiresConfirmation: Boolean,
});

const page = usePage();
const user = page.props.auth.user;

// Parse theme dari user
const userTheme = user?.theme ? JSON.parse(user.theme) : null

const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);
const showRecoveryCodesModal = ref(false);
const showQrModal = ref(false);

const confirmationForm = useForm({
    code: '',
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

const twoFactorEnabled = computed(
    () => ! enabling.value && user?.two_factor_enabled,
);

watch(twoFactorEnabled, () => {
    if (! twoFactorEnabled.value) {
        confirmationForm.reset();
        confirmationForm.clearErrors();
    }
});

const enableTwoFactorAuthentication = () => {
    enabling.value = true;

    router.post(route('two-factor.enable'), {}, {
        preserveScroll: true,
        onSuccess: () => Promise.all([
            showQrCode(),
            showSetupKey(),
            showRecoveryCodes(),
        ]),
        onFinish: () => {
            enabling.value = false;
            confirming.value = props.requiresConfirmation;
            if (confirming.value) {
                showQrModal.value = true;
            }
        },
    });
};

const showQrCode = () => {
    return axios.get(route('two-factor.qr-code')).then(response => {
        qrCode.value = response.data.svg;
    });
};

const showSetupKey = () => {
    return axios.get(route('two-factor.secret-key')).then(response => {
        setupKey.value = response.data.secretKey;
    });
}

const showRecoveryCodes = () => {
    return axios.get(route('two-factor.recovery-codes')).then(response => {
        recoveryCodes.value = response.data;
    });
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post(route('two-factor.confirm'), {
        errorBag: "confirmTwoFactorAuthentication",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
            showQrModal.value = false;
        },
    });
};

const regenerateRecoveryCodes = () => {
    axios
        .post(route('two-factor.recovery-codes'))
        .then(() => {
            showRecoveryCodes();
            showRecoveryCodesModal.value = true;
        });
};

const disableTwoFactorAuthentication = () => {
    disabling.value = true;

    router.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            disabling.value = false;
            confirming.value = false;
            showQrModal.value = false;
            showRecoveryCodesModal.value = false;
        },
    });
};

const openRecoveryCodes = () => {
    showRecoveryCodesModal.value = true;
};

const closeQrModal = () => {
    showQrModal.value = false;
    if (confirming.value) {
        disableTwoFactorAuthentication();
    }
};

const closeRecoveryCodesModal = () => {
    showRecoveryCodesModal.value = false;
};
</script>

<template>
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-purple-500/10 to-indigo-500/10 py-4 md:py-6 px-3 md:px-4 border-b border-purple-100">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 md:w-16 md:h-16 bg-gradient-to-r from-purple-400 to-indigo-400 rounded-full mb-3 md:mb-4 shadow-lg">
                    <span class="text-xl md:text-2xl text-white">🔐</span>
                </div>
                <h2 class="text-lg md:text-2xl font-bold text-gray-800 mb-2">Autentikasi Dua Faktor</h2>
                <p class="text-gray-600 text-sm md:text-base flex items-center justify-center gap-1 md:gap-2">
                    <span class="text-purple-400 text-sm md:text-base">🛡️</span>
                    Tambahkan keamanan tambahan ke akun Anda menggunakan autentikasi dua faktor
                </p>
            </div>
        </div>

        <!-- Content Section -->
        <div class="p-3 md:p-4">
            <!-- Status Information -->
            <div class="mb-3 md:mb-4">
                <h3 v-if="twoFactorEnabled && ! confirming" class="text-base md:text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-green-500 text-lg md:text-xl">✅</span>
                    Anda telah mengaktifkan autentikasi dua faktor.
                </h3>

                <h3 v-else-if="twoFactorEnabled && confirming" class="text-base md:text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-amber-500 text-lg md:text-xl">⏳</span>
                    Selesaikan pengaktifan autentikasi dua faktor.
                </h3>

                <h3 v-else class="text-base md:text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-gray-500 text-lg md:text-xl">🔒</span>
                    Anda belum mengaktifkan autentikasi dua faktor.
                </h3>

                <div class="mt-2 text-xs md:text-sm text-gray-600">
                    <p class="flex items-start gap-2">
                        <span class="text-purple-400 mt-0.5">💡</span>
                        <span>
                            Ketika autentikasi dua faktor diaktifkan, Anda akan diminta untuk memasukkan token acak yang aman selama autentikasi. Anda dapat mengambil token ini dari aplikasi Google Authenticator di ponsel Anda.
                        </span>
                    </p>
                </div>
            </div>

            <!-- Two Factor Content -->
            <div v-if="twoFactorEnabled">
                <!-- QR Code and Setup Information -->
                <div v-if="qrCode" class="space-y-3 md:space-y-4">
                    <div class="p-3 md:p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl md:rounded-2xl border border-blue-200">
                        <p v-if="confirming" class="text-sm md:text-base text-gray-700 font-medium flex items-start gap-2">
                            <span class="text-blue-500 text-lg mt-0.5">📱</span>
                            <span>Untuk menyelesaikan pengaktifan autentikasi dua faktor, pindai kode QR berikut menggunakan aplikasi autentikator di ponsel Anda atau masukkan kunci setup dan berikan kode OTP yang dihasilkan.</span>
                        </p>

                        <p v-else class="text-sm md:text-base text-gray-700 font-medium flex items-start gap-2">
                            <span class="text-blue-500 text-lg mt-0.5">✅</span>
                            <span>Autentikasi dua faktor sekarang telah diaktifkan. Pindai kode QR berikut menggunakan aplikasi autentikator di ponsel Anda atau masukkan kunci setup.</span>
                        </p>
                    </div>

                    <!-- QR Code -->
                    <div class="text-center">
                        <div class="inline-block p-3 md:p-4 bg-white rounded-xl md:rounded-2xl border border-gray-200 shadow-lg">
                            <div v-html="qrCode" class="max-w-[200px] md:max-w-[250px] mx-auto" />
                        </div>
                    </div>

                    <!-- Setup Key -->
                    <div v-if="setupKey" class="p-3 md:p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl md:rounded-2xl border border-green-200">
                        <p class="text-sm md:text-base text-gray-700 font-medium mb-2 flex items-center gap-2">
                            <span class="text-green-500">🔑</span>
                            Kunci Setup:
                        </p>
                        <div class="bg-white/80 p-3 md:p-4 rounded-lg md:rounded-xl border border-gray-200">
                            <code class="text-xs md:text-sm font-mono text-gray-800 break-all">{{ setupKey }}</code>
                        </div>
                    </div>

                    <!-- Confirmation Code Input -->
                    <div v-if="confirming" class="space-y-2 md:space-y-3">
                        <TextInput
                            v-model="confirmationForm.code"
                            label="Kode Verifikasi"
                            placeholder="Masukkan kode 6 digit dari aplikasi"
                            :error="confirmationForm.errors.code"
                            icon="🔢"
                            required
                            inputmode="numeric"
                            autofocus
                            autocomplete="one-time-code"
                            @keyup.enter="confirmTwoFactorAuthentication"
                            class="max-w-xs mx-auto"
                        />
                    </div>
                </div>

                <!-- Recovery Codes -->
                <div v-if="recoveryCodes.length > 0 && ! confirming" class="mt-3 md:mt-4">
                    <div class="p-3 md:p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl md:rounded-2xl border border-amber-200">
                        <p class="text-sm md:text-base text-gray-700 font-medium flex items-start gap-2">
                            <span class="text-amber-500 text-lg mt-0.5">💾</span>
                            <span>Simpan kode pemulihan ini di pengelola kata sandi yang aman. Mereka dapat digunakan untuk memulihkan akses ke akun Anda jika perangkat autentikasi dua faktor hilang.</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 mt-4 md:mt-5 pt-3 md:pt-4 border-t border-gray-100">
                <div v-if="! twoFactorEnabled" class="flex-1">
                    <BaseButton
                        @click="enableTwoFactorAuthentication"
                        :loading="enabling"
                        :class="buttonClasses"
                        class="w-full justify-center"
                    >
                        <template #icon>🔐</template>
                        Aktifkan 2FA
                    </BaseButton>
                </div>

                <div v-else class="flex flex-col sm:flex-row gap-2 md:gap-3 w-full">
                    <BaseButton
                        v-if="confirming"
                        @click="confirmTwoFactorAuthentication"
                        :loading="confirmationForm.processing"
                        :class="buttonClasses"
                        class="flex-1 justify-center"
                    >
                        <template #icon>✅</template>
                        Konfirmasi
                    </BaseButton>

                    <BaseButton
                        v-if="recoveryCodes.length > 0 && ! confirming"
                        @click="regenerateRecoveryCodes"
                        variant="secondary"
                        class="flex-1 justify-center"
                    >
                        <template #icon>🔄</template>
                        Regenerasi Kode
                    </BaseButton>

                    <BaseButton
                        v-if="recoveryCodes.length === 0 && ! confirming"
                        @click="openRecoveryCodes"
                        variant="secondary"
                        class="flex-1 justify-center"
                    >
                        <template #icon>👁️</template>
                        Lihat Kode
                    </BaseButton>

                    <BaseButton
                        v-if="confirming"
                        @click="closeQrModal"
                        variant="secondary"
                        class="flex-1 justify-center"
                    >
                        <template #icon>↩️</template>
                        Batal
                    </BaseButton>

                    <BaseButton
                        v-if="! confirming"
                        @click="disableTwoFactorAuthentication"
                        :loading="disabling"
                        variant="danger"
                        class="flex-1 justify-center"
                    >
                        <template #icon>🚫</template>
                        Nonaktifkan
                    </BaseButton>
                </div>
            </div>
        </div>

    <!-- QR Code Modal -->
    <BaseModal
        v-model:show="showQrModal"
        :title="'Setup Autentikasi Dua Faktor'"
        :description="'Scan QR code dan masukkan kode verifikasi untuk mengaktifkan 2FA'"
        :icon="'📱'"
        :confirm-text="'Konfirmasi'"
        :confirm-loading="confirmationForm.processing"
        :show-cancel="true"
        :cancel-text="'Batal'"
        @confirm="confirmTwoFactorAuthentication"
        @close="closeQrModal"
        size="md"
    >
        <div class="space-y-4 md:space-y-6">
            <!-- QR Code -->
            <div class="text-center">
                <div class="inline-block p-4 bg-white rounded-2xl border border-gray-200 shadow-lg">
                    <div v-html="qrCode" class="max-w-[200px] md:max-w-[250px] mx-auto" />
                </div>
            </div>

            <!-- Setup Key -->
            <div v-if="setupKey" class="p-3 md:p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl md:rounded-2xl border border-green-200">
                <p class="text-sm md:text-base text-gray-700 font-medium mb-2 flex items-center gap-2">
                    <span class="text-green-500">🔑</span>
                    Kunci Setup:
                </p>
                <div class="bg-white/80 p-3 md:p-4 rounded-lg md:rounded-xl border border-gray-200">
                    <code class="text-xs md:text-sm font-mono text-gray-800 break-all">{{ setupKey }}</code>
                </div>
            </div>

            <!-- Confirmation Code Input -->
            <TextInput
                v-model="confirmationForm.code"
                label="Kode Verifikasi 2FA"
                placeholder="Masukkan kode 6 digit dari aplikasi"
                :error="confirmationForm.errors.code"
                icon="🔢"
                required
                inputmode="numeric"
                autofocus
                autocomplete="one-time-code"
                @keyup.enter="confirmTwoFactorAuthentication"
            />

            <div class="p-3 md:p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl md:rounded-2xl border border-blue-200">
                <p class="text-xs md:text-sm text-gray-600 flex items-start gap-2">
                    <span class="text-blue-500 text-base mt-0.5">💡</span>
                    <span>Download aplikasi Google Authenticator atau Authy di smartphone Anda, scan QR code di atas, lalu masukkan kode 6 digit yang muncul di aplikasi.</span>
                </p>
            </div>
        </div>
    </BaseModal>

    <!-- Recovery Codes Modal -->
    <BaseModal
        v-model:show="showRecoveryCodesModal"
        :title="'Kode Pemulihan 2FA'"
        :description="'Simpan kode-kode ini di tempat yang aman untuk memulihkan akses akun'"
        :icon="'💾'"
        :confirm-text="'Tutup'"
        :show-cancel="false"
        @confirm="closeRecoveryCodesModal"
        size="md"
    >
        <div class="space-y-4 md:space-y-6">
            <div class="p-3 md:p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl md:rounded-2xl border border-amber-200">
                <p class="text-sm md:text-base text-gray-700 font-medium flex items-start gap-2">
                    <span class="text-amber-500 text-lg mt-0.5">⚠️</span>
                    <span>Simpan kode pemulihan ini di pengelola kata sandi yang aman. Mereka dapat digunakan untuk memulihkan akses ke akun Anda jika perangkat autentikasi dua faktor hilang.</span>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-3 max-h-60 overflow-y-auto p-2">
                <div 
                    v-for="(code, index) in recoveryCodes" 
                    :key="index"
                    class="p-2 md:p-3 bg-white rounded-lg md:rounded-xl border border-gray-200 text-center font-mono text-xs md:text-sm text-gray-800 break-all"
                >
                    {{ code }}
                </div>
            </div>

            <div class="flex gap-2 md:gap-3">
                <BaseButton
                    @click="regenerateRecoveryCodes"
                    variant="secondary"
                    class="flex-1 justify-center"
                >
                    <template #icon>🔄</template>
                    Regenerasi
                </BaseButton>
                
                <BaseButton
                    @click="closeRecoveryCodesModal"
                    :class="buttonClasses"
                    class="flex-1 justify-center"
                >
                    <template #icon>✅</template>
                    Tutup
                </BaseButton>
            </div>
        </div>
    </BaseModal>
</template>