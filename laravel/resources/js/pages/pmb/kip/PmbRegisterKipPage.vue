<!-- resources/js/pages/pmb/kip/PmbRegisterKipPage.vue -->
<script setup>
import { ref, reactive, computed, provide, watch } from 'vue'

// layout global
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'

// icon notifikasi
import {
  SparklesIcon,
  ExclamationTriangleIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'

// STEP khusus KIP
import StepKipPersonalData from './steps/StepKipPersonalData.vue'
import StepKipSchoolData from './steps/StepKipSchoolData.vue'
import StepKipAccount from './steps/StepKipAccount.vue'
import StepKipOtpWa from './steps/StepKipOtpWa.vue'
import StepKipUploadBerkas from './steps/StepKipUploadBerkas.vue'

/* ========= FORM STATE KIP ========= */
const form = reactive({
  // STEP 1 – Data diri
  nama_lengkap: '',
  jenis_kelamin: 'L',
  tempat_lahir: '',
  tanggal_lahir: '',
  nik: '',
  nomor_kk: '',
  foto_nama: '',
  foto_preview: '',
  foto_file: null, // ✅ file real (optional)
  program_studi_1: '',
  program_studi_2: '',

  // STEP 2 – Data sekolah
  nama_sekolah: '',
  npsn_sekolah: '',
  nisn: '',
  jenis_sekolah: '',
  jurusan_sekolah: '',
  kabkota_sekolah: '',
  tahun_lulus: '',

  // STEP 3 – Akun & WhatsApp (KIP)
  alamat_email: '',
  nomor_hp: '',
  kata_sandi: '',
  konfirmasi_kata_sandi: '',

  // STEP 4 – OTP WA
  kode_otp: '',
  otp_terverifikasi: false,

  // STEP 5 – Upload KTP & KK (REAL FILE)
  kip_ktp_file: null,
  kip_ktp_name: '',
  kip_kk_file: null,
  kip_kk_name: '',

  // Tambahan flags
  status_pembayaran: 'paid', // KIP → bebas biaya
  berkas_terunggah: false,
})

// share ke semua step via inject('pmbForm')
provide('pmbForm', form)

// Jika nomor WA berubah setelah verifikasi, reset OTP
watch(
  () => form.nomor_hp,
  (newVal, oldVal) => {
    if (oldVal !== undefined && newVal !== oldVal && form.otp_terverifikasi) {
      form.otp_terverifikasi = false
      form.kode_otp = ''
    }
  },
)

/* ========= STEP WIZARD (5 LANGKAH) ========= */
const steps = [
  { id: 1, title: 'Data Diri', short: 'Data Diri' },
  { id: 2, title: 'Data Sekolah', short: 'Sekolah' },
  { id: 3, title: 'Akun & WhatsApp', short: 'Akun' },
  { id: 4, title: 'Verifikasi OTP WA', short: 'Verifikasi' },
  { id: 5, title: 'Upload Berkas', short: 'Berkas' },
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

const isDigits = (s) => /^[0-9]+$/.test(String(s || ''))

const validateCurrentStep = () => {
  submitMessage.value = ''
  submitType.value = ''

  // STEP 1 – Data diri
  if (currentStep.value === 1) {
    const nama = (form.nama_lengkap || '').trim()
    const tempat = (form.tempat_lahir || '').trim()
    const nik = (form.nik || '').trim()
    const kk = (form.nomor_kk || '').trim()

    if (!nama || !form.jenis_kelamin || !tempat || !form.tanggal_lahir || !nik || !kk) {
      return setError(
        'Lengkapi Nama Lengkap, Jenis Kelamin, Tempat/Tanggal Lahir, NIK, dan Nomor KK terlebih dahulu.',
      )
    }

    if (!form.program_studi_1) {
      return setError('Pilih Program Studi 1 terlebih dahulu.')
    }

    if (form.program_studi_2 && form.program_studi_2 === form.program_studi_1) {
      return setError('Program Studi 2 tidak boleh sama dengan Program Studi 1.')
    }

    if (nik.length !== 16 || !isDigits(nik)) {
      return setError('NIK harus 16 digit angka.')
    }

    if (kk.length !== 16 || !isDigits(kk)) {
      return setError('Nomor KK harus 16 digit angka.')
    }

    return true
  }

  // STEP 2 – Data sekolah
  if (currentStep.value === 2) {
    if (
      !(form.nama_sekolah || '').trim() ||
      !(form.npsn_sekolah || '').trim() ||
      !(form.nisn || '').trim() ||
      !form.jenis_sekolah ||
      !(form.jurusan_sekolah || '').trim() ||
      !(form.kabkota_sekolah || '').trim() ||
      !form.tahun_lulus
    ) {
      return setError(
        'Lengkapi semua data sekolah: Nama Sekolah, NPSN, NISN, Jenis Sekolah, Jurusan, Kabupaten/Kota, dan Tahun Lulus.',
      )
    }
    return true
  }

  // STEP 3 – Akun & WhatsApp
  if (currentStep.value === 3) {
    if (
      !(form.alamat_email || '').trim() ||
      !(form.nomor_hp || '').trim() ||
      !form.kata_sandi ||
      !form.konfirmasi_kata_sandi
    ) {
      return setError(
        'Lengkapi email, nomor WhatsApp, kata sandi, dan konfirmasi kata sandi akun PMB.',
      )
    }

    if (form.kata_sandi !== form.konfirmasi_kata_sandi) {
      return setError('Kata sandi dan konfirmasi kata sandi tidak sama.')
    }

    if (String(form.kata_sandi).length < 8) {
      return setError('Kata sandi minimal 8 karakter.')
    }

    return true
  }

  // STEP 4 – OTP WA
  if (currentStep.value === 4) {
    if (!(form.nomor_hp || '').trim()) {
      return setError('Nomor WhatsApp wajib diisi pada langkah Akun & WhatsApp.')
    }
    if (!form.otp_terverifikasi) {
      return setError('Mohon kirim dan verifikasi kode OTP WhatsApp terlebih dahulu.')
    }
    return true
  }

  // STEP 5 – Upload KTP & KK (REAL FILE)
  if (currentStep.value === 5) {
    if (!form.kip_ktp_file) {
      return setError('Mohon upload KTP dan Kartu Keluarga (KK) terlebih dahulu.')
    }
    form.berkas_terunggah = true
    return true
  }

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

/* ========= SUBMIT KE BACKEND (FormData) ========= */
const handleSubmit = async () => {
  if (!validateCurrentStep()) return

  submitMessage.value = ''
  submitType.value = ''

  try {
    const fd = new FormData()

    // ===== non-file =====
    fd.append('jalur_pendaftaran', 'KIP')

    fd.append('nama_lengkap', form.nama_lengkap)
    fd.append('jenis_kelamin', form.jenis_kelamin)
    fd.append('tempat_lahir', form.tempat_lahir)
    fd.append('tanggal_lahir', form.tanggal_lahir)
    fd.append('nik', form.nik)
    fd.append('nomor_kk', form.nomor_kk)

    fd.append('program_studi_1', form.program_studi_1)
    fd.append('program_studi_2', form.program_studi_2 || '')

    fd.append('nama_sekolah', form.nama_sekolah)
    fd.append('npsn_sekolah', form.npsn_sekolah)
    fd.append('nisn', form.nisn)
    fd.append('jenis_sekolah', form.jenis_sekolah)
    fd.append('jurusan_sekolah', form.jurusan_sekolah)
    fd.append('kabkota_sekolah', form.kabkota_sekolah)
    fd.append('tahun_lulus', String(form.tahun_lulus))

    fd.append('alamat_email', form.alamat_email)
    fd.append('nomor_hp', form.nomor_hp)
    fd.append('kata_sandi', form.kata_sandi)
    fd.append('konfirmasi_kata_sandi', form.konfirmasi_kata_sandi)

    fd.append('kode_otp', form.kode_otp || '')
    fd.append('otp_terverifikasi', form.otp_terverifikasi ? '1' : '0')

    // KIP gratis → backend akan paksa paid juga
    fd.append('status_pembayaran', 'paid')
    fd.append('berkas_terunggah', form.berkas_terunggah ? '1' : '0')

    // ===== files =====
    if (form.kip_ktp_file) fd.append('kip_ktp', form.kip_ktp_file)
    // if (form.kip_kk_file) fd.append('kip_kk', form.kip_kk_file)
    if (form.foto_file) fd.append('foto', form.foto_file) // optional

    const response = await fetch('/api/pmb/register/kip', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        // NOTE: jangan set Content-Type untuk FormData
      },
      body: fd,
    })

    if (!response.ok) {
      let errorText = 'Terjadi kesalahan saat mengirim pendaftaran EXPO.'
      try {
        const errorData = await response.json()
        if (errorData?.errors) {
          const firstError = Object.values(errorData.errors)[0][0]
          errorText = firstError
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
    submitMessage.value = data.message || 'Pendaftaran  berhasil disimpan ke sistem.'
    console.log('Data pendaftaran EXPO:', data.data)
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
                Pendaftaran Jalur EXPO
              </h1>
              <p class="text-sm text-slate-600 dark:text-slate-300">
                Isi formulir EXPO secara bertahap hingga selesai, lalu kirim pendaftaran.
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
                            : 'bg-slate-100 text-slate-500 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600',
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
                      : 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-100 dark:border-rose-700/70',
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
              <StepKipPersonalData v-if="currentStep === 1" />
              <StepKipSchoolData v-else-if="currentStep === 2" />
              <StepKipAccount v-else-if="currentStep === 3" />
              <StepKipOtpWa v-else-if="currentStep === 4" />
              <StepKipUploadBerkas v-else-if="currentStep === 5" />

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
                    Kirim Pendaftaran EXPO
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
