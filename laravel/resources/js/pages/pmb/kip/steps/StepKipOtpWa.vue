<!-- resources/js/pages/pmb/kip/steps/StepKipOtpWa.vue -->
<script setup>
import { inject, ref, computed } from 'vue'
import {
  ChatBubbleLeftRightIcon,
  PaperAirplaneIcon,
  ArrowPathIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

// safety default
if (!('kode_otp' in form)) form.kode_otp = ''
if (!('otp_terverifikasi' in form)) form.otp_terverifikasi = false

const sending = ref(false)
const verifying = ref(false)
const infoMessage = ref('')
const errorMessage = ref('')

const phoneDisplay = computed(() => {
  const raw = String(form.nomor_hp || '').trim()
  if (!raw) return ''
  if (raw.startsWith('0')) return '+62 ' + raw.slice(1)
  if (raw.startsWith('62')) return '+' + raw
  if (raw.startsWith('+')) return raw
  return raw
})

const extractFirstError = (errorData) => {
  if (errorData?.errors) {
    const first = Object.values(errorData.errors)[0]
    if (Array.isArray(first)) return first[0]
  }
  return errorData?.message || null
}

const sendOtp = async () => {
  errorMessage.value = ''
  infoMessage.value = ''

  if (!form.nomor_hp || !String(form.nomor_hp).trim()) {
    errorMessage.value = 'Isi nomor WhatsApp terlebih dahulu di langkah Akun & WhatsApp.'
    return
  }

  sending.value = true
  try {
    const res = await fetch('/api/pmb/otp/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        nomor_hp: form.nomor_hp, // ✅ key yang diminta backend Anda
        phone: form.nomor_hp,    // ✅ fallback aman
      }),
    })

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      errorMessage.value = extractFirstError(data) || 'Gagal mengirim OTP. Periksa nomor WhatsApp.'
      return
    }

    infoMessage.value = data?.message || 'OTP berhasil dikirim ke WhatsApp.'
    form.otp_terverifikasi = false
    form.kode_otp = ''
  } catch (e) {
    console.error(e)
    errorMessage.value = 'Gagal terhubung ke server saat mengirim OTP.'
  } finally {
    sending.value = false
  }
}

const verifyOtp = async () => {
  errorMessage.value = ''
  infoMessage.value = ''

  if (!form.nomor_hp || !String(form.nomor_hp).trim()) {
    errorMessage.value = 'Nomor WhatsApp belum diisi.'
    return
  }
  if (!form.kode_otp || String(form.kode_otp).trim().length < 4) {
    errorMessage.value = 'Kode OTP belum valid. Masukkan minimal 4 digit.'
    return
  }

  verifying.value = true
  try {
    const res = await fetch('/api/pmb/otp/verify', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        nomor_hp: form.nomor_hp, // ✅ sesuai backend Anda
        phone: form.nomor_hp,    // ✅ fallback
        kode_otp: form.kode_otp, // ✅ umumnya dipakai backend
        code: form.kode_otp,     // ✅ fallback
      }),
    })

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      form.otp_terverifikasi = false
      errorMessage.value = extractFirstError(data) || 'OTP tidak valid atau sudah kedaluwarsa.'
      return
    }

    form.otp_terverifikasi = true
    infoMessage.value = data?.message || 'OTP berhasil diverifikasi.'
  } catch (e) {
    console.error(e)
    form.otp_terverifikasi = false
    errorMessage.value = 'Gagal terhubung ke server saat verifikasi OTP.'
  } finally {
    verifying.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-start gap-2">
      <div
        class="h-8 w-8 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600
               dark:bg-sky-500/20 dark:text-sky-300"
      >
        <ChatBubbleLeftRightIcon class="w-4 h-4" />
      </div>
      <div>
        <h2 class="text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200">
          Verifikasi Kode OTP WhatsApp
        </h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          OTP akan dikirim ke nomor WhatsApp yang kamu isi di langkah <strong>Akun &amp; WhatsApp</strong>.
        </p>
      </div>
    </div>

    <div
      class="rounded-2xl border border-sky-500/20
             bg-gradient-to-r from-sky-50 via-white to-sky-100
             dark:from-slate-950 dark:via-slate-950 dark:to-sky-900/70
             px-4 py-3 text-xs flex flex-col gap-1.5"
    >
      <p class="text-[11px] text-sky-700 dark:text-sky-200/80">Nomor WhatsApp terdaftar</p>
      <p class="text-sm font-semibold text-sky-900 dark:text-sky-50">
        {{ phoneDisplay || 'Belum ada nomor, isi di langkah Akun & WhatsApp.' }}
      </p>
      <p class="text-[10px] text-slate-600 dark:text-slate-300">
        Pastikan nomor sudah benar dan aktif, karena OTP dan informasi penting akan dikirim ke nomor ini.
      </p>
    </div>

    <div class="space-y-2">
      <button
        type="button"
        @click="sendOtp"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-full
               text-xs md:text-sm font-semibold
               bg-sky-500 text-white hover:bg-sky-400
               disabled:opacity-60 disabled:cursor-not-allowed
               transition-all"
        :disabled="sending"
      >
        <ArrowPathIcon v-if="sending" class="w-4 h-4 animate-spin" />
        <PaperAirplaneIcon v-else class="w-4 h-4" />
        <span>{{ sending ? 'Mengirim OTP...' : 'Kirim Kode OTP ke WhatsApp' }}</span>
      </button>

      <p v-if="infoMessage" class="text-[11px] text-emerald-600 dark:text-emerald-300">
        {{ infoMessage }}
      </p>
      <p v-if="errorMessage" class="text-[11px] text-rose-600 dark:text-rose-300">
        {{ errorMessage }}
      </p>
    </div>

    <div class="space-y-2 max-w-xs">
      <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
        Kode OTP
      </label>
      <input
        v-model="form.kode_otp"
        maxlength="6"
        inputmode="numeric"
        class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
               bg-white dark:bg-slate-900/80
               px-3 py-2.5 text-sm tracking-[0.35em] text-center"
        placeholder="••••••"
      />

      <button
        type="button"
        @click="verifyOtp"
        class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-full
               text-xs font-semibold
               bg-emerald-500 text-white hover:bg-emerald-400
               disabled:opacity-60 disabled:cursor-not-allowed
               transition-all"
        :disabled="verifying"
      >
        <ArrowPathIcon v-if="verifying" class="w-4 h-4 animate-spin" />
        <ShieldCheckIcon v-else class="w-4 h-4" />
        {{ verifying ? 'Memverifikasi...' : 'Verifikasi OTP' }}
      </button>

      <p v-if="form.otp_terverifikasi" class="mt-1 text-[11px] text-emerald-600">
        OTP sudah terverifikasi. Kamu bisa lanjut ke langkah <strong>Upload Berkas</strong>.
      </p>
    </div>
  </div>
</template>
