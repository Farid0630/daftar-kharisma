<!-- resources/js/Components/pmb/steps/StepSchoolOrigin.vue -->
<script setup>
import { inject } from 'vue'
import { BuildingLibraryIcon } from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

const props = defineProps({
  years: { type: Array, default: () => [] },
  majors: { type: Array, default: () => [] },
})

const years = props.years
const majors = props.majors
</script>

<template>
  <div class="space-y-5">
    <!-- HEADER SECTION -->
    <div class="flex items-center gap-3">
      <div
        class="h-9 w-9 flex items-center justify-center rounded-2xl
               bg-gradient-to-br from-sky-500 via-sky-500 to-cyan-400
               text-white
               shadow-[0_10px_26px_rgba(56,189,248,0.45)]
               dark:shadow-[0_10px_30px_rgba(56,189,248,0.75)]"
      >
        <BuildingLibraryIcon class="w-4 h-4" />
      </div>
      <div>
        <h2 class="text-sm font-semibold tracking-wide text-slate-900 dark:text-slate-50">
          Data Sekolah Asal
        </h2>
        <p class="text-[11px] text-slate-600 dark:text-slate-400">
          Tulis data sekolah sesuai ijazah atau data resmi sekolah.
          Kolom bertanda <span class="text-red-500 dark:text-red-400">*</span> wajib diisi.
        </p>
      </div>
    </div>

    <!-- FORM GRID -->
    <div class="grid md:grid-cols-2 gap-4 md:gap-6">
      <!-- Nama Sekolah (full width) -->
      <div class="md:col-span-2 space-y-1">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
          Nama Sekolah <span class="text-red-500 dark:text-red-400">*</span>
        </label>
        <input
          v-model="form.nama_sekolah"
          type="text"
          autocomplete="organization"
          class="w-full rounded-2xl border
                 border-slate-300/80 dark:border-slate-600/80
                 bg-white/95 dark:bg-slate-900/80
                 px-4 py-2.5 text-sm
                 text-slate-900 dark:text-slate-50
                 placeholder-slate-400 dark:placeholder-slate-400/70
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 transition-shadow"
          placeholder="Tulis nama SMA/SMK/MA Anda"
          required
        />
      </div>

      <!-- Jenis Sekolah -->
      <div class="space-y-1">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
          Jenis Sekolah <span class="text-red-500 dark:text-red-400">*</span>
        </label>
        <select
          v-model="form.jenis_sekolah"
          class="w-full rounded-2xl border
                 border-slate-300/80 dark:border-slate-600/80
                 bg-white/95 dark:bg-slate-900/80
                 px-4 py-2.5 text-sm
                 text-slate-900 dark:text-slate-50
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 transition-shadow"
          required
        >
          <option value="">-- Pilih Jenis Sekolah --</option>
          <option value="SMA">SMA</option>
          <option value="SMK">SMK</option>
          <option value="MA">MA</option>
        </select>
      </div>

      <!-- Jurusan Sekolah -->
      <div class="space-y-1">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
          Jurusan Sekolah <span class="text-red-500 dark:text-red-400">*</span>
        </label>
        <select
          v-model="form.jurusan"
          class="w-full rounded-2xl border
                 border-slate-300/80 dark:border-slate-600/80
                 bg-white/95 dark:bg-slate-900/80
                 px-4 py-2.5 text-sm
                 text-slate-900 dark:text-slate-50
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 transition-shadow"
          required
        >
          <option value="">-- Pilih Jurusan Sekolah --</option>
          <option v-for="m in majors" :key="m" :value="m">
            {{ m }}
          </option>
        </select>
      </div>

      <!-- Kabupaten / Kota Sekolah -->
      <div class="space-y-1">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
          Kabupaten / Kota Sekolah <span class="text-red-500 dark:text-red-400">*</span>
        </label>
        <input
          v-model="form.kota_sekolah"
          type="text"
          autocomplete="address-level2"
          class="w-full rounded-2xl border
                 border-slate-300/80 dark:border-slate-600/80
                 bg-white/95 dark:bg-slate-900/80
                 px-4 py-2.5 text-sm
                 text-slate-900 dark:text-slate-50
                 placeholder-slate-400 dark:placeholder-slate-400/70
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 transition-shadow"
          placeholder="Contoh: Kota Makassar"
          required
        />
      </div>

      <!-- Tahun Lulus -->
      <div class="space-y-1">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
          Tahun Lulus <span class="text-red-500 dark:text-red-400">*</span>
        </label>
        <select
          v-model="form.tahun_lulus"
          class="w-full rounded-2xl border
                 border-slate-300/80 dark:border-slate-600/80
                 bg-white/95 dark:bg-slate-900/80
                 px-4 py-2.5 text-sm
                 text-slate-900 dark:text-slate-50
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 transition-shadow"
          required
        >
          <option value="">-- Pilih Tahun Lulus --</option>
          <option v-for="y in years" :key="y" :value="y">
            {{ y }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>
