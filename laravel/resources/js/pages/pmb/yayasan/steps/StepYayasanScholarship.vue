<!-- resources/js/Components/pmb/yayasan/steps/StepYayasanScholarship.vue -->
<script setup>
import { inject, computed, watch, ref } from 'vue'
import { AcademicCapIcon, SparklesIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline'

const form = inject('pmbForm')
const fileError = ref('')

/* ===================== SAFETY DEFAULT ===================== */
if (!form.jalur_pendaftaran) form.jalur_pendaftaran = 'Beasiswa Yayasan'
if (!Array.isArray(form.kategori_prestasi)) form.kategori_prestasi = []
if (!('jenis_beasiswa' in form)) form.jenis_beasiswa = ''
if (!('deskripsi_prestasi' in form)) form.deskripsi_prestasi = ''
if (!('bukti_prestasi_nama' in form)) form.bukti_prestasi_nama = ''
if (!('bukti_prestasi_file' in form)) form.bukti_prestasi_file = null

/* ===================== PROGRAM STUDI (2 PILIHAN) ===================== */
const programOptions = [
  { value: 'S1 Teknik Informatika', label: 'S1 Teknik Informatika', desc: 'Fokus pada software, AI, dan rekayasa sistem.' },
  { value: 'S1 Sistem Informasi', label: 'S1 Sistem Informasi', desc: 'Menghubungkan TI dengan proses bisnis dan manajemen.' },
  { value: 'S1 Bisnis Digital', label: 'S1 Bisnis Digital', desc: 'Strategi bisnis modern & pemasaran digital berbasis teknologi.' },
]

// Pastikan field ada
if (!('program_studi_1' in form) || !form.program_studi_1) {
  form.program_studi_1 = form.program_studi || programOptions[0].value
}
if (!('program_studi_2' in form)) {
  form.program_studi_2 = ''
}
if (!form.program_studi) {
  form.program_studi = form.program_studi_1
}

// sync prodi_1 -> program_studi (compat)
watch(
  () => form.program_studi_1,
  (val) => {
    if (val) form.program_studi = val
    if (form.program_studi_2 && form.program_studi_2 === val) {
      form.program_studi_2 = ''
    }
  },
  { immediate: true },
)

const programOptions2 = computed(() => programOptions.filter((p) => p.value !== form.program_studi_1))
const selectedProgram1 = computed(() => programOptions.find((p) => p.value === form.program_studi_1))
const selectedProgram2 = computed(() => programOptions.find((p) => p.value === form.program_studi_2))

/* ===================== KATEGORI PRESTASI ===================== */
const prestasiAcademicOptions = ['Nilai Rapor', 'Karya Ilmiah', 'Olimpiade Sains', 'Lainnya (Akademik)']
const prestasiNonAcademicOptions = ['Runner / Atletik', 'Basket', 'Futsal / Sepak Bola', 'Bela Diri', 'Hafidz Qur’an', 'Lainnya (Non Akademik)']

const akademikSet = new Set(prestasiAcademicOptions)
const nonAkademikSet = new Set(prestasiNonAcademicOptions)

const isAkademik = computed(() => form.jenis_beasiswa === 'akademik')
const isNonAkademik = computed(() => form.jenis_beasiswa === 'non_akademik')

const totalKategoriDipilih = computed(() => (form.kategori_prestasi?.length || 0))
const isKategoriSelected = (opt) => Array.isArray(form.kategori_prestasi) && form.kategori_prestasi.includes(opt)

const uniqueArray = (arr) => Array.from(new Set((arr || []).filter(Boolean)))
const filterKategoriByType = (type, list) => {
  const allowed = type === 'akademik' ? akademikSet : nonAkademikSet
  return (list || []).filter((x) => allowed.has(x))
}

watch(
  () => form.jenis_beasiswa,
  (val) => {
    if (val !== 'akademik' && val !== 'non_akademik') return
    const clean = uniqueArray(form.kategori_prestasi)
    form.kategori_prestasi = filterKategoriByType(val, clean)
  },
  { immediate: true },
)

const toggleKategoriByType = (type, opt) => {
  if (form.jenis_beasiswa !== type) return
  if (!Array.isArray(form.kategori_prestasi)) form.kategori_prestasi = []

  const idx = form.kategori_prestasi.indexOf(opt)
  if (idx === -1) form.kategori_prestasi.push(opt)
  else form.kategori_prestasi.splice(idx, 1)

  const clean = uniqueArray(form.kategori_prestasi)
  form.kategori_prestasi = filterKategoriByType(type, clean)
}

/* ===================== UPLOAD BUKTI PRESTASI (SIMPAN FILE) ===================== */
const handleBuktiPrestasiChange = (event) => {
  fileError.value = ''
  const input = event.target
  const files = Array.from(input.files || [])

  if (!files.length) {
    form.bukti_prestasi_nama = ''
    form.bukti_prestasi_file = null
    return
  }

  const f = files[0]

  // Validasi ringan client-side (backend tetap validasi utama)
  const allowed = ['application/pdf', 'image/jpeg', 'image/png']
  if (!allowed.includes(f.type)) {
    fileError.value = 'Format bukti prestasi harus PDF/JPG/PNG.'
    form.bukti_prestasi_nama = ''
    form.bukti_prestasi_file = null
    input.value = ''
    return
  }
  const maxBytes = 2 * 1024 * 1024
  if (f.size > maxBytes) {
    fileError.value = 'Ukuran bukti prestasi maksimal 2MB.'
    form.bukti_prestasi_nama = ''
    form.bukti_prestasi_file = null
    input.value = ''
    return
  }

  form.bukti_prestasi_file = f
  form.bukti_prestasi_nama = f.name
}
</script>

<template>
  <div class="space-y-6">
    <!-- HEADER -->
    <div class="flex items-start gap-3">
      <div
        class="h-10 w-10 flex items-center justify-center rounded-2xl
               bg-gradient-to-br from-sky-500/25 via-cyan-400/20 to-emerald-400/25
               text-sky-700 dark:text-sky-50
               ring-1 ring-sky-500/20 dark:ring-sky-400/20
               shadow-[0_0_24px_rgba(56,189,248,0.18)]
               dark:shadow-[0_0_32px_rgba(56,189,248,0.45)]"
      >
        <SparklesIcon class="w-5 h-5" />
      </div>

      <div class="space-y-1.5">
        <h2 class="text-sm md:text-base font-semibold tracking-wide text-slate-900 dark:text-slate-50">
          Jalur Beasiswa Yayasan & Program Studi
        </h2>
        <p class="text-[11px] md:text-xs text-slate-600 dark:text-slate-400">
          Tentukan program studi tujuan dan jenis Beasiswa Yayasan. Data ini akan menjadi dasar penilaian prestasi dan verifikasi berkas kamu.
        </p>

        <div class="flex flex-wrap items-center gap-2 pt-1">
          <span
            class="inline-flex items-center gap-1.5 rounded-full
                   bg-sky-500/10 text-sky-700 dark:text-sky-200
                   border border-sky-500/30 dark:border-sky-400/30
                   px-3 py-1 text-[11px] font-medium
                   backdrop-blur-sm"
          >
            <AcademicCapIcon class="w-3.5 h-3.5" />
            <span>Jalur: Beasiswa Yayasan</span>
          </span>

          <span
            v-if="totalKategoriDipilih > 0"
            class="inline-flex items-center gap-1.5 rounded-full
                   bg-emerald-500/10 text-emerald-700 dark:text-emerald-200
                   border border-emerald-500/30 dark:border-emerald-400/30
                   px-2.5 py-0.5 text-[10px] font-medium
                   backdrop-blur-sm"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
            {{ totalKategoriDipilih }} kategori prestasi dipilih
          </span>
        </div>
      </div>
    </div>

    <!-- PROGRAM STUDI 1 & 2 -->
    <div
      class="rounded-2xl border border-sky-500/15
             bg-white/70 dark:bg-slate-900/70
             backdrop-blur-md px-4 py-4 space-y-3"
    >
      <div class="grid md:grid-cols-2 gap-4">
        <!-- Prodi 1 -->
        <div class="space-y-2">
          <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
            Program Studi 1 (utama) <span class="text-red-500">*</span>
          </label>

          <div class="relative rounded-xl border border-slate-300/70 dark:border-slate-600/70 bg-white/95 dark:bg-slate-950/50 text-sm overflow-hidden">
            <select
              v-model="form.program_studi_1"
              class="w-full bg-transparent rounded-xl pl-3 pr-8 py-2.5
                     text-slate-900 dark:text-slate-100
                     focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                     dark:focus:ring-sky-500"
            >
              <option v-for="prodi in programOptions" :key="prodi.value" :value="prodi.value">
                {{ prodi.label }}
              </option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 dark:text-slate-300">
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none">
                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </span>
          </div>

          <p v-if="selectedProgram1" class="text-[10px] text-slate-600 dark:text-slate-400">
            {{ selectedProgram1.desc }}
          </p>
        </div>

        <!-- Prodi 2 -->
        <div class="space-y-2">
          <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
            Program Studi 2 (opsional)
          </label>

          <div class="relative rounded-xl border border-slate-300/70 dark:border-slate-600/70 bg-white/95 dark:bg-slate-950/50 text-sm overflow-hidden">
            <select
              v-model="form.program_studi_2"
              class="w-full bg-transparent rounded-xl pl-3 pr-8 py-2.5
                     text-slate-900 dark:text-slate-100
                     focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                     dark:focus:ring-sky-500"
            >
              <option value="">-- Tidak ada (opsional) --</option>
              <option v-for="prodi in programOptions2" :key="prodi.value" :value="prodi.value">
                {{ prodi.label }}
              </option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 dark:text-slate-300">
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none">
                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </span>
          </div>

          <p v-if="selectedProgram2" class="text-[10px] text-slate-600 dark:text-slate-400">
            {{ selectedProgram2.desc }}
          </p>
          <p v-else class="text-[10px] text-slate-500 dark:text-slate-400">
            Program Studi 2 digunakan sebagai alternatif jika diperlukan.
          </p>
        </div>
      </div>
    </div>

    <!-- JENIS BEASISWA + KATEGORI (tetap seperti Anda) -->
    <div class="space-y-2">
      <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
        Jenis Beasiswa Yayasan <span class="text-red-500">*</span>
      </p>

      <div class="grid sm:grid-cols-2 gap-4">
        <!-- AKADEMIK -->
        <label
          class="relative cursor-pointer rounded-2xl px-4 py-3.5 text-xs space-y-2.5
                 bg-white/80 dark:bg-slate-950/60
                 border border-slate-200/80 dark:border-slate-700/70
                 backdrop-blur-md transition-all"
          :class="isAkademik
            ? 'border-sky-400/70 shadow-[0_0_28px_rgba(56,189,248,0.25)] dark:shadow-[0_0_32px_rgba(56,189,248,0.6)]'
            : 'hover:border-sky-400/40 hover:shadow-[0_0_18px_rgba(56,189,248,0.18)] dark:hover:shadow-[0_0_20px_rgba(56,189,248,0.35)]'"
        >
          <div class="flex items-start gap-2">
            <span class="mt-0.5">
              <span
                class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full
                       border border-slate-400/70 dark:border-slate-500/80
                       bg-white/70 dark:bg-slate-900/80 transition-all"
                :class="isAkademik ? 'bg-sky-500 border-sky-300 ring-4 ring-sky-500/20 dark:ring-sky-500/30' : ''"
              >
                <span v-if="isAkademik" class="h-1.5 w-1.5 rounded-full bg-white" />
              </span>
            </span>

            <input type="radio" name="jenis_beasiswa" value="akademik" v-model="form.jenis_beasiswa" class="sr-only" />

            <div>
              <p class="font-semibold text-slate-900 dark:text-slate-50">Prestasi Akademik</p>
              <p class="text-[11px] text-slate-600 dark:text-slate-400">
                Nilai rapor, karya ilmiah, olimpiade sains, atau prestasi akademik lainnya.
              </p>
            </div>
          </div>

          <div class="pt-1 flex flex-wrap gap-1.5">
            <button
              v-for="opt in prestasiAcademicOptions"
              :key="opt"
              type="button"
              :disabled="!isAkademik"
              class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] border transition-all"
              :class="[
                !isAkademik
                  ? 'opacity-45 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-900/40 dark:text-slate-500 dark:border-slate-700'
                  : isKategoriSelected(opt)
                    ? 'bg-sky-500 text-white border-sky-400'
                    : 'bg-white/60 text-slate-700 border-slate-200 hover:border-sky-400/60 hover:text-sky-700 dark:bg-slate-900/60 dark:text-slate-300 dark:border-slate-600 dark:hover:border-sky-400/70 dark:hover:text-sky-200'
              ]"
              @click="toggleKategoriByType('akademik', opt)"
            >
              <span class="h-1.5 w-1.5 rounded-full" :class="isKategoriSelected(opt) ? 'bg-white' : 'bg-slate-400 dark:bg-slate-500'"></span>
              <span>{{ opt }}</span>
              <input type="checkbox" :value="opt" v-model="form.kategori_prestasi" class="sr-only" />
            </button>
          </div>
        </label>

        <!-- NON AKADEMIK -->
        <label
          class="relative cursor-pointer rounded-2xl px-4 py-3.5 text-xs space-y-2.5
                 bg-white/80 dark:bg-slate-950/60
                 border border-slate-200/80 dark:border-slate-700/70
                 backdrop-blur-md transition-all"
          :class="isNonAkademik
            ? 'border-emerald-400/70 shadow-[0_0_28px_rgba(16,185,129,0.22)] dark:shadow-[0_0_32px_rgba(16,185,129,0.7)]'
            : 'hover:border-emerald-400/40 hover:shadow-[0_0_18px_rgba(16,185,129,0.16)] dark:hover:shadow-[0_0_20px_rgba(16,185,129,0.4)]'"
        >
          <div class="flex items-start gap-2">
            <span class="mt-0.5">
              <span
                class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full
                       border border-slate-400/70 dark:border-slate-500/80
                       bg-white/70 dark:bg-slate-900/80 transition-all"
                :class="isNonAkademik ? 'bg-emerald-500 border-emerald-300 ring-4 ring-emerald-500/20 dark:ring-emerald-500/30' : ''"
              >
                <span v-if="isNonAkademik" class="h-1.5 w-1.5 rounded-full bg-white" />
              </span>
            </span>

            <input type="radio" name="jenis_beasiswa" value="non_akademik" v-model="form.jenis_beasiswa" class="sr-only" />

            <div>
              <p class="font-semibold text-slate-900 dark:text-slate-50">Prestasi Non Akademik</p>
              <p class="text-[11px] text-slate-600 dark:text-slate-400">
                Olahraga, seni, bela diri, hafidz Qur’an, atau prestasi non akademik lainnya.
              </p>
            </div>
          </div>

          <div class="pt-1 flex flex-wrap gap-1.5">
            <button
              v-for="opt in prestasiNonAcademicOptions"
              :key="opt"
              type="button"
              :disabled="!isNonAkademik"
              class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] border transition-all"
              :class="[
                !isNonAkademik
                  ? 'opacity-45 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-900/40 dark:text-slate-500 dark:border-slate-700'
                  : isKategoriSelected(opt)
                    ? 'bg-emerald-500 text-white border-emerald-400'
                    : 'bg-white/60 text-slate-700 border-slate-200 hover:border-emerald-400/60 hover:text-emerald-700 dark:bg-slate-900/60 dark:text-slate-300 dark:border-slate-600 dark:hover:border-emerald-400/70 dark:hover:text-emerald-200'
              ]"
              @click="toggleKategoriByType('non_akademik', opt)"
            >
              <span class="h-1.5 w-1.5 rounded-full" :class="isKategoriSelected(opt) ? 'bg-white' : 'bg-slate-400 dark:bg-slate-500'"></span>
              <span>{{ opt }}</span>
              <input type="checkbox" :value="opt" v-model="form.kategori_prestasi" class="sr-only" />
            </button>
          </div>
        </label>
      </div>
    </div>

    <!-- DESKRIPSI & BUKTI -->
    <div class="grid md:grid-cols-[1.6fr_1fr] gap-4">
      <div class="rounded-2xl border border-slate-200/80 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/60 backdrop-blur-md px-4 py-3.5">
        <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Deskripsi singkat prestasi (opsional)
        </label>
        <textarea
          v-model="form.deskripsi_prestasi"
          rows="3"
          class="w-full rounded-xl border border-slate-200/80 dark:border-slate-700/80 bg-white/90 dark:bg-slate-950/50 text-xs px-3 py-2
                 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 dark:focus:ring-sky-500 dark:focus:border-sky-500"
          placeholder="Contoh: Juara 2 Olimpiade Sains tingkat provinsi, rata-rata nilai rapor di atas 85, kapten tim futsal sekolah, dll."
        ></textarea>
      </div>

      <div class="rounded-2xl border border-slate-200/80 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/60 backdrop-blur-md px-4 py-3.5 space-y-2">
        <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
          Upload bukti prestasi <span class="text-red-500">*</span>
        </label>

        <input
          id="bukti-prestasi-input"
          type="file"
          @change="handleBuktiPrestasiChange"
          class="hidden"
          accept=".pdf,.jpg,.jpeg,.png"
        />

        <label
          for="bukti-prestasi-input"
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full
                 bg-gradient-to-r from-sky-500 to-cyan-500
                 text-white text-[11px] font-semibold
                 hover:from-sky-400 hover:to-cyan-400 cursor-pointer transition-all"
        >
          <ArrowUpTrayIcon class="w-4 h-4" />
          <span>Pilih file bukti prestasi</span>
        </label>

        <p class="text-[10px] text-slate-600 dark:text-slate-400">
          Format: PDF/JPG/PNG, ukuran disarankan &lt; 2 MB.
        </p>

        <p v-if="fileError" class="text-[10px] text-rose-500">
          {{ fileError }}
        </p>

        <p v-if="form.bukti_prestasi_nama" class="text-[10px] text-slate-900 dark:text-slate-200 truncate">
          File terpilih: {{ form.bukti_prestasi_nama }}
        </p>
      </div>
    </div>
  </div>
</template>
