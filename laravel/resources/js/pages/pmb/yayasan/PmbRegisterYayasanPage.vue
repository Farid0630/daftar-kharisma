<!-- resources/js/Pages/pmb/yayasan/PmbRegisterYayasanPage.vue -->
<script setup>
import { ref, reactive, computed, provide, watch } from 'vue'

// layout global
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'

// ikon notifikasi
import {
  SparklesIcon,
  ExclamationTriangleIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'

// STEP khusus Yayasan
import StepYayasanScholarship from './steps/StepYayasanScholarship.vue'
import StepYayasanPersonalData from './steps/StepYayasanPersonalData.vue'
import StepYayasanSchoolData from './steps/StepYayasanSchoolData.vue'
import StepYayasanAccount from './steps/StepYayasanAccount.vue'
import StepYayasanOtpWa from './steps/StepYayasanOtpWa.vue'
import StepYayasanPayment from './steps/StepYayasanPayment.vue'
import StepYayasanUploadDocs from './steps/StepYayasanUploadDocs.vue'
import StepYayasanPrintExamCard from './steps/StepYayasanPrintExamCard.vue'

/* ========= FORM STATE YAYASAN (lengkap + file objects) ========= */
const form = reactive({
  // STEP 1 – Jalur & Beasiswa
  jalur_pendaftaran: 'Beasiswa Yayasan',

  // prodi: kompatibel dengan komponen/logic lain yg pakai `program_studi`
  program_studi: '',
  program_studi_1: '',
  program_studi_2: '',

  jenis_beasiswa: '', // 'akademik' | 'non_akademik'
  kategori_prestasi: [],
  deskripsi_prestasi: '',

  // bukti prestasi (simpan file object)
  bukti_prestasi_nama: '',
  bukti_prestasi_file: null, // File

  // STEP 2 – Data diri
  nama_lengkap: '',
  jenis_kelamin: 'L',
  tempat_lahir: '',
  tanggal_lahir: '',
  kewarganegaraan: '',

  // foto (simpan file object + nama + preview)
  foto_nama: '',
  foto_preview: '',
  foto_file: null, // File

  // STEP 3 – Data sekolah
  provinsi_sekolah: '',
  jenis_sekolah: '', // SMA / SMK / MA
  nama_sekolah: '',
  jurusan_sekolah: '',
  kabkota_sekolah: '',
  tahun_lulus: '',

  // STEP 4 – Akun & WhatsApp
  username: '',
  kata_sandi: '',
  konfirmasi_kata_sandi: '',
  nomor_hp: '',
  alamat_email: '',

  // STEP 5 – OTP WA
  kode_otp: '',
  otp_terverifikasi: false,
  otp_session_id: '',
  otp_expires_at: '',

  // STEP 6 – Pembayaran
  setuju_biaya_formulir: false,
  metode_pembayaran: '',
  status_pembayaran: 'pending', // 'pending' | 'paid'

  // OPTIONAL: jika StepPayment Anda mengisi data Xendit, ini ikut terkirim
  xendit_external_id: '',
  xendit_invoice_id: '',
  xendit_invoice_url: '',
  xendit_amount: '',
  xendit_paid_at: '',

  // STEP 7 – Berkas (simpan file object)
  file_ktp_nama: '',
  file_ktp_file: null, // File

  file_kk_nama: '',
  file_kk_file: null, // File

  file_rapor_nama: '',
  file_rapor_files: [], // File[]

  berkas_terunggah: false,
})

// share state ke semua step
provide('pmbForm', form)

/* ========= SYNC prodi (penting untuk kompatibilitas) ========= */
watch(
  () => form.program_studi_1,
  (val) => {
    if (val) form.program_studi = val
    // cegah program_studi_2 sama dengan 1
    if (form.program_studi_2 && form.program_studi_2 === val) {
      form.program_studi_2 = ''
    }
  },
  { immediate: true },
)

/* ========= sync nama bukti prestasi dari file (safety) ========= */
watch(
  () => form.bukti_prestasi_file,
  (file) => {
    if (file instanceof File) {
      form.bukti_prestasi_nama = file.name
    } else if (!file) {
      // jika file dihapus, kosongkan nama
      form.bukti_prestasi_nama = ''
    }
  },
)

/* ========= RESET OTP jika nomor WA berubah ========= */
watch(
  () => form.nomor_hp,
  (newVal, oldVal) => {
    // reset jika benar-benar berubah (termasuk dari kosong -> terisi)
    if (newVal !== oldVal) {
      form.kode_otp = ''
      form.otp_terverifikasi = false
      form.otp_session_id = ''
      form.otp_expires_at = ''
    }
  },
)

/* ========= DATA BANTUAN ========= */
const provinces = [
  'ACEH',
  'SUMATERA UTARA',
  'SUMATERA BARAT',
  'RIAU',
  'KEPULAUAN RIAU',
  'JAMBI',
  'BENGKULU',
  'SUMATERA SELATAN',
  'KEPULAUAN BANGKA BELITUNG',
  'LAMPUNG',
  'DKI JAKARTA',
  'JAWA BARAT',
  'BANTEN',
  'JAWA TENGAH',
  'DAERAH ISTIMEWA YOGYAKARTA',
  'JAWA TIMUR',
  'BALI',
  'NUSA TENGGARA BARAT',
  'NUSA TENGGARA TIMUR',
  'KALIMANTAN BARAT',
  'KALIMANTAN TENGAH',
  'KALIMANTAN SELATAN',
  'KALIMANTAN TIMUR',
  'KALIMANTAN UTARA',
  'SULAWESI UTARA',
  'SULAWESI TENGAH',
  'SULAWESI SELATAN',
  'SULAWESI TENGGARA',
  'SULAWESI BARAT',
  'GORONTALO',
  'MALUKU',
  'MALUKU UTARA',
  'PAPUA',
  'PAPUA BARAT',
  'PAPUA TENGAH',
  'PAPUA PEGUNUNGAN',
  'PAPUA SELATAN',
  'PAPUA BARAT DAYA',
]

const schoolTypes = ['SMA', 'SMK', 'MA']

const majors = [
  'IPA',
  'IPS',
  'Bahasa',
  'TKJ (Teknik Komputer & Jaringan)',
  'RPL (Rekayasa Perangkat Lunak)',
  'Multimedia',
  'Akuntansi',
  'Perkantoran / OTKP',
  'Teknik Mesin',
  'Teknik Kendaraan Ringan',
  'Teknik Listrik / Elektro',
  'Pariwisata / Perhotelan',
  'Lainnya',
]

// Tahun lulus: dari (tahun sekarang + 1) sampai 1995
const START_YEAR = new Date().getFullYear() + 1
const END_YEAR = 1995
const years = Array.from({ length: START_YEAR - END_YEAR + 1 }, (_, i) => START_YEAR - i)

/* ========= STEP WIZARD (8 LANGKAH) ========= */
const steps = [
  { id: 1, title: 'Jalur & Prodi', short: 'Jalur & Prodi' },
  { id: 2, title: 'Data Diri', short: 'Data Diri' },
  { id: 3, title: 'Data Sekolah', short: 'Sekolah' },
  { id: 4, title: 'Akun PMB', short: 'Akun' },
  { id: 5, title: 'Verifikasi OTP WA', short: 'Verifikasi' },
  { id: 6, title: 'Pembayaran Formulir', short: 'Pembayaran' },
  { id: 7, title: 'Upload Berkas', short: 'Berkas' },
  { id: 8, title: 'Kartu Ujian', short: 'Kartu Ujian' },
]

const currentStep = ref(1)

const stepProgress = computed(() =>
  Math.round((currentStep.value / steps.length) * 100),
)

/* ========= VALIDASI PER STEP ========= */
const submitMessage = ref('')
const submitType = ref('') // 'success' | 'error'

const setError = (msg) => {
  submitType.value = 'error'
  submitMessage.value = msg
  return false
}

const validateCurrentStep = () => {
  submitMessage.value = ''
  submitType.value = ''

  // STEP 1 – Jalur & Beasiswa
  if (currentStep.value === 1) {
    if (!form.program_studi_1 || !form.jenis_beasiswa) {
      return setError('Pilih Program Studi 1 dan jenis Beasiswa Yayasan terlebih dahulu.')
    }
    // cek file object
    if (!(form.bukti_prestasi_file instanceof File)) {
      return setError('Mohon upload minimal satu bukti prestasi (file).')
    }
    return true
  }

  // STEP 2 – Data Diri
  if (currentStep.value === 2) {
    const nama = (form.nama_lengkap || '').trim()
    const tempat = (form.tempat_lahir || '').trim()
    const tgl = form.tanggal_lahir
    const kewarganegaraan = (form.kewarganegaraan || '').trim()

    if (!nama || !form.jenis_kelamin || !tempat || !tgl || !kewarganegaraan) {
      return setError('Lengkapi Nama Lengkap, Jenis Kelamin, Tempat/Tanggal Lahir, dan Kewarganegaraan.')
    }
    return true
  }

  // STEP 3 – Data Sekolah
  if (currentStep.value === 3) {
    const prov = form.provinsi_sekolah
    const jenis = form.jenis_sekolah
    const namaSek = (form.nama_sekolah || '').trim()
    const jur = (form.jurusan_sekolah || '').trim()
    const kab = (form.kabkota_sekolah || '').trim()
    const tahun = form.tahun_lulus

    if (!prov || !jenis || !namaSek || !jur || !kab || !tahun) {
      return setError('Lengkapi provinsi, jenis sekolah, nama sekolah, jurusan, kabupaten/kota, dan tahun lulus.')
    }
    return true
  }

  // STEP 4 – Akun & WhatsApp
  if (currentStep.value === 4) {
    const username = (form.username || '').trim()
    const wa = (form.nomor_hp || '').trim()

    if (!username || !wa || !form.kata_sandi || !form.konfirmasi_kata_sandi) {
      return setError('Lengkapi Username, Nomor WhatsApp, Kata Sandi, dan Konfirmasi Kata Sandi.')
    }
    if (form.kata_sandi !== form.konfirmasi_kata_sandi) {
      return setError('Kata sandi dan konfirmasi kata sandi tidak sama.')
    }
    return true
  }

  // STEP 5 – OTP WA
  if (currentStep.value === 5) {
    if (!form.otp_terverifikasi) {
      return setError('Mohon kirim dan verifikasi kode OTP WhatsApp terlebih dahulu.')
    }
    return true
  }

  // STEP 6 – Pembayaran
  if (currentStep.value === 6) {
    if (!form.setuju_biaya_formulir) {
      return setError('Konfirmasi bahwa Anda menyetujui biaya formulir 100K.')
    }
    if (!form.metode_pembayaran) {
      return setError('Pilih metode pembayaran (Transfer Bank / E-Wallet).')
    }
    if (form.status_pembayaran !== 'paid') {
      return setError('Status pembayaran belum LUNAS.')
    }
    return true
  }

  // STEP 7 – Upload Berkas
  if (currentStep.value === 7) {
    const ok =
      form.file_ktp_file instanceof File &&
      form.file_kk_file instanceof File &&
      Array.isArray(form.file_rapor_files) &&
      form.file_rapor_files.length > 0

    if (!ok) {
      return setError('Mohon upload KTP, Kartu Keluarga, dan scan rapor kelas X & XI (file).')
    }

    form.berkas_terunggah = true
    return true
  }

  // STEP 8 – Kartu Ujian
  return true
}

/* ========= VALIDASI SEMUA STEP sebelum submit ========= */
const validateAllBeforeSubmit = () => {
  const originalStep = currentStep.value

  for (let s = 1; s <= 7; s++) {
    currentStep.value = s
    const ok = validateCurrentStep()
    if (!ok) {
      window.scrollTo({ top: 0, behavior: 'smooth' })
      return false
    }
  }

  currentStep.value = originalStep
  return true
}

/* ========= NAVIGASI STEP ========= */
const goNext = () => {
  if (currentStep.value >= steps.length) return
  if (!validateCurrentStep()) return
  currentStep.value++
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const goPrev = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    submitMessage.value = ''
    submitType.value = ''
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

/* ========= SUBMIT KE BACKEND (FormData multipart) ========= */
const handleSubmit = async () => {
  // pastikan semua langkah valid
  if (!validateAllBeforeSubmit()) return

  submitMessage.value = ''
  submitType.value = ''

  try {
    const fd = new FormData()

    // helper append aman
    const appendText = (key, val) => fd.append(key, val == null ? '' : String(val))
    const appendBool = (key, val) => fd.append(key, val ? '1' : '0')

    // jalur
    appendText('jalur_pendaftaran', 'Beasiswa Yayasan')

    // prodi
    appendText('program_studi_1', form.program_studi_1)
    appendText('program_studi_2', form.program_studi_2 || '')
    appendText('program_studi', form.program_studi || form.program_studi_1)

    // beasiswa
    appendText('jenis_beasiswa', form.jenis_beasiswa)
    ;(form.kategori_prestasi || []).forEach((k) => fd.append('kategori_prestasi[]', String(k)))
    // fallback JSON (jika backend Anda lebih suka json)
    appendText('kategori_prestasi_json', JSON.stringify(form.kategori_prestasi || []))
    appendText('deskripsi_prestasi', form.deskripsi_prestasi || '')

    // data diri
    appendText('nama_lengkap', form.nama_lengkap)
    appendText('jenis_kelamin', form.jenis_kelamin)
    appendText('tempat_lahir', form.tempat_lahir)
    appendText('tanggal_lahir', form.tanggal_lahir)
    appendText('kewarganegaraan', form.kewarganegaraan)

    // sekolah
    appendText('provinsi_sekolah', form.provinsi_sekolah)
    appendText('jenis_sekolah', form.jenis_sekolah)
    appendText('nama_sekolah', form.nama_sekolah)
    appendText('jurusan_sekolah', form.jurusan_sekolah)
    appendText('kabkota_sekolah', form.kabkota_sekolah)
    appendText('tahun_lulus', form.tahun_lulus)

    // akun (field asli)
    appendText('username', form.username)
    appendText('alamat_email', form.alamat_email || '')
    appendText('nomor_hp', form.nomor_hp)
    appendText('kata_sandi', form.kata_sandi)
    appendText('konfirmasi_kata_sandi', form.konfirmasi_kata_sandi)

    // akun (alias umum Laravel, tidak merusak jika tidak dipakai)
    appendText('email', form.alamat_email || '')
    appendText('phone', form.nomor_hp || '')
    appendText('password', form.kata_sandi || '')
    appendText('password_confirmation', form.konfirmasi_kata_sandi || '')

    // otp
    appendText('kode_otp', form.kode_otp || '')
    appendBool('otp_terverifikasi', !!form.otp_terverifikasi)
    appendText('otp_session_id', form.otp_session_id || '')
    appendText('otp_expires_at', form.otp_expires_at || '')

    // pembayaran
    appendBool('setuju_biaya_formulir', !!form.setuju_biaya_formulir)
    appendText('metode_pembayaran', form.metode_pembayaran || '')
    appendText('status_pembayaran', form.status_pembayaran)

    // optional xendit fields
    appendText('xendit_external_id', form.xendit_external_id || '')
    appendText('xendit_invoice_id', form.xendit_invoice_id || '')
    appendText('xendit_invoice_url', form.xendit_invoice_url || '')
    appendText('xendit_amount', form.xendit_amount || '')
    appendText('xendit_paid_at', form.xendit_paid_at || '')
    appendText('xendit_external_id', form.xendit_external_id || '')
    appendText('xendit_expiry_date', form.xendit_expiry_date || '')


    // berkas flag
    appendBool('berkas_terunggah', !!form.berkas_terunggah)

    // files
    if (form.foto_file instanceof File) fd.append('foto', form.foto_file)
    if (form.bukti_prestasi_file instanceof File) fd.append('bukti_prestasi', form.bukti_prestasi_file)
    if (form.file_ktp_file instanceof File) fd.append('file_ktp', form.file_ktp_file)
    if (form.file_kk_file instanceof File) fd.append('file_kk', form.file_kk_file)

    if (Array.isArray(form.file_rapor_files) && form.file_rapor_files.length) {
      form.file_rapor_files.forEach((f) => {
        if (f instanceof File) fd.append('file_rapor[]', f)
      })
    }

    const response = await fetch('/api/pmb/register/yayasan', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: fd,
    })

    if (!response.ok) {
      let errorText = 'Terjadi kesalahan saat mengirim pendaftaran Beasiswa Yayasan.'
      try {
        const errorData = await response.json()
        if (errorData?.errors) {
          const firstError = Object.values(errorData.errors)?.[0]?.[0]
          if (firstError) errorText = firstError
        } else if (errorData?.message) {
          errorText = errorData.message
        }
      } catch (e) {}
      submitType.value = 'error'
      submitMessage.value = errorText
      return
    }

    const data = await response.json()
    submitType.value = 'success'
    submitMessage.value = data.message || 'Pendaftaran Beasiswa Yayasan berhasil disimpan ke sistem.'
    console.log('Data pendaftaran Yayasan:', data.data)
  } catch (error) {
    console.error(error)
    submitType.value = 'error'
    submitMessage.value = 'Gagal terhubung ke server. Silakan coba lagi beberapa saat.'
  }
}
</script>

<style>
::placeholder {
  color: #94a3b8;
  opacity: 1;
}
.dark ::placeholder {
  color: #ffffff;
  opacity: 0.9;
}

input,
textarea,
select {
  color: #0f172a;
}
.dark input,
.dark textarea,
.dark select {
  color: #ffffff;
}

select option {
  color: inherit;
}
.dark select option {
  color: #ffffff;
  background-color: #020617;
}

.dark input[type='date'] {
  color-scheme: dark;
}
.dark input[type='date']::-webkit-calendar-picker-indicator {
  filter: invert(1) brightness(1.2);
  opacity: 0.9;
}
.dark input[type='date']::-webkit-datetime-edit,
.dark input[type='date']::-webkit-datetime-edit-text,
.dark input[type='date']::-webkit-datetime-edit-month-field,
.dark input[type='date']::-webkit-datetime-edit-day-field,
.dark input[type='date']::-webkit-datetime-edit-year-field {
  color: #ffffff;
}

input[type='radio'],
input[type='checkbox'] {
  accent-color: #0ea5e9;
}
</style>

<template>
  <div class="min-h-screen flex bg-slate-100 dark:bg-slate-950">
    <div class="flex-1 flex flex-col">
      <AppHeader />

      <main class="flex-1 overflow-y-auto">
        <section class="py-6 md:py-8">
          <div class="max-w-4xl mx-auto">
            <header class="mb-4 md:mb-5">
              <h1 class="text-xl md:text-2xl font-semibold text-slate-900 dark:text-slate-50">
                Pendaftaran Beasiswa Yayasan
              </h1>
              <p class="text-sm text-slate-600 dark:text-slate-300">
                Isi formulir jalur Beasiswa Yayasan secara bertahap hingga selesai, lalu cetak kartu ujian.
              </p>
            </header>

            <form
              @submit.prevent="handleSubmit"
              class="rounded-2xl border border-blue-100/70 dark:border-slate-700
                     bg-white/95 dark:bg-slate-900/90
                     shadow-xl shadow-sky-900/10 dark:shadow-sky-900/30
                     px-4 sm:px-6 md:px-8 py-5 md:py-7 space-y-6"
            >
              <!-- STEP INDICATOR -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
                    Langkah {{ currentStep }} dari {{ steps.length }}
                  </p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    {{ steps[currentStep - 1].title }}
                  </p>
                </div>

                <div class="flex items-center gap-3">
                  <div class="flex-1 h-1.5 rounded-full bg-slate-200/80 dark:bg-slate-800 overflow-hidden">
                    <div
                      class="h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-500 transition-all"
                      :style="{ width: stepProgress + '%' }"
                    ></div>
                  </div>
                  <span class="text-[11px] text-slate-600 dark:text-slate-300 min-w-[60px] text-right">
                    {{ stepProgress }}%
                  </span>
                </div>

                <div class="mt-1 flex justify-between gap-2">
                  <div v-for="step in steps" :key="step.id" class="flex-1 flex flex-col items-center">
                    <div
                      :class="[
                        'flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold border',
                        currentStep === step.id
                          ? 'bg-sky-500 text-white border-sky-500 shadow-[0_0_10px_rgba(56,189,248,0.6)]'
                          : step.id < currentStep
                            ? 'bg-emerald-500 text-white border-emerald-500'
                            : 'bg-slate-100 text-slate-500 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600'
                      ]"
                    >
                      <span v-if="step.id < currentStep">✓</span>
                      <span v-else>{{ step.id }}</span>
                    </div>
                    <p class="mt-1 text-[10px] text-center text-slate-600 dark:text-slate-300">
                      {{ step.short }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- pesan submit / error -->
              <div v-if="submitMessage" class="mt-1">
                <div
                  :class="[
                    'rounded-xl px-3 py-2 text-xs flex items-start gap-2',
                    submitType === 'success'
                      ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-100 dark:border-emerald-700/70'
                      : 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-100 dark:border-rose-700/70'
                  ]"
                >
                  <span class="mt-0.5">
                    <SparklesIcon v-if="submitType === 'success'" class="w-4 h-4" />
                    <ExclamationTriangleIcon v-else class="w-4 h-4" />
                  </span>
                  <p>{{ submitMessage }}</p>
                </div>
              </div>

              <!-- STEP CONTENT -->
              <StepYayasanScholarship v-if="currentStep === 1" />

              <StepYayasanPersonalData v-else-if="currentStep === 2" />

              <StepYayasanSchoolData
                v-else-if="currentStep === 3"
                :provinces="provinces"
                :school-types="schoolTypes"
                :majors="majors"
                :years="years"
              />

              <StepYayasanAccount v-else-if="currentStep === 4" />
              <StepYayasanOtpWa v-else-if="currentStep === 5" />
              <StepYayasanPayment v-else-if="currentStep === 6" />
              <StepYayasanUploadDocs v-else-if="currentStep === 7" />
              <StepYayasanPrintExamCard v-else-if="currentStep === 8" />

              <!-- NAVIGASI STEP -->
              <div class="pt-4 flex items-center justify-between">
                <button
                  v-if="currentStep > 1"
                  type="button"
                  @click="goPrev"
                  class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs md:text-sm
                         border border-slate-300/80 text-slate-700 bg-white
                         hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-100
                         dark:border-slate-600 dark:hover:bg-slate-800
                         transition-colors"
                >
                  <ChevronDownIcon class="w-4 h-4 rotate-90" />
                  Kembali
                </button>

                <div class="ml-auto">
                  <button
                    v-if="currentStep < steps.length"
                    type="button"
                    @click="goNext"
                    class="inline-flex items-center justify-center gap-2
                           rounded-full px-5 md:px-7 py-2.5
                           text-xs md:text-sm font-semibold tracking-wide
                           bg-gradient-to-r from-sky-500 to-blue-500
                           text-white shadow-[0_10px_25px_rgba(37,99,235,0.4)]
                           hover:from-sky-400 hover:to-blue-500
                           focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2
                           focus:ring-offset-slate-100 dark:focus:ring-offset-slate-900
                           transition-all"
                  >
                    Lanjut ke Langkah {{ currentStep + 1 }}
                    <ChevronDownIcon class="w-4 h-4 -rotate-90" />
                  </button>

                  <button
                    v-else
                    type="submit"
                    class="inline-flex items-center justify-center gap-2
                           rounded-full px-6 md:px-8 py-2.5
                           text-sm font-semibold tracking-wide
                           bg-gradient-to-r from-emerald-500 to-sky-500
                           text-white shadow-[0_12px_30px_rgba(16,185,129,0.45)]
                           hover:from-emerald-400 hover:to-sky-500
                           focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2
                           focus:ring-offset-slate-100 dark:focus:ring-offset-slate-900
                           transition-all"
                  >
                    Kirim Pendaftaran Beasiswa Yayasan
                  </button>
                </div>
              </div>
            </form>
          </div>
        </section>
      </main>

    </div>
  </div>
</template>
