<!-- resources/js/Components/pmb/steps/StepMandiriPayment.vue -->
<script setup>
import { inject, computed, ref, watch, onBeforeUnmount } from 'vue'
import {
  CreditCardIcon,
  BanknotesIcon,
  DevicePhoneMobileIcon,
  DocumentDuplicateIcon,
} from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

// safety: fields yang dipakai
if (!('setuju_biaya_formulir' in form)) form.setuju_biaya_formulir = false
if (!('metode_pembayaran' in form)) form.metode_pembayaran = ''
if (!('status_pembayaran' in form)) form.status_pembayaran = 'pending'

// simpan metadata xendit
if (!('xendit_external_id' in form)) form.xendit_external_id = null
if (!('xendit_invoice_id' in form)) form.xendit_invoice_id = null
if (!('xendit_invoice_url' in form)) form.xendit_invoice_url = null
if (!('xendit_expiry_date' in form)) form.xendit_expiry_date = null

// nominal “display” — sumber kebenaran tetap backend (env XENDIT_INVOICE_AMOUNT)
const amountDisplay = 100000

const formattedAmount = computed(() =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amountDisplay),
)

const isBank = computed(() => form.metode_pembayaran === 'bank')
const isEwallet = computed(() => form.metode_pembayaran === 'ewallet')

const creating = ref(false)
const checking = ref(false)
const errorMsg = ref('')
const infoMsg = ref('')
const lastCheckedAt = ref(null)

// polling status
let pollTimer = null
const startPolling = () => {
  stopPolling()
  pollTimer = setInterval(() => {
    if (form.status_pembayaran === 'paid') return stopPolling()
    if (!form.xendit_external_id) return stopPolling()
    checkStatus()
  }, 5000)
}
const stopPolling = () => {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = null
}

onBeforeUnmount(stopPolling)

const canCreateInvoice = computed(() => {
  return (
    !!form.setuju_biaya_formulir &&
    !!form.metode_pembayaran &&
    !!String(form.nama_lengkap || '').trim() &&
    !!String(form.nomor_hp || '').trim() &&
    !!String(form.alamat_email || '').trim() // ✅ samakan dengan kebutuhan backend (payer_email)
  )
})

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    infoMsg.value = 'Berhasil disalin.'
    setTimeout(() => (infoMsg.value = ''), 1200)
  } catch (e) {
    errorMsg.value = 'Gagal menyalin.'
  }
}

const resetPaymentState = () => {
  form.status_pembayaran = 'pending'
  form.xendit_external_id = null
  form.xendit_invoice_id = null
  form.xendit_invoice_url = null
  form.xendit_expiry_date = null
  lastCheckedAt.value = null
  stopPolling()
}

// ✅ penting: jika metode berubah saat invoice sudah dibuat → reset (hindari mismatch bank/ewallet)
watch(
  () => [form.metode_pembayaran, form.setuju_biaya_formulir],
  ([method, agree], [prevMethod, prevAgree]) => {
    // jika batal / kosong
    if (!agree || !method) {
      resetPaymentState()
      return
    }

    // jika metode berubah dan sudah ada invoice
    if (prevMethod && method !== prevMethod && form.xendit_external_id) {
      resetPaymentState()
      return
    }

    // jika sebelumnya tidak setuju lalu setuju lagi, tapi ada invoice tersisa
    if (prevAgree === false && agree === true && form.xendit_external_id) {
      resetPaymentState()
    }
  },
)

const createInvoice = async () => {
  errorMsg.value = ''
  infoMsg.value = ''

  if (!canCreateInvoice.value) {
    errorMsg.value = 'Lengkapi persetujuan biaya, metode pembayaran, nama, nomor HP, dan email.'
    return
  }

  creating.value = true
  try {
    const res = await fetch('/api/pmb/payments/xendit/invoice', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        jalur: 'mandiri',
        method: form.metode_pembayaran,             // 'bank' | 'ewallet'
        payer_email: form.alamat_email || null,     // ✅ samakan key dengan backend
        phone: form.nomor_hp,                       // ✅ samakan key dengan backend
        name: form.nama_lengkap || null,            // optional
      }),
    })

    const json = await res.json().catch(() => ({}))
    if (!res.ok) {
      // tampilkan pesan backend kalau ada
      errorMsg.value = json?.message || 'Gagal membuat invoice Xendit.'
      return
    }

    form.xendit_external_id = json?.external_id || null
    form.xendit_invoice_id = json?.invoice_id || null
    form.xendit_invoice_url = json?.invoice_url || null
    form.xendit_expiry_date = json?.expiry_date || null

    // status dari backend
    const st = String(json?.status || 'PENDING').toUpperCase()
    form.status_pembayaran = st === 'PAID' || st === 'SETTLED' ? 'paid' : 'pending'

    infoMsg.value = 'Invoice berhasil dibuat. Silakan lakukan pembayaran.'
    startPolling()
  } catch (e) {
    errorMsg.value = 'Gagal terhubung ke server pembayaran.'
  } finally {
    creating.value = false
  }
}

const checkStatus = async () => {
  if (!form.xendit_external_id) return
  checking.value = true
  errorMsg.value = ''

  try {
    const res = await fetch(
      `/api/pmb/payments/xendit/${encodeURIComponent(form.xendit_external_id)}`,
      { headers: { Accept: 'application/json' } },
    )

    const json = await res.json().catch(() => ({}))
    if (!res.ok) {
      errorMsg.value = json?.message || 'Gagal cek status pembayaran.'
      return
    }

    lastCheckedAt.value = new Date().toISOString()

    const st = String(json?.status || '').toUpperCase()
    if (st === 'PAID' || st === 'SETTLED') {
      form.status_pembayaran = 'paid'
      infoMsg.value = 'Pembayaran terkonfirmasi LUNAS.'
      stopPolling()
    } else if (st === 'EXPIRED') {
      form.status_pembayaran = 'pending'
      errorMsg.value = 'Invoice kedaluwarsa. Silakan buat invoice baru.'
      stopPolling()
    } else {
      form.status_pembayaran = 'pending'
    }
  } catch (e) {
    errorMsg.value = 'Gagal terhubung saat cek status.'
  } finally {
    checking.value = false
  }
}

const openInvoice = () => {
  if (!form.xendit_invoice_url) return
  window.open(form.xendit_invoice_url, '_blank', 'noopener,noreferrer')
}
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
        <CreditCardIcon class="w-4 h-4" />
      </div>
      <div>
        <h2 class="text-sm font-semibold tracking-wide text-slate-800 dark:text-slate-50">
          Pembayaran Formulir Pendaftaran Mandiri (Xendit)
        </h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Sistem akan membuat invoice Xendit. Setelah Anda membayar, status akan otomatis menjadi LUNAS (via webhook).
        </p>
      </div>
    </div>

    <!-- RINGKASAN TAGIHAN -->
    <div
      class="rounded-2xl border border-sky-500/15
             bg-gradient-to-r from-sky-50 via-sky-100 to-sky-200
             dark:from-slate-900 dark:via-slate-950 dark:to-sky-900/70
             px-4 py-3 flex items-center justify-between gap-4
             shadow-[0_16px_40px_rgba(15,23,42,0.12)]
             dark:shadow-[0_18px_45px_rgba(15,23,42,0.65)]"
    >
      <div>
        <p class="text-[11px] text-sky-700 dark:text-sky-200/80">Total yang harus dibayar</p>
        <p class="text-xl font-semibold tracking-tight text-sky-900 dark:text-sky-50">
          {{ formattedAmount }}
        </p>
        <p class="text-[10px] text-slate-600 dark:text-slate-300">
          Biaya formulir Penerimaan Mahasiswa Baru
        </p>
      </div>

      <div class="text-right space-y-1">
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] border bg-sky-50 dark:bg-slate-900/70"
          :class="form.status_pembayaran === 'paid'
            ? 'text-emerald-600 border-emerald-300 dark:text-emerald-300 dark:border-emerald-400/70'
            : 'text-amber-600 border-amber-300 dark:text-amber-300 dark:border-amber-400/70'"
        >
          <span
            class="h-2 w-2 rounded-full"
            :class="form.status_pembayaran === 'paid' ? 'bg-emerald-400' : 'bg-amber-400 animate-pulse'"
          ></span>
          <span>{{ form.status_pembayaran === 'paid' ? 'LUNAS' : 'Menunggu pembayaran' }}</span>
        </span>

        <p v-if="form.xendit_external_id" class="text-[10px] text-slate-600 dark:text-slate-300">
          External ID:
          <span class="font-mono">{{ form.xendit_external_id }}</span>
        </p>
      </div>
    </div>

    <!-- ALERT -->
    <div v-if="errorMsg" class="text-[11px] text-rose-600 dark:text-rose-300">
      {{ errorMsg }}
    </div>
    <div v-if="infoMsg" class="text-[11px] text-sky-700 dark:text-sky-300">
      {{ infoMsg }}
    </div>

    <!-- KONFIRMASI BIAYA -->
    <label class="pay-checkbox" :class="{ 'pay-checkbox--active': form.setuju_biaya_formulir }">
      <input type="checkbox" v-model="form.setuju_biaya_formulir" class="sr-only" />
      <span class="pay-checkbox-indicator" aria-hidden="true"></span>
      <span class="pay-checkbox-text">
        Saya menyetujui biaya formulir pendaftaran Jalur Mandiri sebesar <strong>Rp100.000</strong>.
      </span>
    </label>

    <!-- METODE PEMBAYARAN -->
    <div class="space-y-2">
      <p class="text-xs font-medium text-slate-700 dark:text-slate-200">
        Pilih Metode Pembayaran <span class="text-red-500">*</span>
      </p>

      <div
        class="grid sm:grid-cols-2 gap-3 text-xs"
        :class="!form.setuju_biaya_formulir ? 'opacity-60 pointer-events-none select-none' : ''"
      >
        <label class="pay-option" :class="{ 'pay-option--active pay-option--primary': isBank }">
          <input type="radio" name="metode_pembayaran" value="bank" v-model="form.metode_pembayaran" class="sr-only" />
          <div class="pay-option-header">
            <span class="pay-option-indicator" :class="{ 'pay-option-indicator--on': isBank }" aria-hidden="true"></span>
            <div>
              <p class="pay-option-title">Transfer Bank (VA)</p>
              <p class="pay-option-desc">Bayar lewat ATM / m-banking via Virtual Account (di halaman invoice).</p>
            </div>
          </div>
        </label>

        <label class="pay-option" :class="{ 'pay-option--active pay-option--secondary': isEwallet }">
          <input type="radio" name="metode_pembayaran" value="ewallet" v-model="form.metode_pembayaran" class="sr-only" />
          <div class="pay-option-header">
            <span class="pay-option-indicator" :class="{ 'pay-option-indicator--on': isEwallet }" aria-hidden="true"></span>
            <div>
              <p class="pay-option-title">E-Wallet</p>
              <p class="pay-option-desc flex items-center gap-1">
                <DevicePhoneMobileIcon class="w-3.5 h-3.5" />
                OVO, DANA, ShopeePay, dll (di halaman invoice).
              </p>
            </div>
          </div>
        </label>
      </div>
    </div>

    <!-- ACTION: BUAT INVOICE -->
    <div class="flex flex-wrap gap-2 items-center">
      <button
        type="button"
        @click="createInvoice"
        :disabled="creating || !canCreateInvoice"
        class="inline-flex items-center gap-2 px-5 md:px-6 py-2.5 rounded-full
               text-xs md:text-sm font-semibold
               bg-gradient-to-r from-sky-500 to-blue-500
               text-white shadow-[0_14px_35px_rgba(37,99,235,0.35)]
               hover:from-sky-400 hover:to-blue-500
               disabled:opacity-60 disabled:cursor-not-allowed
               transition-all"
      >
        <BanknotesIcon class="w-4 h-4" />
        <span>{{ creating ? 'Membuat invoice…' : 'Buat Invoice Xendit' }}</span>
      </button>

      <button
        v-if="form.xendit_invoice_url"
        type="button"
        @click="openInvoice"
        class="inline-flex items-center gap-2 px-5 md:px-6 py-2.5 rounded-full
               text-xs md:text-sm font-semibold
               bg-gradient-to-r from-emerald-500 to-teal-500
               text-white shadow-[0_14px_35px_rgba(16,185,129,0.35)]
               hover:from-emerald-400 hover:to-teal-500
               transition-all"
      >
        <CreditCardIcon class="w-4 h-4" />
        Buka Halaman Pembayaran
      </button>

      <button
        v-if="form.xendit_external_id && form.status_pembayaran !== 'paid'"
        type="button"
        @click="checkStatus"
        :disabled="checking"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-full
               text-[11px] font-semibold
               border border-slate-300/80 text-slate-700 bg-white
               hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-100
               dark:border-slate-600 dark:hover:bg-slate-800
               transition-colors"
      >
        {{ checking ? 'Mengecek…' : 'Cek Status' }}
      </button>
    </div>

    <!-- DETAIL INVOICE -->
    <div
      v-if="form.xendit_invoice_url"
      class="rounded-2xl border border-slate-200/80 dark:border-slate-700/80
             bg-white/95 dark:bg-slate-950/80
             px-4 py-3 space-y-3 text-xs"
    >
      <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-200">
        Detail Invoice
      </p>

      <div class="space-y-2">
        <div class="flex items-center justify-between gap-2">
          <p class="text-[11px] text-slate-500 dark:text-slate-400">Invoice URL</p>
          <button
            type="button"
            @click="copyToClipboard(form.xendit_invoice_url)"
            class="inline-flex items-center gap-1 px-2 py-1 rounded-full
                   text-[10px] font-semibold
                   bg-sky-500 text-white hover:bg-sky-400 transition-colors"
          >
            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
            Salin
          </button>
        </div>

        <p class="text-[11px] break-all font-mono text-slate-800 dark:text-slate-100">
          {{ form.xendit_invoice_url }}
        </p>

        <p v-if="form.xendit_expiry_date" class="text-[11px] text-slate-500 dark:text-slate-400">
          Expired at: <span class="font-mono">{{ form.xendit_expiry_date }}</span>
        </p>

        <p v-if="lastCheckedAt" class="text-[10px] text-slate-400 dark:text-slate-500">
          Terakhir dicek: {{ lastCheckedAt }}
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* checkbox toggle (dipertahankan dari versi Anda) */
.pay-checkbox {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.9rem;
  padding: 0.45rem 0.9rem 0.45rem 0.2rem;
  font-size: 0.78rem;
  cursor: pointer;
  user-select: none;
}
.pay-checkbox-text { line-height: 1.6; color: #0f172a; }
.pay-checkbox-indicator {
  position: relative; width: 58px; height: 30px; border-radius: 999px; padding: 3px;
  background:
    radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.35), transparent 60%),
    radial-gradient(circle at 100% 100%, rgba(56, 189, 248, 0.4), transparent 60%),
    linear-gradient(90deg, #e0f2fe 0%, #bfdbfe 45%, #e0f2fe 100%);
  box-shadow:
    0 0 0 1px rgba(59, 130, 246, 0.45),
    0 14px 30px rgba(59, 130, 246, 0.45),
    0 0 45px rgba(56, 189, 248, 0.55);
  display: flex; align-items: center; justify-content: flex-start;
  transition: box-shadow 0.25s ease-out, background 0.25s ease-out;
}
.pay-checkbox-indicator::before {
  content: '';
  position: absolute; top: 50%; left: 5px;
  width: 22px; height: 22px; border-radius: 999px;
  background: radial-gradient(circle at 30% 20%, #020617, #020617);
  box-shadow: inset 0 0 0 2px rgba(248, 250, 252, 0.9), 0 0 14px rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(248, 250, 252, 0.9);
  transform: translate3d(0, -50%, 0);
  transition: transform 0.24s cubic-bezier(0.22, 0.61, 0.36, 1);
}
.pay-checkbox--active .pay-checkbox-indicator::before { transform: translate3d(24px, -50%, 0); }
.dark .pay-checkbox-text { color: #e5f2ff; }
.dark .pay-checkbox-indicator {
  background:
    radial-gradient(circle at 0% 0%, rgba(15, 23, 42, 0.6), transparent 60%),
    radial-gradient(circle at 100% 100%, rgba(37, 99, 235, 0.75), transparent 60%),
    linear-gradient(90deg, #020617 0%, #020617 45%, #020617 100%);
  box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.8), 0 0 40px rgba(37, 99, 235, 0.9);
}

/* radio option */
.pay-option {
  position: relative;
  border-radius: 1.5rem;
  padding: 1rem 1.1rem;
  border: 1px solid rgba(148, 163, 184, 0.65);
  background: radial-gradient(circle at 0% 0%, rgba(148, 163, 184, 0.12), transparent 60%), #ffffff;
  color: #0f172a;
  cursor: pointer;
  overflow: hidden;
  transition: border-color 0.22s ease-out, box-shadow 0.22s ease-out, background 0.22s ease-out, transform 0.18s ease-out;
}
.pay-option:hover {
  border-color: rgba(59, 130, 246, 0.8);
  box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.22), 0 16px 30px rgba(15, 23, 42, 0.16);
  transform: translateY(-1px);
}
.pay-option-header { display: flex; align-items: flex-start; gap: 0.6rem; }
.pay-option-indicator {
  width: 20px; height: 20px; border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.95);
  background: radial-gradient(circle at 30% 20%, #e5f2ff, #cbd5f5);
  position: relative; flex-shrink: 0;
}
.pay-option-indicator::before {
  content: '';
  position: absolute; inset: 4px; border-radius: inherit;
  background: radial-gradient(circle at 30% 20%, #38bdf8, #4f46e5);
  opacity: 0; transform: scale(0.35);
  transition: opacity 0.18s ease-out, transform 0.18s ease-out;
}
.pay-option--active { transform: translateY(-1.5px); border-color: rgba(56, 189, 248, 0.95); }
.pay-option-indicator--on::before { opacity: 1; transform: scale(1); }
.pay-option-title { font-weight: 600; font-size: 0.82rem; color: #0f172a; }
.pay-option-desc { margin-top: 0.2rem; font-size: 0.7rem; color: #6b7280; }
.dark .pay-option { color: #e5e7eb; border-color: rgba(51, 65, 85, 0.95); background: #020617; }
.dark .pay-option-title { color: #f9fafb; }
.dark .pay-option-desc { color: rgba(226, 232, 240, 0.92); }
</style>
