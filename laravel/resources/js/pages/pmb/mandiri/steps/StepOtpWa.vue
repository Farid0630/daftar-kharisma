<!-- resources/js/Components/pmb/steps/StepOtpWa.vue -->
<script setup>
import { inject, ref, computed, onBeforeUnmount } from 'vue'
import {
  ChatBubbleBottomCenterTextIcon,
  PaperAirplaneIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

// safety fields
if (!('kode_otp' in form)) form.kode_otp = ''
if (!('otp_terverifikasi' in form)) form.otp_terverifikasi = false
if (!('otp_session_id' in form)) form.otp_session_id = null

const otpSent = ref(false)
const sending = ref(false)
const verifying = ref(false)
const errorMsg = ref('')
const infoMsg = ref('')

const ttlLeft = ref(0)
let ttlTimer = null

const formattedWaNumber = computed(() => {
  const raw = (form.nomor_hp || '').replace(/\D/g, '')
  if (!raw) return '-'
  if (raw.startsWith('0')) return '+62 ' + raw.slice(1)
  if (raw.startsWith('62')) return '+62 ' + raw.slice(2)
  return '+62 ' + raw
})

const canSend = computed(() => {
  const raw = (form.nomor_hp || '').replace(/\D/g, '')
  return raw.length >= 9
})

// ✅ Backend umumnya tidak butuh otp_session_id untuk verify.
// Kalau backend Anda mengirim otp_session_id, tetap kita ikutkan di request.
const canVerify = computed(() => {
  const code = (form.kode_otp || '').trim()
  return code.length >= 4
})

const startTtl = (seconds) => {
  ttlLeft.value = Number(seconds || 0)
  if (ttlTimer) clearInterval(ttlTimer)
  ttlTimer = setInterval(() => {
    ttlLeft.value = Math.max(0, ttlLeft.value - 1)
    if (ttlLeft.value <= 0) {
      clearInterval(ttlTimer)
      ttlTimer = null
    }
  }, 1000)
}

const sendOtp = async () => {
  errorMsg.value = ''
  infoMsg.value = ''
  otpSent.value = false
  form.otp_terverifikasi = false
  form.kode_otp = ''

  if (!canSend.value) {
    errorMsg.value = 'Nomor WhatsApp belum valid.'
    return
  }

  sending.value = true
  try {
    const res = await fetch('/api/pmb/otp/send', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      // ✅ sesuaikan key dengan backend: nomor_hp
      body: JSON.stringify({ nomor_hp: form.nomor_hp }),
    })

    const json = await res.json().catch(() => ({}))

    if (!res.ok) {
      // ✅ dukung format Laravel validation errors
      errorMsg.value =
        json?.errors?.nomor_hp?.[0] ||
        json?.message ||
        'Gagal mengirim OTP.'
      return
    }

    // ✅ fleksibel: backend bisa mengembalikan otp_session_id / session_id
    form.otp_session_id = json?.otp_session_id ?? json?.session_id ?? null

    otpSent.value = true
    infoMsg.value = json?.message || 'OTP dikirim ke WhatsApp.'

    // ✅ fleksibel: backend bisa mengembalikan ttl_seconds / ttl
    const ttl = json?.ttl_seconds ?? json?.ttl ?? 0
    startTtl(ttl)
  } catch (e) {
    errorMsg.value = 'Gagal terhubung ke server OTP.'
  } finally {
    sending.value = false
  }
}

const verifyOtp = async () => {
  errorMsg.value = ''
  infoMsg.value = ''

  if (!canVerify.value) {
    errorMsg.value = 'Masukkan kode OTP dengan benar.'
    return
  }

  verifying.value = true
  try {
    const payload = {
      // ✅ sesuaikan key dengan backend
      nomor_hp: form.nomor_hp,
      kode_otp: form.kode_otp,
    }

    // ✅ kalau backend Anda memakai session id, ikutkan saja (aman walau backend mengabaikan)
    if (form.otp_session_id) payload.otp_session_id = form.otp_session_id

    const res = await fetch('/api/pmb/otp/verify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
    })

    const json = await res.json().catch(() => ({}))

    if (!res.ok) {
      form.otp_terverifikasi = false
      errorMsg.value =
        json?.errors?.kode_otp?.[0] ||
        json?.errors?.nomor_hp?.[0] ||
        json?.message ||
        'OTP tidak valid.'
      return
    }

    form.otp_terverifikasi = true
    infoMsg.value = json?.message || 'OTP berhasil diverifikasi.'
  } catch (e) {
    errorMsg.value = 'Gagal terhubung ke server verifikasi OTP.'
  } finally {
    verifying.value = false
  }
}

onBeforeUnmount(() => {
  if (ttlTimer) clearInterval(ttlTimer)
})
</script>

<template>
  <div class="space-y-5">
    <!-- HEADER -->
    <div class="flex items-start gap-3">
      <div
        class="h-9 w-9 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600
               dark:bg-sky-500/20 dark:text-sky-300"
      >
        <ChatBubbleBottomCenterTextIcon class="w-4 h-4" />
      </div>
      <div>
        <h2 class="text-sm font-semibold tracking-wide text-slate-800 dark:text-slate-50">
          Verifikasi Kode OTP WhatsApp
        </h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Sistem akan mengirim kode OTP ke nomor WhatsApp yang Anda masukkan pada langkah Akun & WhatsApp.
        </p>
      </div>
    </div>

    <!-- CARD NOMOR WHATSAPP -->
    <div
      class="rounded-2xl border border-sky-500/15
             bg-gradient-to-r from-sky-50 via-sky-100 to-sky-200
             dark:from-slate-900 dark:via-slate-950 dark:to-sky-900/70
             px-4 py-3 md:px-5 md:py-4
             shadow-[0_16px_40px_rgba(15,23,42,0.12)]
             dark:shadow-[0_18px_45px_rgba(15,23,42,0.8)]
             flex flex-col gap-2"
    >
      <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-800 dark:text-sky-200">
        Nomor WhatsApp terdaftar
      </p>

      <div class="flex items-baseline justify-between gap-3">
        <div>
          <p class="text-lg md:text-xl font-semibold tracking-wide text-slate-900 dark:text-slate-50">
            {{ formattedWaNumber }}
          </p>
          <p class="text-[11px] text-slate-600 dark:text-slate-300">
            Pastikan nomor benar dan aktif.
          </p>
        </div>

        <span
          v-if="form.otp_terverifikasi"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                 text-[10px] font-semibold
                 bg-emerald-500/10 text-emerald-600 border border-emerald-400/60
                 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-400/70"
        >
          <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
          OTP terverifikasi
        </span>
      </div>
    </div>

    <!-- ALERT -->
    <div v-if="errorMsg" class="text-[11px] text-rose-600 dark:text-rose-300">
      {{ errorMsg }}
    </div>
    <div v-if="infoMsg" class="text-[11px] text-sky-700 dark:text-sky-300">
      {{ infoMsg }}
      <span v-if="ttlLeft > 0" class="font-semibold"> (berlaku {{ ttlLeft }} detik)</span>
    </div>

    <!-- KIRIM OTP -->
    <div>
      <button
        type="button"
        @click="sendOtp"
        :disabled="sending || !canSend"
        class="inline-flex items-center gap-2 px-5 md:px-6 py-2.5 rounded-full
               text-xs md:text-sm font-semibold
               bg-gradient-to-r from-sky-400 via-cyan-400 to-emerald-400
               text-white shadow-[0_14px_35px_rgba(56,189,248,0.65)]
               hover:from-sky-300 hover:via-cyan-400 hover:to-emerald-400
               disabled:opacity-70 disabled:cursor-not-allowed
               transition-all"
      >
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/10 border border-sky-100/40">
          <PaperAirplaneIcon class="w-3.5 h-3.5 -rotate-45" />
        </span>
        <span>{{ sending ? 'Mengirim OTP…' : 'Kirim Kode OTP ke WhatsApp' }}</span>
      </button>

      <p v-if="otpSent && !form.otp_terverifikasi" class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
        OTP sudah dikirim. Masukkan kodenya lalu verifikasi.
      </p>
    </div>

    <!-- INPUT OTP -->
    <div class="space-y-2">
      <p class="text-xs font-medium text-slate-700 dark:text-slate-200">
        Kode OTP (6 digit)
      </p>

      <div
        class="inline-flex items-center gap-3
               rounded-2xl border border-slate-300/80 dark:border-slate-700/80
               bg-white/70 dark:bg-slate-900/70
               px-4 py-3"
      >
        <input
          v-model="form.kode_otp"
          type="text"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="6"
          class="w-44 sm:w-52 md:w-56
                 text-center text-base sm:text-lg
                 tracking-[0.45em]
                 bg-transparent border-0 outline-none
                 text-slate-900 dark:text-slate-50
                 placeholder-slate-400 dark:placeholder-slate-500"
          placeholder="••••••"
        />

        <span
          class="text-[11px] px-2 py-1 rounded-full
                 border border-slate-200/80 dark:border-slate-700/70
                 bg-slate-50 dark:bg-slate-950/50
                 text-slate-500 dark:text-slate-300"
        >
          6 digit
        </span>
      </div>
    </div>

    <!-- VERIFIKASI -->
    <div>
      <button
        type="button"
        @click="verifyOtp"
        :disabled="!canVerify || verifying"
        class="inline-flex items-center gap-2 px-5 md:px-6 py-2.5 rounded-full
               text-xs md:text-sm font-semibold
               bg-gradient-to-r from-emerald-500 to-teal-500
               text-white shadow-[0_12px_30px_rgba(16,185,129,0.6)]
               hover:from-emerald-400 hover:to-teal-500
               disabled:opacity-60 disabled:cursor-not-allowed
               transition-all"
      >
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/10 border border-emerald-100/40">
          <ShieldCheckIcon class="w-3.5 h-3.5" />
        </span>
        <span>
          {{ verifying ? 'Memverifikasi…' : (form.otp_terverifikasi ? 'OTP sudah terverifikasi' : 'Verifikasi OTP') }}
        </span>
      </button>

      <p v-if="form.otp_terververifikasi" class="mt-1 text-[11px] text-emerald-500 dark:text-emerald-300">
        OTP berhasil diverifikasi. Silakan lanjut ke langkah berikutnya.
      </p>
    </div>
  </div>
</template>
