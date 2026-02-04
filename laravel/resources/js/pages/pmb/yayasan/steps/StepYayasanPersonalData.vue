<!-- resources/js/Pages/pmb/yayasan/steps/StepYayasanPersonalData.vue -->
<script setup>
import { inject, ref } from 'vue'
import { UserCircleIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

// ===== SAFETY DEFAULT (agar tidak undefined) =====
if (!('foto_nama' in form)) form.foto_nama = ''
if (!('foto_preview' in form)) form.foto_preview = ''
if (!('foto_file' in form)) form.foto_file = null

// ref untuk input tanggal lahir
const tanggalLahirInput = ref(null)

// handle upload & preview foto (✅ simpan File object)
const handleFotoChange = (event) => {
  const input = event.target
  const files = Array.from(input.files || [])

  if (!files.length) {
    form.foto_nama = ''
    form.foto_preview = ''
    form.foto_file = null
    return
  }

  const file = files[0]

  // simpan object file + nama
  form.foto_nama = file.name
  form.foto_file = file

  // preview
  const reader = new FileReader()
  reader.onload = (e) => {
    form.foto_preview = e.target?.result || ''
  }
  reader.readAsDataURL(file)
}

// buka date picker ketika klik area input / icon
const openDatePicker = () => {
  if (tanggalLahirInput.value?.showPicker) {
    tanggalLahirInput.value.showPicker()
  } else if (tanggalLahirInput.value) {
    tanggalLahirInput.value.focus()
  }
}
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
        <UserCircleIcon class="w-4 h-4" />
      </div>
      <h2
        class="text-sm font-semibold tracking-wide
               text-slate-700 dark:text-slate-200"
      >
        Data Diri Calon Penerima Beasiswa
      </h2>
    </div>

    <!-- GRID: kiri form, kanan foto -->
    <div class="grid md:grid-cols-3 gap-6">
      <!-- KIRI: FORM UTAMA (2/3 lebar) -->
      <div class="md:col-span-2 space-y-4">
        <!-- Nama lengkap -->
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
            Nama Lengkap <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.nama_lengkap"
            type="text"
            class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                   bg-white dark:bg-slate-900/80
                   px-3 py-2.5 text-sm
                   placeholder-slate-400 dark:placeholder-slate-300
                   focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                   dark:focus:ring-sky-500"
            placeholder="Sesuai KTP / KK"
            required
          />
        </div>

        <!-- Jenis Kelamin -->
        <div>
          <p class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
            Jenis Kelamin <span class="text-red-500">*</span>
          </p>

          <div class="flex flex-wrap gap-3">
            <label
              class="cursor-pointer inline-flex items-center px-4 py-2 rounded-full
                     text-xs font-medium border transition-all
                     dark:border-slate-600"
              :class="form.jenis_kelamin === 'L'
                ? 'bg-sky-500 text-white border-sky-500 shadow-sm shadow-sky-500/50 dark:bg-sky-500 dark:border-sky-400'
                : 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-200'"
            >
              <input type="radio" value="L" v-model="form.jenis_kelamin" class="sr-only" />
              <span>Laki-laki</span>
            </label>

            <label
              class="cursor-pointer inline-flex items-center px-4 py-2 rounded-full
                     text-xs font-medium border transition-all
                     dark:border-slate-600"
              :class="form.jenis_kelamin === 'P'
                ? 'bg-sky-500 text-white border-sky-500 shadow-sky-500/50 dark:bg-sky-500 dark:border-sky-400'
                : 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-200'"
            >
              <input type="radio" value="P" v-model="form.jenis_kelamin" class="sr-only" />
              <span>Perempuan</span>
            </label>
          </div>
        </div>

        <!-- Tanggal lahir + Tempat lahir -->
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
              Tanggal Lahir <span class="text-red-500">*</span>
            </label>

            <div class="relative" @click="openDatePicker">
              <input
                ref="tanggalLahirInput"
                v-model="form.tanggal_lahir"
                type="date"
                class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                       bg-white dark:bg-slate-900/80
                       pl-3 pr-9 py-2.5 text-sm
                       text-slate-900 dark:text-slate-100
                       focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                       dark:focus:ring-sky-500"
                required
              />

              <button
                type="button"
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3
                       text-slate-400 dark:text-slate-300"
              >
                <CalendarDaysIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
              Tempat Lahir <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.tempat_lahir"
              type="text"
              class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                     bg-white dark:bg-slate-900/80
                     px-3 py-2.5 text-sm
                     placeholder-slate-400 dark:placeholder-slate-300
                     focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                     dark:focus:ring-sky-500"
              placeholder="Kota / Kabupaten"
              required
            />
          </div>
        </div>

        <!-- Kewarganegaraan -->
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
            Kewarganegaraan <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.kewarganegaraan"
            class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                   bg-white dark:bg-slate-900/80
                   px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                   dark:focus:ring-sky-500"
            required
          >
            <option value="">-- Pilih kewarganegaraan --</option>
            <option value="WNI">Warga Negara Indonesia (WNI)</option>
            <option value="WNA">Warga Negara Asing (WNA)</option>
          </select>
        </div>
      </div>

      <!-- KANAN: FOTO -->
      <div class="space-y-3">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Foto (opsional)
        </label>

        <div
          class="w-28 h-28 md:w-32 md:h-32 rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-slate-100 dark:bg-slate-800
                 overflow-hidden flex items-center justify-center
                 mx-auto md:mx-0"
        >
          <template v-if="form.foto_preview">
            <img :src="form.foto_preview" alt="Foto calon penerima beasiswa" class="w-full h-full object-cover" />
          </template>
          <template v-else>
            <span class="text-[10px] text-slate-400 text-center px-2">
              Belum ada foto
            </span>
          </template>
        </div>

        <div class="space-y-1">
          <input
            id="foto-input"
            type="file"
            accept="image/*"
            @change="handleFotoChange"
            class="hidden"
          />
          <label
            for="foto-input"
            class="inline-flex items-center justify-center px-3 py-1.5
                   rounded-full bg-sky-500 text-white text-[11px] font-semibold
                   shadow-sm hover:bg-sky-400 cursor-pointer transition-colors"
          >
            Pilih Foto
          </label>

          <p class="text-[10px] text-slate-500 dark:text-slate-400">
            Disarankan JPG/PNG, maks ±1–2 MB.
          </p>

          <p v-if="form.foto_nama" class="text-[10px] text-slate-600 dark:text-slate-300 truncate">
            File: {{ form.foto_nama }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(input[type='date']::-webkit-calendar-picker-indicator) {
  opacity: 0;
  display: none;
}

:deep(input[type='date']::-moz-focus-inner) {
  border: 0;
}
</style>
