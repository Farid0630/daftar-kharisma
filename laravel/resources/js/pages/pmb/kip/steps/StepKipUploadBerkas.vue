<!-- resources/js/pages/pmb/kip/steps/StepKipUploadBerkas.vue -->
<script setup>
import { inject, ref } from 'vue'
import { CloudArrowUpIcon } from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

if (!('kip_ktp_file' in form)) form.kip_ktp_file = null
if (!('kip_ktp_name' in form)) form.kip_ktp_name = ''
if (!('kip_kk_file' in form)) form.kip_kk_file = null
if (!('kip_kk_name' in form)) form.kip_kk_name = ''

const errKtp = ref('')
const errKk = ref('')

const validateFile = (file) => {
  if (!file) return 'File tidak ditemukan.'
  const maxMB = 5
  if (file.size > maxMB * 1024 * 1024) return `Ukuran file maksimal ${maxMB} MB.`
  const okTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf']
  if (!okTypes.includes(file.type)) return 'Format harus JPG/PNG/PDF.'
  return ''
}

const handleFile = (event, kind) => {
  const input = event.target
  const file = input.files?.[0] || null

  if (kind === 'ktp') {
    errKtp.value = ''
    if (!file) {
      form.kip_ktp_file = null
      form.kip_ktp_name = ''
      return
    }
    const msg = validateFile(file)
    if (msg) {
      errKtp.value = msg
      input.value = ''
      form.kip_ktp_file = null
      form.kip_ktp_name = ''
      return
    }
    form.kip_ktp_file = file
    form.kip_ktp_name = file.name
  }

  if (kind === 'kk') {
    errKk.value = ''
    if (!file) {
      form.kip_kk_file = null
      form.kip_kk_name = ''
      return
    }
    const msg = validateFile(file)
    if (msg) {
      errKk.value = msg
      input.value = ''
      form.kip_kk_file = null
      form.kip_kk_name = ''
      return
    }
    form.kip_kk_file = file
    form.kip_kk_name = file.name
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <div
        class="h-8 w-8 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300"
      >
        <CloudArrowUpIcon class="w-4 h-4" />
      </div>
      <h2 class="text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200">
        Upload Berkas KIP
      </h2>
    </div>

    <p class="text-xs text-slate-600 dark:text-slate-300">
      Upload berkas wajib berikut: <strong>KTP</strong> dan <strong>Kartu Keluarga (KK)</strong>.
      Format: JPG/PNG/PDF. Maks 5MB.
    </p>

    <div class="space-y-3">
      <!-- KTP -->
      <div
        class="rounded-xl border border-slate-200/80 dark:border-slate-700
               bg-slate-50/80 dark:bg-slate-900/60 px-3 py-2.5 flex flex-col gap-1.5"
      >
        <label class="text-xs font-medium text-slate-700 dark:text-slate-200">
          a. Upload KTP <span class="text-red-500">*</span>
        </label>
        <input
          type="file"
          accept=".jpg,.jpeg,.png,.pdf"
          @change="(e) => handleFile(e, 'ktp')"
          class="text-[11px] text-slate-700 dark:text-slate-100"
        />
        <p v-if="form.kip_ktp_name" class="text-[11px] text-sky-500">
          File: {{ form.kip_ktp_name }}
        </p>
        <p v-if="errKtp" class="text-[11px] text-rose-500">
          {{ errKtp }}
        </p>
      </div>

      <!-- KK -->
      <div
        class="rounded-xl border border-slate-200/80 dark:border-slate-700
               bg-slate-50/80 dark:bg-slate-900/60 px-3 py-2.5 flex flex-col gap-1.5"
      >
        <label class="text-xs font-medium text-slate-700 dark:text-slate-200">
          b. Upload Kartu Keluarga (KK) <span class="text-red-500">*</span>
        </label>
        <input
          type="file"
          accept=".jpg,.jpeg,.png,.pdf"
          @change="(e) => handleFile(e, 'kk')"
          class="text-[11px] text-slate-700 dark:text-slate-100"
        />
        <p v-if="form.kip_kk_name" class="text-[11px] text-sky-500">
          File: {{ form.kip_kk_name }}
        </p>
        <p v-if="errKk" class="text-[11px] text-rose-500">
          {{ errKk }}
        </p>
      </div>
    </div>
  </div>
</template>
