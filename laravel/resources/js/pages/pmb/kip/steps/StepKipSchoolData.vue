<!-- resources/js/pages/pmb/kip/steps/StepKipSchoolData.vue -->
<script setup>
import { inject } from 'vue'
import { BuildingLibraryIcon } from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

const jenisSekolahOptions = ['SMA', 'SMK', 'MA', 'Paket C', 'Lainnya']

const jurusanOptions = [
  'IPA',
  'IPS',
  'Bahasa',
  'Agama',
  'TKJ (Teknik Komputer & Jaringan)',
  'RPL (Rekayasa Perangkat Lunak)',
  'Multimedia',
  'Akuntansi',
  'OTKP / Perkantoran',
  'Teknik Mesin',
  'Teknik Kendaraan Ringan',
  'Teknik Listrik / Elektro',
  'Pariwisata / Perhotelan',
  'Lainnya',
]

// Tahun lulus: 2026, 2025, 2024
const tahunOptions = [2026, 2025, 2024]
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <div
        class="h-8 w-8 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600
               dark:bg-sky-500/20 dark:text-sky-300"
      >
        <BuildingLibraryIcon class="w-4 h-4" />
      </div>
      <h2 class="text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200">
        Data Sekolah Asal
      </h2>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Nama Sekolah <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.nama_sekolah"
          type="text"
          required
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-white
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Tulis nama SMA/SMK/MA Anda"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          NPSN (Nomor Pokok Sekolah Nasional) <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.npsn_sekolah"
          type="text"
          required
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-white
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Nomor Pokok Nasional"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          NIS Nasional (NISN) <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.nisn"
          type="text"
          required
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-white
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Nomor Induk Siswa Nasional"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Jenis Sekolah <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.jenis_sekolah"
          required
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 text-slate-900 dark:text-slate-100
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
        >
          <option value="">-- Pilih Jenis Sekolah --</option>
          <option v-for="jenis in jenisSekolahOptions" :key="jenis" :value="jenis">
            {{ jenis }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Jurusan Sekolah <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.jurusan_sekolah"
          required
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 text-slate-900 dark:text-slate-100
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
        >
          <option value="">-- Pilih Jurusan Sekolah --</option>
          <option v-for="jur in jurusanOptions" :key="jur" :value="jur">
            {{ jur }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Kabupaten / Kota Sekolah <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.kabkota_sekolah"
          type="text"
          required
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-white
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Contoh: Kota Makassar"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Tahun Lulus <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.tahun_lulus"
          required
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 text-slate-900 dark:text-slate-100
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
        >
          <option value="">-- Pilih Tahun Lulus --</option>
          <option v-for="tahun in tahunOptions" :key="tahun" :value="tahun">
            {{ tahun }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>
