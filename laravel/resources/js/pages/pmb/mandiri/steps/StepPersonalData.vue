<!-- resources/js/Components/pmb/steps/StepPersonalData.vue -->
<script setup>
import { inject, ref, computed, watch } from "vue";
import {
  UserGroupIcon,
  CalendarDaysIcon,
  AcademicCapIcon,
  ChevronDownIcon,
} from "@heroicons/vue/24/outline";

const form = inject("pmbForm");

// pastikan field file ada
if (!('foto_file' in form)) form.foto_file = null;

// === Opsi Prodi + Deskripsi ===
const programStudiOptions = [
  { label: "S1 Teknik Informatika", desc: "Fokus pada pengembangan perangkat lunak, kecerdasan buatan, dan rekayasa sistem." },
  { label: "S1 Sistem Informasi", desc: "Menggabungkan teknologi informasi dengan proses bisnis dan manajemen organisasi." },
  { label: "S1 Bisnis Digital", desc: "Belajar strategi bisnis modern, pemasaran digital, dan inovasi berbasis teknologi." },
];

// === Pastikan field ada di state global ===
if (!("program_studi_1" in form)) form.program_studi_1 = "";
if (!("program_studi_2" in form)) form.program_studi_2 = "";
if (!("foto_nama" in form)) form.foto_nama = "";
if (!("foto_preview" in form)) form.foto_preview = "";

// Default pilihan
if (!form.program_studi_1) form.program_studi_1 = programStudiOptions[0].label;
if (!form.program_studi_2) form.program_studi_2 = programStudiOptions[1]?.label || "";

// Prodi 2 tidak boleh sama dengan Prodi 1 → filter opsi
const prodi2Options = computed(() =>
  programStudiOptions.filter((p) => p.label !== form.program_studi_1)
);

const selectedProdi1 = computed(
  () => programStudiOptions.find((p) => p.label === form.program_studi_1) || null
);

const selectedProdi2 = computed(
  () => programStudiOptions.find((p) => p.label === form.program_studi_2) || null
);

watch(
  () => form.program_studi_1,
  () => {
    const allowed = prodi2Options.value.map((p) => p.label);
    if (!allowed.includes(form.program_studi_2)) {
      form.program_studi_2 = allowed[0] || "";
    }
  }
);

// ref untuk input tanggal lahir
const tanggalLahirInput = ref(null);

// handle upload & preview foto + SIMPAN FILE ASLI (penting untuk backend)
const handleFotoChange = (event) => {
  const input = event.target;
  const files = Array.from(input.files || []);

  if (!files.length) {
    form.foto_nama = "";
    form.foto_preview = "";
    form.foto_file = null;
    return;
  }

  const file = files[0];
  form.foto_nama = file.name;
  form.foto_file = file; // ✅ INI YANG DIKIRIM VIA FormData

  const reader = new FileReader();
  reader.onload = (e) => {
    form.foto_preview = e.target.result;
  };
  reader.readAsDataURL(file);
};

// buka date picker ketika klik area input / icon
const openDatePicker = () => {
  if (tanggalLahirInput.value?.showPicker) {
    tanggalLahirInput.value.showPicker();
  } else if (tanggalLahirInput.value) {
    tanggalLahirInput.value.focus();
  }
};
</script>


<template>
    <div class="space-y-4">
        <!-- Judul section -->
        <div class="flex items-center gap-2">
            <div
                class="h-8 w-8 flex items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300"
            >
                <UserGroupIcon class="w-4 h-4" />
            </div>
            <h2
                class="text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200"
            >
                Data Pribadi
            </h2>
        </div>

        <!-- GRID: kiri form, kanan foto -->
        <div class="grid md:grid-cols-3 gap-6">
            <!-- KIRI: FORM UTAMA (2/3 lebar) -->
            <div class="md:col-span-2 space-y-4">
                <!-- Nama lengkap -->
                <div>
                    <label
                        class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1"
                    >
                        Nama Lengkap Anda <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.nama_lengkap"
                        type="text"
                        class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600 bg-white dark:bg-slate-900/80 px-3 py-2.5 text-sm placeholder-slate-400 dark:placeholder-white focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 dark:focus:ring-sky-500"
                        placeholder="Sesuai ijazah"
                        required
                    />
                </div>

                <!-- Jenis Kelamin -->
                <div>
                    <p
                        class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1"
                    >
                        Jenis Kelamin <span class="text-red-500">*</span>
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <!-- Laki-laki -->
                        <label
                            class="cursor-pointer inline-flex items-center px-4 py-2 rounded-full text-xs font-medium border transition-all dark:border-slate-600"
                            :class="
                                form.jenis_kelamin === 'L'
                                    ? 'bg-sky-500 text-white border-sky-500 shadow-sm shadow-sky-500/50 dark:bg-sky-500 dark:border-sky-400'
                                    : 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-200'
                            "
                        >
                            <input
                                type="radio"
                                value="L"
                                v-model="form.jenis_kelamin"
                                class="sr-only"
                            />
                            <span>Laki-laki</span>
                        </label>

                        <!-- Perempuan -->
                        <label
                            class="cursor-pointer inline-flex items-center px-4 py-2 rounded-full text-xs font-medium border transition-all dark:border-slate-600"
                            :class="
                                form.jenis_kelamin === 'P'
                                    ? 'bg-sky-500 text-white border-sky-500 shadow-sm shadow-sky-500/50 dark:bg-sky-500 dark:border-sky-400'
                                    : 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-200'
                            "
                        >
                            <input
                                type="radio"
                                value="P"
                                v-model="form.jenis_kelamin"
                                class="sr-only"
                            />
                            <span>Perempuan</span>
                        </label>
                    </div>
                </div>

                <!-- Tanggal lahir + Tempat lahir -->
                <div class="grid sm:grid-cols-2 gap-4">
                    <!-- Tanggal lahir -->
                    <div>
                        <label
                            class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1"
                        >
                            Tanggal Lahir <span class="text-red-500">*</span>
                        </label>

                        <div class="relative" @click="openDatePicker">
                            <input
                                ref="tanggalLahirInput"
                                v-model="form.tanggal_lahir"
                                type="date"
                                class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600 bg-white dark:bg-slate-900/80 pl-3 pr-9 py-2.5 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 dark:focus:ring-sky-500"
                                required
                            />

                            <button
                                type="button"
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 dark:text-slate-300"
                            >
                                <CalendarDaysIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Tempat Lahir -->
                    <div>
                        <label
                            class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1"
                        >
                            Tempat Lahir <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.tempat_lahir"
                            type="text"
                            class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600 bg-white dark:bg-slate-900/80 px-3 py-2.5 text-sm placeholder-slate-400 dark:placeholder-white focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 dark:focus:ring-sky-500"
                            placeholder="Kota / Kabupaten"
                            required
                        />
                    </div>
                </div>

                <!-- PROGRAM STUDI: 2 PILIHAN -->
                <div class="grid sm:grid-cols-2 gap-4">
                    <!-- Prodi 1 -->
                    <div>
                        <label
                            class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1"
                        >
                            Program Studi Pilihan 1
                            <span class="text-red-500">*</span>
                        </label>

                        <div
                            class="relative rounded-xl border border-slate-300/80 dark:border-slate-600 bg-white dark:bg-slate-900/80 text-sm"
                        >
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3 text-sky-500/80 dark:text-sky-400"
                            >
                                <AcademicCapIcon class="w-4 h-4" />
                            </div>

                            <select
                                v-model="form.program_studi_1"
                                class="w-full appearance-none bg-transparent pl-9 pr-8 py-2.5 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 dark:focus:ring-sky-500 rounded-xl"
                                required
                            >
                                <option
                                    v-for="prodi in programStudiOptions"
                                    :key="prodi.label"
                                    :value="prodi.label"
                                >
                                    {{ prodi.label }}
                                </option>
                            </select>

                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 dark:text-slate-300"
                            >
                                <ChevronDownIcon class="w-4 h-4" />
                            </div>
                        </div>

                        <p
                            v-if="selectedProdi1"
                            class="mt-1 text-[10px] text-slate-500 dark:text-slate-400"
                        >
                            {{ selectedProdi1.desc }}
                        </p>
                    </div>

                    <!-- Prodi 2 -->
                    <div>
                        <label
                            class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1"
                        >
                            Program Studi Pilihan 2
                            <span class="text-red-500">*</span>
                        </label>

                        <div
                            class="relative rounded-xl border border-slate-300/80 dark:border-slate-600 bg-white dark:bg-slate-900/80 text-sm"
                        >
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3 text-sky-500/80 dark:text-sky-400"
                            >
                                <AcademicCapIcon class="w-4 h-4" />
                            </div>

                            <select
                                v-model="form.program_studi_2"
                                class="w-full appearance-none bg-transparent pl-9 pr-8 py-2.5 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 dark:focus:ring-sky-500 rounded-xl"
                                required
                            >
                                <option
                                    v-for="prodi in prodi2Options"
                                    :key="prodi.label"
                                    :value="prodi.label"
                                >
                                    {{ prodi.label }}
                                </option>
                            </select>

                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 dark:text-slate-300"
                            >
                                <ChevronDownIcon class="w-4 h-4" />
                            </div>
                        </div>

                        <p
                            v-if="selectedProdi2"
                            class="mt-1 text-[10px] text-slate-500 dark:text-slate-400"
                        >
                            {{ selectedProdi2.desc }}
                        </p>

                        <p
                            v-if="
                                form.program_studi_1 &&
                                form.program_studi_2 &&
                                form.program_studi_1 === form.program_studi_2
                            "
                            class="mt-1 text-[11px] text-rose-500"
                        >
                            Pilihan 2 tidak boleh sama dengan pilihan 1.
                        </p>
                    </div>
                </div>
            </div>

            <!-- KANAN: FOTO -->
            <div class="space-y-3">
                <label
                    class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1"
                >
                    Foto (opsional)
                </label>

                <div
                    class="w-28 h-28 md:w-32 md:h-32 rounded-xl border border-slate-300/80 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 overflow-hidden flex items-center justify-center mx-auto md:mx-0"
                >
                    <template v-if="form.foto_preview">
                        <img
                            :src="form.foto_preview"
                            alt="Foto calon mahasiswa"
                            class="w-full h-full object-cover"
                        />
                    </template>
                    <template v-else>
                        <span
                            class="text-[10px] text-slate-400 text-center px-2"
                        >
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
                        class="inline-flex items-center justify-center px-3 py-1.5 rounded-full bg-sky-500 text-white text-[11px] font-semibold shadow-sm hover:bg-sky-400 cursor-pointer transition-colors"
                    >
                        Pilih Foto
                    </label>

                    <p class="text-[10px] text-slate-500 dark:text-slate-400">
                        Disarankan JPG/PNG, maks ±1–2 MB.
                    </p>

                    <p
                        v-if="form.foto_nama"
                        class="text-[10px] text-slate-600 dark:text-slate-300 truncate"
                    >
                        File: {{ form.foto_nama }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* hilangkan icon native date agar tidak bentrok */
:deep(input[type="date"]::-webkit-calendar-picker-indicator) {
    opacity: 0;
    display: none;
}

:deep(input[type="date"]::-moz-focus-inner) {
    border: 0;
}
</style>
