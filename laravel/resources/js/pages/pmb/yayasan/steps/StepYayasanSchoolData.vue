<!-- resources/js/Components/pmb/yayasan/steps/StepYayasanSchoolData.vue -->
<script setup>
import { inject } from 'vue'
import { BuildingLibraryIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  provinces: { type: Array, default: () => [] },
  schoolTypes: { type: Array, default: () => [] },
  majors: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
})

const form = inject('pmbForm')
</script>

<template>
  <div class="space-y-4">
    <!-- Judul section -->
    <div class="flex items-center gap-2">
      <div
        class="h-8 w-8 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600
               dark:bg-sky-500/20 dark:text-sky-300"
      >
        <BuildingLibraryIcon class="w-4 h-4" />
      </div>
      <h2
        class="text-sm font-semibold tracking-wide
               text-slate-700 dark:text-slate-200"
      >
        Data Sekolah Asal Calon Penerima Beasiswa
      </h2>
    </div>

    <!-- GRID FORM -->
    <div class="grid md:grid-cols-2 gap-4 md:gap-6">
      <!-- Provinsi Sekolah -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Provinsi Sekolah <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.provinsi_sekolah"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          required
        >
          <option value="">-- Pilih Provinsi --</option>
          <option v-for="prov in props.provinces" :key="prov" :value="prov">
            {{ prov }}
          </option>
        </select>
      </div>

      <!-- Jenis Sekolah -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Jenis Sekolah <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.jenis_sekolah"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          required
        >
          <option value="">-- Pilih jenis sekolah --</option>
          <option v-for="type in props.schoolTypes" :key="type" :value="type">
            {{ type }}
          </option>
        </select>
      </div>

      <!-- Nama Sekolah -->
      <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Nama Sekolah <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.nama_sekolah"
          type="text"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-slate-300
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Contoh: SMA Negeri 1 Makassar"
          required
        />
      </div>

      <!-- Jurusan Sekolah -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Jurusan Sekolah <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.jurusan_sekolah"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          required
        >
          <option value="">-- Pilih jurusan --</option>
          <option v-for="jur in props.majors" :key="jur" :value="jur">
            {{ jur }}
          </option>
        </select>
      </div>

      <!-- Kabupaten / Kota Sekolah -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Kabupaten / Kota Sekolah <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.kabkota_sekolah"
          type="text"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-slate-300
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Contoh: Kota Makassar / Kab. Gowa"
          required
        />
      </div>

      <!-- Tahun Lulus -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Tahun Lulus <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.tahun_lulus"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          required
        >
          <option value="">-- Pilih tahun lulus --</option>
          <option v-for="year in props.years" :key="year" :value="year">
            {{ year }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>
