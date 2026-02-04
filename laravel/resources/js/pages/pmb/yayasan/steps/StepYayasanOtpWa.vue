<!-- resources/js/Components/pmb/yayasan/steps/StepYayasanOtpWa.vue -->
<script setup>
import { inject, ref, computed, onBeforeUnmount } from 'vue'
import {
  ChatBubbleLeftRightIcon,
  PaperAirplaneIcon,
  ArrowPathIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

const sending = ref(false)
const verifying = ref(false)
const infoMessage = ref('')
const infoType = ref('info') // 'info' | 'success' | 'error'

// cooldown resend OTP (agar tidak spam)
const cooldown = ref(0)
let cooldownTimer = null

const startCooldown = (sec = 30) => {
  cooldown.value = sec
  if (cooldownTimer) clearInterval(cooldownTimer)
  cooldownTimer = setInterval(() => {
    cooldown.value = Math.max(0, cooldown.value - 1)
    if (cooldown.value === 0 && cooldownTimer) {
      clearInterval(cooldownTimer)
      cooldownTimer = null
    }
  }, 1000)
}

onBeforeUnmount(() => {
  if (cooldownTimer) clearInterval(cooldownTimer)
})

/**
 * Normalisasi nomor WA agar aman:
 * - buang karakter selain digit
 * - jika mulai 0 -> jadi 62...
 * - jika mulai 8 -> jadi 628...
 * - jika sudah 62... tetap
 */
const normalizePhone = (raw) => {
  const digits = String(raw || '').replace(/[^\d]/g, '')
  if (!digits) return ''
  if (digits.startsWith('0')) return '62' + digits.slice(1)
  if (digits.startsWith('8')) return '62' + digits
  if (digits.startsWith('62')) return digits
  // fallback: kembalikan apa adanya
  return digits
}

const phoneNormalized = computed(() => normalizePhone(form.nomor_hp))

const phoneDisplay = computed(() => {
  const p = phoneNormalized.value
  if (!p) return 'Belum ada nomor, isi di langkah Akun.'
  // tampilkan +62 xxxx...
  return '+' + p
})

const setMsg = (type, msg) => {
  infoType.value = type
  infoMessage.value = msg
}

const sendOtp = async () => {
  const phone = phoneNormalized.value
  if (!phone) {
    setMsg('error', 'Isi nomor WhatsApp terlebih dahulu di langkah Akun.')
    return
  }

  if (sending.value) return
  if (cooldown.value > 0) {
    setMsg('info', `Tunggu ${cooldown.value} detik sebelum kirim OTP lagi.`)
    return
  }

  sending.value = true
  setMsg('info', '')

  try {
    const res = await fetch('/api/pmb/otp/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        // kirim beberapa key agar kompatibel dengan backend yang bervariasi
        nomor_hp: phone,
        phone: phone,
        wa: phone,
        no_wa: phone,
        channel: 'whatsapp',
        jalur: form.jalur_pendaftaran || 'Beasiswa Yayasan',
      }),
    })

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      const msg =
        data?.message ||
        (data?.errors ? Object.values(data.errors)?.[0]?.[0] : null) ||
        'Gagal mengirim OTP. Silakan coba lagi.'
      setMsg('error', msg)
      return
    }

    // Simpan session jika backend mengirimkan
    form.otp_session_id = data?.data?.session_id || data?.session_id || form.otp_session_id || ''
    form.otp_expires_at = data?.data?.expires_at || data?.expires_at || form.otp_expires_at || ''

    // reset status verifikasi saat kirim ulang
    form.otp_terverifikasi = false
    form.kode_otp = ''

    setMsg('success', data?.message || 'OTP berhasil dikirim ke WhatsApp. Silakan cek pesan masuk.')
    startCooldown(30)
  } catch (e) {
    setMsg('error', 'Gagal terhubung ke server saat mengirim OTP. Silakan coba lagi.')
  } finally {
    sending.value = false
  }
}

const verifyOtp = async () => {
  const phone = phoneNormalized.value
  const otp = String(form.kode_otp || '').replace(/[^\d]/g, '')

  if (!phone) {
    setMsg('error', 'Nomor WhatsApp belum diisi. Kembali ke langkah Akun.')
    form.otp_terverifikasi = false
    return
  }

  if (!otp || otp.length < 4) {
    setMsg('error', 'Kode OTP belum valid. Masukkan minimal 4 digit.')
    form.otp_terverifikasi = false
    return
  }

  if (verifying.value) return
  verifying.value = true
  setMsg('info', '')

  try {
    const res = await fetch('/api/pmb/otp/verify', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        nomor_hp: phone,
        phone: phone,
        wa: phone,
        no_wa: phone,
        otp: otp,
        kode_otp: otp,

        // jika backend Anda pakai session
        session_id: form.otp_session_id || '',
        otp_session_id: form.otp_session_id || '',

        jalur: form.jalur_pendaftaran || 'Beasiswa Yayasan',
      }),
    })

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      const msg =
        data?.message ||
        (data?.errors ? Object.values(data.errors)?.[0]?.[0] : null) ||
        'OTP tidak valid atau sudah kedaluwarsa.'
      form.otp_terverifikasi = false
      setMsg('error', msg)
      return
    }

    // backend bisa mengirim status boolean/flag
    const verified =
      data?.data?.verified === true ||
      data?.verified === true ||
      data?.data?.otp_terverifikasi === true ||
      data?.otp_terverifikasi === true ||
      true // kalau response OK, anggap verified (umum di banyak API)

    form.otp_terverifikasi = !!verified

    // update session/expiry kalau ada
    form.otp_session_id = data?.data?.session_id || data?.session_id || form.otp_session_id || ''
    form.otp_expires_at = data?.data?.expires_at || data?.expires_at || form.otp_expires_at || ''

    setMsg('success', data?.message || 'Kode OTP berhasil diverifikasi. Silakan lanjut ke langkah berikutnya.')
  } catch (e) {
    form.otp_terverifikasi = false
    setMsg('error', 'Gagal terhubung ke server saat verifikasi OTP. Silakan coba lagi.')
  } finally {
    verifying.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-start gap-2">
      <div
        class="h-8 w-8 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600
               dark:bg-sky-500/20 dark:text-sky-300"
      >
        <ChatBubbleLeftRightIcon class="w-4 h-4" />
      </div>
      <div>
        <h2
          class="text-sm font-semibold tracking-wide
                 text-slate-700 dark:text-slate-200"
        >
          Verifikasi Kode OTP WhatsApp
        </h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Kami akan mengirim kode OTP ke nomor WhatsApp yang kamu isi di langkah
          <strong>Akun &amp; WhatsApp</strong>. Proses kirim & verifikasi dilakukan melalui API Laravel (WhatsApp Gateway Fonte).
        </p>
      </div>
    </div>

    <!-- Kartu info nomor WA (LIGHT + DARK) -->
    <div
      class="rounded-2xl border border-sky-500/20
             bg-gradient-to-r from-sky-50 via-white to-sky-100
             dark:from-slate-950 dark:via-slate-950 dark:to-sky-900/70
             px-4 py-3 text-xs flex flex-col gap-1.5
             shadow-[0_16px_40px_rgba(15,23,42,0.12)]
             dark:shadow-[0_16px_40px_rgba(15,23,42,0.65)]"
    >
      <p class="text-[11px] text-sky-700 dark:text-sky-200/80">
        Nomor WhatsApp terdaftar
      </p>

      <p class="text-sm font-semibold text-sky-900 dark:text-sky-50">
        {{ phoneDisplay }}
      </p>

      <p class="text-[10px] text-slate-600 dark:text-slate-300">
        Pastikan nomor sudah benar dan aktif, karena OTP dan informasi penting
        akan dikirim ke nomor ini.
      </p>
    </div>

    <!-- Kirim OTP -->
    <div class="space-y-2">
      <button
        type="button"
        @click="sendOtp"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-full
               text-xs md:text-sm font-semibold
               bg-sky-500 text-white hover:bg-sky-400
               shadow-[0_10px_25px_rgba(56,189,248,0.45)]
               disabled:opacity-60 disabled:cursor-not-allowed
               transition-all"
        :disabled="sending || cooldown > 0"
      >
        <ArrowPathIcon v-if="sending" class="w-4 h-4 animate-spin" />
        <PaperAirplaneIcon v-else class="w-4 h-4" />
        <span>
          {{
            sending
              ? 'Mengirim OTP...'
              : cooldown > 0
                ? `Kirim OTP lagi (${cooldown}s)`
                : 'Kirim Kode OTP ke WhatsApp'
          }}
        </span>
      </button>

      <p
        v-if="infoMessage"
        class="text-[11px]"
        :class="infoType === 'error'
          ? 'text-rose-500 dark:text-rose-300'
          : infoType === 'success'
            ? 'text-emerald-500 dark:text-emerald-300'
            : 'text-sky-500 dark:text-sky-300'"
      >
        {{ infoMessage }}
      </p>
    </div>

    <!-- Input OTP -->
    <div class="space-y-2 max-w-xs">
      <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
        Kode OTP (6 digit)
      </label>
      <input
        v-model="form.kode_otp"
        maxlength="6"
        inputmode="numeric"
        class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
               bg-white dark:bg-slate-900/80
               px-3 py-2.5 text-sm tracking-[0.35em] text-center
               placeholder-slate-400 dark:placeholder-white
               focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
               dark:focus:ring-sky-500"
        placeholder="••••••"
      />

      <button
        type="button"
        @click="verifyOtp"
        class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-full
               text-xs font-semibold
               bg-emerald-500 text-white hover:bg-emerald-400
               shadow-[0_8px_20px_rgba(16,185,129,0.45)]
               transition-all
               disabled:opacity-60 disabled:cursor-not-allowed"
        :disabled="verifying"
      >
        <ArrowPathIcon v-if="verifying" class="w-4 h-4 animate-spin" />
        <ShieldCheckIcon v-else class="w-4 h-4" />
        Verifikasi OTP
      </button>

      <p
        v-if="form.otp_terverifikasi"
        class="mt-1 text-[11px] text-emerald-500"
      >
        OTP sudah terverifikasi. Kamu bisa melanjutkan ke langkah
        <strong>Pembayaran</strong>.
      </p>
    </div>
  </div>
</template>
