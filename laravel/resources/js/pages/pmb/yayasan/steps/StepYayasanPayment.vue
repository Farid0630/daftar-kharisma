<script setup>
import { inject, computed, ref, watch } from 'vue'
import {
  CreditCardIcon,
  BanknotesIcon,
  DevicePhoneMobileIcon,
  DocumentDuplicateIcon,
  ArrowTopRightOnSquareIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

// ===== safety defaults =====
if (!('setuju_biaya_formulir' in form)) form.setuju_biaya_formulir = false
if (!('metode_pembayaran' in form)) form.metode_pembayaran = ''
if (!('status_pembayaran' in form)) form.status_pembayaran = 'pending'

// simpan info invoice agar ikut tersubmit ke register/yayasan
if (!('xendit_external_id' in form)) form.xendit_external_id = ''
if (!('xendit_invoice_id' in form)) form.xendit_invoice_id = ''
if (!('xendit_invoice_url' in form)) form.xendit_invoice_url = ''
if (!('xendit_expiry_date' in form)) form.xendit_expiry_date = ''
if (!('xendit_invoice_status' in form)) form.xendit_invoice_status = ''

// nominal: backend tetap sumber kebenaran, frontend hanya display
const amount = 100000
const formattedAmount = computed(() =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount),
)

const isBank = computed(() => form.metode_pembayaran === 'bank')
const isEwallet = computed(() => form.metode_pembayaran === 'ewallet')

// ===== UI state =====
const creating = ref(false)
const checking = ref(false)
const msg = ref('')
const msgType = ref('') // success | error | ''

const setMsg = (type, text) => {
  msgType.value = type
  msg.value = text
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(String(text || ''))
  } catch (e) {
    console.error('Gagal menyalin:', e)
  }
}

const openInvoice = () => {
  if (form.xendit_invoice_url) window.open(form.xendit_invoice_url, '_blank', 'noopener,noreferrer')
}

// reset invoice kalau user ganti metode (supaya konsisten)
watch(
  () => form.metode_pembayaran,
  (nv, ov) => {
    if (!ov) return
    if (nv !== ov) {
      form.status_pembayaran = 'pending'
      form.xendit_external_id = ''
      form.xendit_invoice_id = ''
      form.xendit_invoice_url = ''
      form.xendit_expiry_date = ''
      form.xendit_invoice_status = ''
      setMsg('', '')
    }
  },
)

// ===== create invoice: sesuai validator backend =====
const createInvoice = async () => {
  setMsg('', '')

  if (!form.setuju_biaya_formulir) return setMsg('error', 'Centang persetujuan biaya formulir terlebih dahulu.')
  if (!form.metode_pembayaran) return setMsg('error', 'Pilih metode pembayaran terlebih dahulu.')

  const name = (form.nama_lengkap || '').trim()
  const phone = (form.nomor_hp || '').trim()
  const payerEmail = (form.alamat_email || '').trim() || null

  if (!name) return setMsg('error', 'Nama lengkap belum diisi (cek langkah Data Diri).')
  if (!phone) return setMsg('error', 'Nomor WhatsApp belum diisi (cek langkah Akun & WhatsApp).')

  creating.value = true
  try {
    const payload = {
      // ini yang diminta controller Anda
      jalur: 'yayasan',
      method: form.metode_pembayaran, // bank | ewallet
      name,
      phone,
      payer_email: payerEmail,
      email: payerEmail,
    }

    const res = await fetch('/api/pmb/payments/xendit/invoice', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      if (data?.errors) {
        const all = Object.values(data.errors).flat().join(' | ')
        return setMsg('error', all || data?.message || 'Validasi gagal saat membuat invoice.')
      }
      return setMsg('error', data?.message || 'Gagal membuat invoice.')
    }

    // response controller Anda top-level:
    // external_id, invoice_id, invoice_url, status, expiry_date, amount, currency
    form.xendit_external_id = data.external_id || ''
    form.xendit_invoice_id = data.invoice_id || ''
    form.xendit_invoice_url = data.invoice_url || ''
    form.xendit_invoice_status = String(data.status || 'PENDING')
    form.xendit_expiry_date = data.expiry_date || ''

    form.status_pembayaran = 'pending'

    setMsg('success', 'Invoice berhasil dibuat. Silakan lanjutkan pembayaran di halaman Xendit.')
    if (form.xendit_invoice_url) openInvoice()
  } catch (e) {
    console.error(e)
    setMsg('error', 'Gagal terhubung ke server saat membuat invoice.')
  } finally {
    creating.value = false
  }
}

const isPaidStatus = (status) => {
  const s = String(status || '').toUpperCase()
  return s === 'PAID' || s === 'SETTLED'
}

// ===== check status: sesuai show() controller =====
const checkStatus = async () => {
  setMsg('', '')

  if (!form.xendit_external_id) {
    return setMsg('error', 'Invoice belum dibuat. Klik “Buat Invoice Pembayaran” terlebih dahulu.')
  }

  checking.value = true
  try {
    const res = await fetch(`/api/pmb/payments/xendit/${encodeURIComponent(form.xendit_external_id)}`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    })

    const data = await res.json().catch(() => ({}))
    if (!res.ok) return setMsg('error', data?.message || 'Gagal cek status pembayaran.')

    form.xendit_external_id = data.external_id || form.xendit_external_id
    form.xendit_invoice_id = data.invoice_id || form.xendit_invoice_id
    form.xendit_invoice_url = data.invoice_url || form.xendit_invoice_url
    form.xendit_invoice_status = String(data.status || form.xendit_invoice_status || 'PENDING')
    form.xendit_expiry_date = data.expiry_date || form.xendit_expiry_date

    if (isPaidStatus(form.xendit_invoice_status)) {
      form.status_pembayaran = 'paid'
      setMsg('success', 'Pembayaran sudah LUNAS. Silakan lanjut ke langkah berikutnya.')
    } else {
      form.status_pembayaran = 'pending'
      setMsg('error', `Status pembayaran masih: ${form.xendit_invoice_status}. Jika sudah bayar, tunggu sebentar lalu cek lagi.`)
    }
  } catch (e) {
    console.error(e)
    setMsg('error', 'Gagal terhubung ke server saat cek status.')
  } finally {
    checking.value = false
  }
}

const trxLabel = computed(() => (form.xendit_external_id ? form.xendit_external_id : 'Belum dibuat'))
const badgeText = computed(() => (form.status_pembayaran === 'paid' ? 'LUNAS' : 'Menunggu pembayaran'))

const xenditRef = computed(() => form.xendit_invoice_url || form.xendit_external_id || '')
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
          Pembayaran Formulir Beasiswa Yayasan
        </h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Satu langkah lagi. Selesaikan pembayaran biaya formulir untuk mengaktifkan pendaftaranmu.
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
          <span class="h-2 w-2 rounded-full" :class="form.status_pembayaran === 'paid' ? 'bg-emerald-400' : 'bg-amber-400 animate-pulse'"></span>
          <span>{{ badgeText }}</span>
        </span>

        <p class="text-[10px] text-slate-600 dark:text-slate-300">
          ID Transaksi:
          <span class="font-mono">{{ trxLabel }}</span>
        </p>
      </div>
    </div>

    <div v-if="msg" class="text-[11px]" :class="msgType === 'success' ? 'text-emerald-400' : 'text-rose-400'">
      {{ msg }}
    </div>

    <!-- KONFIRMASI BIAYA – TOGGLE NEON PREMIUM -->
    <label class="pay-checkbox" :class="{ 'pay-checkbox--active': form.setuju_biaya_formulir }">
      <input type="checkbox" v-model="form.setuju_biaya_formulir" class="sr-only" />
      <span class="pay-checkbox-indicator" aria-hidden="true"></span>
      <span class="pay-checkbox-text">
        Saya menyetujui biaya formulir pendaftaran Beasiswa Yayasan sebesar <strong>Rp100.000</strong>.
      </span>
    </label>

    <!-- METODE PEMBAYARAN – custom radio -->
    <div class="space-y-2">
      <p class="text-xs font-medium text-slate-700 dark:text-slate-200">
        Pilih Metode Pembayaran <span class="text-red-500">*</span>
      </p>

      <div class="grid sm:grid-cols-2 gap-3 text-xs">
        <!-- Transfer Bank -->
        <label class="pay-option" :class="{ 'pay-option--active pay-option--primary': form.metode_pembayaran === 'bank' }">
          <input type="radio" name="metode_pembayaran" value="bank" v-model="form.metode_pembayaran" class="sr-only" />
          <div class="pay-option-header">
            <span class="pay-option-indicator" :class="{ 'pay-option-indicator--on': form.metode_pembayaran === 'bank' }" aria-hidden="true"></span>
            <div>
              <p class="pay-option-title">Transfer Bank (VA)</p>
              <p class="pay-option-desc">Bayar lewat ATM / m-banking via Virtual Account.</p>
            </div>
          </div>
        </label>

        <!-- E-Wallet -->
        <label class="pay-option" :class="{ 'pay-option--active pay-option--secondary': form.metode_pembayaran === 'ewallet' }">
          <input type="radio" name="metode_pembayaran" value="ewallet" v-model="form.metode_pembayaran" class="sr-only" />
          <div class="pay-option-header">
            <span class="pay-option-indicator" :class="{ 'pay-option-indicator--on': form.metode_pembayaran === 'ewallet' }" aria-hidden="true"></span>
            <div>
              <p class="pay-option-title">E-Wallet</p>
              <p class="pay-option-desc flex items-center gap-1">
                <DevicePhoneMobileIcon class="w-3.5 h-3.5" />
                OVO, Dana, GoPay, ShopeePay, dan lainnya.
              </p>
            </div>
          </div>
        </label>
      </div>
    </div>

    <!-- DETAIL METODE -->
    <div
      v-if="form.metode_pembayaran"
      class="rounded-2xl border border-slate-200/80 dark:border-slate-700/80
             bg-white/95 dark:bg-slate-950/80
             px-4 py-3 space-y-3 text-xs"
    >
      <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-200">
        Instruksi singkat pembayaran
      </p>

      <div class="rounded-xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-700/80 px-3 py-2.5">
        <p class="text-[11px] text-slate-500 dark:text-slate-400">Referensi Pembayaran (Xendit)</p>

        <div class="mt-1 flex items-center justify-between gap-2">
          <p class="font-mono text-[11px] md:text-sm text-slate-900 dark:text-slate-50 truncate">
            {{ xenditRef || '—' }}
          </p>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="copyToClipboard(xenditRef)"
              class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-sky-500 text-white hover:bg-sky-400 transition-colors"
              :disabled="!xenditRef"
            >
              <DocumentDuplicateIcon class="w-3.5 h-3.5" />
              Salin
            </button>

            <button
              v-if="form.xendit_invoice_url"
              type="button"
              @click="openInvoice"
              class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-emerald-500 text-white hover:bg-emerald-400 transition-colors"
            >
              <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
              Buka
            </button>
          </div>
        </div>
      </div>

      <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-500 dark:text-slate-400">
        <li>Klik tombol <strong>Buat Invoice</strong> untuk mendapatkan link pembayaran.</li>
        <li>Bayar di halaman Xendit (pilih metode yang sesuai).</li>
        <li>Setelah bayar, klik <strong>Cek Status</strong>.</li>
      </ul>

      <div class="pt-2 border-t border-dashed border-slate-200/70 dark:border-slate-700/70 flex flex-wrap gap-2">
        <button
          type="button"
          @click="createInvoice"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[11px] font-semibold
                 bg-gradient-to-r from-sky-500 to-blue-500 text-white
                 shadow-[0_8px_22px_rgba(56,189,248,0.55)]
                 hover:from-sky-400 hover:to-blue-500
                 disabled:opacity-60 disabled:cursor-not-allowed transition-all"
          :disabled="creating"
        >
          <ArrowPathIcon v-if="creating" class="w-4 h-4 animate-spin" />
          <BanknotesIcon v-else class="w-4 h-4" />
          Buat Invoice Pembayaran
        </button>

        <button
          type="button"
          @click="checkStatus"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[11px] font-semibold
                 bg-gradient-to-r from-emerald-500 to-sky-500 text-white
                 shadow-[0_8px_22px_rgba(16,185,129,0.55)]
                 hover:from-emerald-400 hover:to-sky-500
                 disabled:opacity-60 disabled:cursor-not-allowed transition-all"
          :disabled="checking"
        >
          <ArrowPathIcon v-if="checking" class="w-4 h-4 animate-spin" />
          <span v-else class="inline-flex items-center gap-2">
            <BanknotesIcon class="w-4 h-4" />
            Cek Status Pembayaran
          </span>
        </button>

        <p class="w-full mt-1 text-[10px] text-slate-500 dark:text-slate-400">
          Status idealnya ter-update otomatis dari webhook. Jika perlu, gunakan tombol cek status.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ===== CSS Anda: tidak saya ubah ===== */
.pay-checkbox { position: relative; display: inline-flex; align-items: center; gap: 0.9rem; padding: 0.45rem 0.9rem 0.45rem 0.2rem; font-size: 0.78rem; cursor: pointer; user-select: none; }
.pay-checkbox-text { line-height: 1.6; color: #0f172a; }
.pay-checkbox-indicator { position: relative; width: 58px; height: 30px; border-radius: 999px; padding: 3px; background: radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.35), transparent 60%), radial-gradient(circle at 100% 100%, rgba(56, 189, 248, 0.4), transparent 60%), linear-gradient(90deg, #e0f2fe 0%, #bfdbfe 45%, #e0f2fe 100%); box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.45), 0 14px 30px rgba(59, 130, 246, 0.45), 0 0 45px rgba(56, 189, 248, 0.55); display: flex; align-items: center; justify-content: flex-start; transition: box-shadow 0.25s ease-out, background 0.25s ease-out; }
.pay-checkbox-indicator::before { content: ''; position: absolute; top: 50%; left: 5px; width: 22px; height: 22px; border-radius: 999px; background: radial-gradient(circle at 30% 20%, #020617, #020617); box-shadow: inset 0 0 0 2px rgba(248, 250, 252, 0.9), 0 0 14px rgba(15, 23, 42, 0.85); border: 1px solid rgba(248, 250, 252, 0.9); transform: translate3d(0, -50%, 0); transition: transform 0.24s cubic-bezier(0.22, 0.61, 0.36, 1); }
.pay-checkbox-indicator::after { content: ''; position: absolute; top: 50%; left: 5px; width: 9px; height: 5px; border-left: 2px solid #f9fafb; border-bottom: 2px solid #f9fafb; border-radius: 2px; transform-origin: center; transform: translate3d(7px, -50%, 0) rotate(-45deg); opacity: 0.9; transition: transform 0.24s cubic-bezier(0.22, 0.61, 0.36, 1); }
.pay-checkbox--active .pay-checkbox-indicator::before { transform: translate3d(24px, -50%, 0); }
.pay-checkbox--active .pay-checkbox-indicator::after { transform: translate3d(31px, -50%, 0) rotate(-45deg); }
.pay-checkbox:hover .pay-checkbox-indicator { box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.6), 0 18px 40px rgba(59, 130, 246, 0.6), 0 0 55px rgba(56, 189, 248, 0.7); }
.dark .pay-checkbox-text { color: #e5f2ff; }
.dark .pay-checkbox-indicator { background: radial-gradient(circle at 0% 0%, rgba(15, 23, 42, 0.6), transparent 60%), radial-gradient(circle at 100% 100%, rgba(37, 99, 235, 0.75), transparent 60%), linear-gradient(90deg, #020617 0%, #020617 45%, #020617 100%); box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.8), 0 0 40px rgba(37, 99, 235, 0.9); }

.pay-option { position: relative; border-radius: 1.5rem; padding: 1rem 1.1rem; border: 1px solid rgba(148, 163, 184, 0.65); background: radial-gradient(circle at 0% 0%, rgba(148, 163, 184, 0.12), transparent 60%), #ffffff; color: #0f172a; cursor: pointer; overflow: hidden; transition: border-color 0.22s ease-out, box-shadow 0.22s ease-out, background 0.22s ease-out, transform 0.18s ease-out; }
.pay-option::before { content: ''; position: absolute; inset: -45%; background: radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.16), transparent 55%), radial-gradient(circle at 100% 100%, rgba(129, 140, 248, 0.16), transparent 55%); opacity: 0; transition: opacity 0.2s ease-out; pointer-events: none; }
.pay-option:hover { border-color: rgba(59, 130, 246, 0.8); box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.22), 0 16px 30px rgba(15, 23, 42, 0.16); transform: translateY(-1px); }
.pay-option:hover::before { opacity: 1; }
.pay-option-header { display: flex; align-items: flex-start; gap: 0.6rem; }
.pay-option-indicator { width: 20px; height: 20px; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.95); background: radial-gradient(circle at 30% 20%, #e5f2ff, #cbd5f5); box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.6), inset 0 0 0 1px rgba(248, 250, 252, 0.9); position: relative; flex-shrink: 0; }
.pay-option-indicator::before { content: ''; position: absolute; inset: 4px; border-radius: inherit; background: radial-gradient(circle at 30% 20%, #38bdf8, #4f46e5); opacity: 0; transform: scale(0.35); transition: opacity 0.18s ease-out, transform 0.18s ease-out; }
.pay-option-indicator::after { content: ''; position: absolute; inset: 1px; border-radius: inherit; border: 1px solid rgba(56, 189, 248, 0.35); opacity: 0; transform: scale(0.8); transition: opacity 0.22s ease-out, transform 0.22s ease-out; }
.pay-option-indicator--on::before, .pay-option--active .pay-option-indicator::before { opacity: 1; transform: scale(1); }
.pay-option-indicator--on::after, .pay-option--active .pay-option-indicator::after { opacity: 1; transform: scale(1.08); }
.pay-option-title { font-weight: 600; font-size: 0.82rem; color: #0f172a; }
.pay-option-desc { margin-top: 0.2rem; font-size: 0.7rem; color: #6b7280; }
.pay-option--active { transform: translateY(-1.5px); border-color: rgba(56, 189, 248, 0.95); box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.7), 0 0 32px rgba(56, 189, 248, 0.4); }
.pay-option--primary { background: radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.16), transparent 60%), radial-gradient(circle at 100% 100%, rgba(37, 99, 235, 0.16), transparent 60%), #eff6ff; }
.pay-option--secondary { background: radial-gradient(circle at 0% 0%, rgba(52, 211, 153, 0.18), transparent 60%), radial-gradient(circle at 100% 100%, rgba(56, 189, 248, 0.18), transparent 60%), #ecfdf5; }
.pay-option--active .pay-option-title { color: #020617; }
.pay-option--active .pay-option-desc { color: #0f172a; }
.dark .pay-option { color: #e5e7eb; border-color: rgba(51, 65, 85, 0.95); background: radial-gradient(circle at 0% 0%, rgba(15, 23, 42, 0.9), transparent 60%), #020617; }
.dark .pay-option--primary { background: radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.24), transparent 60%), radial-gradient(circle at 100% 100%, rgba(37, 99, 235, 0.45), transparent 60%), #020617; }
.dark .pay-option--secondary { background: radial-gradient(circle at 0% 0%, rgba(52, 211, 153, 0.18), transparent 60%), radial-gradient(circle at 100% 100%, rgba(56, 189, 248, 0.32), transparent 60%), #020617; }
.dark .pay-option--active .pay-option-title { color: #f9fafb; }
.dark .pay-option--active .pay-option-desc { color: rgba(226, 232, 240, 0.92); }
</style>
