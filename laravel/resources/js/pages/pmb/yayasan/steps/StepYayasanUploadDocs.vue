<!-- resources/js/Pages/pmb/yayasan/steps/StepYayasanUploadDocs.vue -->
<script setup>
import { inject } from 'vue'
import { ArrowUpTrayIcon, IdentificationIcon } from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

// ===== SAFETY DEFAULT (agar tidak undefined) =====
if (!('file_ktp_file' in form)) form.file_ktp_file = null
if (!('file_ktp_nama' in form)) form.file_ktp_nama = ''

if (!('file_kk_file' in form)) form.file_kk_file = null
if (!('file_kk_nama' in form)) form.file_kk_nama = ''

if (!('file_rapor_files' in form)) form.file_rapor_files = []
if (!('file_rapor_nama' in form)) form.file_rapor_nama = ''

const handleKtpChange = (e) => {
  const files = Array.from(e.target.files || [])
  const file = files[0] || null
  form.file_ktp_file = file
  form.file_ktp_nama = file ? file.name : ''
}

const handleKkChange = (e) => {
  const files = Array.from(e.target.files || [])
  const file = files[0] || null
  form.file_kk_file = file
  form.file_kk_nama = file ? file.name : ''
}

const handleRaporChange = (e) => {
  const files = Array.from(e.target.files || [])
  form.file_rapor_files = files

  if (!files.length) {
    form.file_rapor_nama = ''
    return
  }

  const first = files[0]?.name || ''
  form.file_rapor_nama =
    files.length === 1 ? first : `${files.length} file dipilih (contoh: ${first})`
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <div
        class="h-8 w-8 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600
               dark:bg-sky-500/20 dark:text-sky-300"
      >
        <IdentificationIcon class="w-4 h-4" />
      </div>
      <div>
        <h2 class="text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200">
          Upload Berkas Beasiswa Yayasan
        </h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Upload scan KTP, Kartu Keluarga, dan rapor kelas X & XI.
        </p>
      </div>
    </div>

    <div class="space-y-3 text-xs">
      <!-- KTP -->
      <div
        class="rounded-2xl border border-slate-200/80 dark:border-slate-700/80
               bg-slate-50/80 dark:bg-slate-900/80 px-3 py-2.5 flex flex-col gap-1.5"
      >
        <div class="flex items-center justify-between gap-2">
          <div>
            <p class="font-medium text-slate-800 dark:text-slate-100">
              KTP <span class="text-red-500">*</span>
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
              Scan / foto Kartu Tanda Penduduk (KTP) siswa.
            </p>
          </div>
          <div>
            <input
              id="ktp-input"
              type="file"
              class="hidden"
              accept=".pdf,.jpg,.jpeg,.png"
              @change="handleKtpChange"
            />
            <label
              for="ktp-input"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                     bg-sky-500 text-white text-[11px] font-semibold
                     shadow-sm hover:bg-sky-400 cursor-pointer transition-colors"
            >
              <ArrowUpTrayIcon class="w-4 h-4" />
              <span>Pilih file</span>
            </label>
          </div>
        </div>
        <p v-if="form.file_ktp_nama" class="text-[10px] text-slate-600 dark:text-slate-300 truncate">
          File: {{ form.file_ktp_nama }}
        </p>
      </div>

      <!-- KK -->
      <div
        class="rounded-2xl border border-slate-200/80 dark:border-slate-700/80
               bg-slate-50/80 dark:bg-slate-900/80 px-3 py-2.5 flex flex-col gap-1.5"
      >
        <div class="flex items-center justify-between gap-2">
          <div>
            <p class="font-medium text-slate-800 dark:text-slate-100">
              Kartu Keluarga <span class="text-red-500">*</span>
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
              Scan / foto KK yang masih berlaku.
            </p>
          </div>
          <div>
            <input
              id="kk-input"
              type="file"
              class="hidden"
              accept=".pdf,.jpg,.jpeg,.png"
              @change="handleKkChange"
            />
            <label
              for="kk-input"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                     bg-sky-500 text-white text-[11px] font-semibold
                     shadow-sm hover:bg-sky-400 cursor-pointer transition-colors"
            >
              <ArrowUpTrayIcon class="w-4 h-4" />
              <span>Pilih file</span>
            </label>
          </div>
        </div>
        <p v-if="form.file_kk_nama" class="text-[10px] text-slate-600 dark:text-slate-300 truncate">
          File: {{ form.file_kk_nama }}
        </p>
      </div>

      <!-- Rapor -->
      <div
        class="rounded-2xl border border-slate-200/80 dark:border-slate-700/80
               bg-slate-50/80 dark:bg-slate-900/80 px-3 py-2.5 flex flex-col gap-1.5"
      >
        <div class="flex items-center justify-between gap-2">
          <div>
            <p class="font-medium text-slate-800 dark:text-slate-100">
              Scan Rapor Kelas X & XI <span class="text-red-500">*</span>
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
              Gabungan dalam satu file PDF atau beberapa file JPG/PNG.
            </p>
          </div>
          <div>
            <input
              id="rapor-input"
              type="file"
              class="hidden"
              accept=".pdf,.jpg,.jpeg,.png"
              multiple
              @change="handleRaporChange"
            />
            <label
              for="rapor-input"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                     bg-sky-500 text-white text-[11px] font-semibold
                     shadow-sm hover:bg-sky-400 cursor-pointer transition-colors"
            >
              <ArrowUpTrayIcon class="w-4 h-4" />
              <span>Pilih file</span>
            </label>
          </div>
        </div>
        <p v-if="form.file_rapor_nama" class="text-[10px] text-slate-600 dark:text-slate-300 truncate">
          File: {{ form.file_rapor_nama }}
        </p>
      </div>

      <p class="text-[10px] text-slate-500 dark:text-slate-400">
        Format: PDF/JPG/PNG. Pastikan berkas terbaca dengan jelas sebelum dikirim.
      </p>
    </div>
  </div>
</template>
