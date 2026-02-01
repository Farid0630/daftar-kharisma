<script setup>
import { ref, reactive, computed, provide, watch } from 'vue'

// layout global
import AppHeader from '@/components/layout/AppHeader.vue'

// notifikasi icon
import {
  SparklesIcon,
  ExclamationTriangleIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'

// step components
import StepPersonalData from './steps/StepPersonalData.vue'
import StepSchoolOrigin from './steps/StepSchoolOrigin.vue'
import StepContactAccount from './steps/StepContactAccount.vue'
import StepBerkasPersetujuan from './steps/StepBerkasPersetujuan.vue'
import StepOtpWa from './steps/StepOtpWa.vue'
import StepPaymentGateway from './steps/StepPaymentGateway.vue'
import StepPrintExamCard from './steps/StepPrintExamCard.vue'

/* ====== FORM STATE (shared ke semua step) ====== */
const form = reactive({
  // Data pribadi
  nama_lengkap: '',
  jenis_kelamin: 'L',
  tempat_lahir: '',
  tanggal_lahir: '',
  program_studi_1: '',
  program_studi_2: '',

  foto_nama: '',
  foto_preview: '',        // untuk preview UI
  foto_file: null,         // FILE ASLI untuk dikirim ke backend

  // Sekolah asal
  nama_sekolah: '',
  jenis_sekolah: '',
  kota_sekolah: '',
  jurusan: '',
  tahun_lulus: '',
  nisn: '',

  // Kontak & akun
  // (Jika step Anda butuh username, tambahkan: username: '')
  alamat_email: '',
  nomor_hp: '',
  kata_sandi: '',
  konfirmasi_kata_sandi: '',

  // Berkas & persetujuan
  setuju_syarat: false,
  setuju_kebenaran_data: false,
  berkas_terunggah: false,

  // Simpan FILE ASLI berkas wajib
  berkas_ktp_file: null,
  berkas_kk_file: null,
  berkas_rapor_files: [],

  // OTP
  kode_otp: '',
  otp_terverifikasi: false,
  otp_session_id: '',
  otp_expires_at: '',

  // Pembayaran (Xendit)
  metode_pembayaran: '',
  status_pembayaran: 'pending',
  xendit_external_id: '',
  xendit_invoice_id: '',
  xendit_invoice_url: '',
  xendit_expiry_date: '',
})

provide('pmbForm', form)

// jika nomor WA berubah setelah verifikasi, reset OTP
watch(
  () => form.nomor_hp,
  (newVal, oldVal) => {
    if (oldVal !== undefined && newVal !== oldVal) {
      form.kode_otp = ''
      form.otp_terverifikasi = false
      form.otp_session_id = ''
      form.otp_expires_at = ''
    }
  },
)

/* ====== OPTION DATA (JURUSAN, TAHUN LULUS) ====== */
const majors = [
  'IPA','IPS','BAHASA','AGAMA',
  'TKJ (Teknik Komputer & Jaringan)',
  'RPL (Rekayasa Perangkat Lunak)',
  'MULTIMEDIA','AKUNTANSI','OTKP / PERKANTORAN',
  'TEKNIK MESIN','TEKNIK KENDARAAN RINGAN','TEKNIK LISTRIK / ELEKTRO',
  'PARIWISATA / PERHOTELAN','LAINNYA / TIDAK ADA JURUSAN',
]

const years = []
for (let y = 2026; y >= 1985; y--) years.push(y)

/* ==== STEP WIZARD ==== */
const steps = [
  { id: 1, title: 'Data Pribadi', short: 'Data Pribadi' },
  { id: 2, title: 'Sekolah Asal', short: 'Sekolah Asal' },
  { id: 3, title: 'Kontak & Akun PMB', short: 'Kontak & Akun' },
  { id: 4, title: 'Verifikasi WhatsApp', short: 'Verifikasi' },
  { id: 5, title: 'Pembayaran Biaya Pendaftaran', short: 'Bayar' },
  { id: 6, title: 'Berkas & Persetujuan', short: 'Berkas & Setuju' },
  { id: 7, title: 'Cetak Kartu Ujian', short: 'Kartu Ujian' },
]

const currentStep = ref(1)

const stepProgress = computed(() =>
  Math.round((currentStep.value / steps.length) * 100),
)

/* ==== SUBMIT STATUS & VALIDASI STEP ==== */
const submitMessage = ref('')
const submitType = ref('') // 'success' | 'error'

const validateCurrentStep = () => {
  submitMessage.value = ''
  submitType.value = ''

  // Step 1
  if (currentStep.value === 1) {
    if (
      !form.nama_lengkap.trim() ||
      !form.tempat_lahir.trim() ||
      !form.tanggal_lahir ||
      !String(form.program_studi_1 || '').trim() ||
      !String(form.program_studi_2 || '').trim()
    ) {
      submitType.value = 'error'
      submitMessage.value = 'Lengkapi semua data pribadi termasuk pilihan program studi 1 dan 2.'
      return false
    }

    if (form.program_studi_1 === form.program_studi_2) {
      submitType.value = 'error'
      submitMessage.value = 'Program Studi Pilihan 1 dan 2 tidak boleh sama.'
      return false
    }
  }

  // Step 2
  if (currentStep.value === 2) {
    const requiredSchoolFields = [
      form.nama_sekolah,
      form.jenis_sekolah,
      form.kota_sekolah,
      form.jurusan,
      form.tahun_lulus,
    ]
    const allFilled = requiredSchoolFields.every((v) => String(v ?? '').trim() !== '')
    if (!allFilled) {
      submitType.value = 'error'
      submitMessage.value = 'Lengkapi semua data sekolah asal sebelum lanjut.'
      return false
    }
  }

  // Step 3
  if (currentStep.value === 3) {
    if (
      !String(form.alamat_email || '').trim() ||
      !String(form.nomor_hp || '').trim() ||
      !form.kata_sandi ||
      !form.konfirmasi_kata_sandi
    ) {
      submitType.value = 'error'
      submitMessage.value = 'Lengkapi email, nomor HP, dan kata sandi Anda.'
      return false
    }
    if (form.kata_sandi !== form.konfirmasi_kata_sandi) {
      submitType.value = 'error'
      submitMessage.value = 'Kata sandi dan konfirmasi kata sandi tidak sama.'
      return false
    }
  }

  // Step 4 – OTP
  if (currentStep.value === 4) {
    const verified =
      form.otp_terverifikasi === true ||
      form.otp_terverifikasi === 1 ||
      form.otp_terverifikasi === '1'

    if (!verified) {
      submitType.value = 'error'
      submitMessage.value = 'Mohon kirim dan verifikasi kode OTP WhatsApp terlebih dahulu.'
      return false
    }
  }

  // Step 5 – Pembayaran
  if (currentStep.value === 5) {
    if (!form.xendit_external_id || !form.xendit_invoice_url) {
      submitType.value = 'error'
      submitMessage.value = 'Buat invoice Xendit terlebih dahulu.'
      return false
    }
    if (form.status_pembayaran !== 'paid') {
      submitType.value = 'error'
      submitMessage.value = 'Status pembayaran belum LUNAS.'
      return false
    }
  }

  // Step 6 – Berkas + Persetujuan
  if (currentStep.value === 6) {
    if (!form.berkas_terunggah) {
      submitType.value = 'error'
      submitMessage.value = 'Unggah KTP, KK, dan Rapor terlebih dahulu.'
      return false
    }
    if (!form.setuju_syarat || !form.setuju_kebenaran_data) {
      submitType.value = 'error'
      submitMessage.value = 'Mohon centang persetujuan syarat & kebenaran data terlebih dahulu.'
      return false
    }
  }

  return true
}

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

// ====== SUBMIT (FormData / multipart) ======
const handleSubmit = async () => {
  if (!validateCurrentStep()) return

  submitMessage.value = ''
  submitType.value = ''

  try {
    const fd = new FormData()

    // ===== scalar fields =====
    fd.append('nama_lengkap', form.nama_lengkap)
    fd.append('jenis_kelamin', form.jenis_kelamin)
    fd.append('tempat_lahir', form.tempat_lahir)
    fd.append('tanggal_lahir', form.tanggal_lahir)
    fd.append('program_studi_1', form.program_studi_1)
    fd.append('program_studi_2', form.program_studi_2)

    fd.append('nama_sekolah', form.nama_sekolah)
    fd.append('jenis_sekolah', form.jenis_sekolah)
    fd.append('kota_sekolah', form.kota_sekolah)
    fd.append('jurusan', form.jurusan)
    fd.append('tahun_lulus', String(form.tahun_lulus || ''))
    if (String(form.nisn || '').trim()) fd.append('nisn', form.nisn)

    fd.append('alamat_email', form.alamat_email)
    fd.append('nomor_hp', form.nomor_hp)
    fd.append('kata_sandi', form.kata_sandi)
    fd.append('konfirmasi_kata_sandi', form.konfirmasi_kata_sandi)

    // boolean lebih aman kirim 1/0 supaya validasi boolean Laravel selalu lolos
    fd.append('otp_terverifikasi', form.otp_terverifikasi ? '1' : '0')
    fd.append('setuju_syarat', form.setuju_syarat ? '1' : '0')
    fd.append('setuju_kebenaran_data', form.setuju_kebenaran_data ? '1' : '0')

    // pembayaran
    if (String(form.metode_pembayaran || '').trim()) fd.append('metode_pembayaran', form.metode_pembayaran)
    fd.append('status_pembayaran', form.status_pembayaran)

    // ===== files =====
    if (form.foto_file instanceof File) {
      fd.append('foto', form.foto_file)
    }

    // berkas[] multi
    if (form.berkas_ktp_file instanceof File) fd.append('berkas[]', form.berkas_ktp_file)
    if (form.berkas_kk_file instanceof File) fd.append('berkas[]', form.berkas_kk_file)
    if (Array.isArray(form.berkas_rapor_files)) {
      form.berkas_rapor_files.forEach((f) => {
        if (f instanceof File) fd.append('berkas[]', f)
      })
    }

    // kirim
    const response = await fetch('/api/pmb/register', {
      method: 'POST',
      headers: { Accept: 'application/json' }, // JANGAN set Content-Type saat pakai FormData
      body: fd,
    })

    if (!response.ok) {
      let errorText = 'Terjadi kesalahan saat mengirim pendaftaran.'
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
    submitMessage.value = data.message || 'Pendaftaran berhasil disimpan ke sistem.'
    console.log('Data pendaftaran tersimpan:', data.data)
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
                Pendaftaran Jalur Mandiri
              </h1>
              <p class="text-sm text-slate-600 dark:text-slate-300">
                Registrasi Calon Mahasiswa
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
              <StepPersonalData v-if="currentStep === 1" />

              <StepSchoolOrigin
                v-else-if="currentStep === 2"
                :years="years"
                :majors="majors"
              />

              <StepContactAccount v-else-if="currentStep === 3" />

              <!-- ✅ OTP WA -->
              <StepOtpWa v-else-if="currentStep === 4" />

              <!-- ✅ Pembayaran Xendit -->
              <StepPaymentGateway v-else-if="currentStep === 5" />

              <!-- ✅ Berkas setelah Pembayaran -->
              <StepBerkasPersetujuan v-else-if="currentStep === 6" />

              <StepPrintExamCard v-else-if="currentStep === 7" />

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
                    Kirim Pendaftaran
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
