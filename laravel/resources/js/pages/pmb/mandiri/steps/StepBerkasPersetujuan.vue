<!-- resources/js/Components/pmb/steps/StepBerkasPersetujuan.vue -->
<script setup>
import { inject, ref } from 'vue'
import {
  ArrowUpTrayIcon,
  DocumentTextIcon,
  SparklesIcon,
  ShieldCheckIcon,
  CheckIcon,
} from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

// pastikan field file ada di form global
if (!('berkas_ktp_file' in form)) form.berkas_ktp_file = null
if (!('berkas_kk_file' in form)) form.berkas_kk_file = null
if (!('berkas_rapor_files' in form)) form.berkas_rapor_files = []
if (!('berkas_terunggah' in form)) form.berkas_terunggah = false

// nama file yang ditampilkan (untuk UI)
const fileKtpName = ref('')
const fileKkName = ref('')
const fileRaporName = ref('')

// cek apakah semua berkas wajib sudah terisi
const updateStatusBerkas = () => {
  const raporOk = Array.isArray(form.berkas_rapor_files) && form.berkas_rapor_files.length > 0
  form.berkas_terunggah = !!form.berkas_ktp_file && !!form.berkas_kk_file && raporOk
}

const handleFileChange = (jenis, event) => {
  const input = event.target
  const files = Array.from(input.files || [])

  // KTP
  if (jenis === 'ktp') {
    const f = files[0] || null
    form.berkas_ktp_file = f
    fileKtpName.value = f ? f.name : ''
  }

  // KK
  if (jenis === 'kk') {
    const f = files[0] || null
    form.berkas_kk_file = f
    fileKkName.value = f ? f.name : ''
  }

  // Rapor (multiple)
  if (jenis === 'rapor') {
    form.berkas_rapor_files = files
    if (!files.length) {
      fileRaporName.value = ''
    } else if (files.length === 1) {
      fileRaporName.value = files[0].name
    } else {
      fileRaporName.value = `${files.length} file dipilih`
    }
  }

  updateStatusBerkas()
}
</script>


<template>
  <div class="space-y-6 text-xs text-slate-700 dark:text-slate-200">
    <!-- ================= BAGIAN 1: UPLOAD BERKAS ================= -->
    <div class="space-y-4">
      <div class="flex items-center gap-2">
        <div
          class="h-8 w-8 flex items-center justify-center rounded-xl
                 bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300"
        >
          <ArrowUpTrayIcon class="w-4 h-4" />
        </div>
        <h2 class="text-sm font-semibold tracking-wide">
          Upload Berkas Pendaftaran
        </h2>
      </div>

      <p class="text-xs text-slate-600 dark:text-slate-300">
        Unggah scan/foto dokumen penting (format JPG/PNG/PDF, ukuran wajar)
        untuk melengkapi pendaftaran.
      </p>

      <!-- Card berkas wajib -->
      <div
        class="border border-dashed border-slate-300 dark:border-slate-700
               rounded-2xl p-4 bg-slate-50/70 dark:bg-slate-900/60"
      >
        <div class="flex items-center justify-between gap-2 mb-3">
          <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-200">
            Berkas wajib bertanda <span class="text-red-500">*</span>
          </p>
          <DocumentTextIcon class="w-4 h-4 text-slate-400" />
        </div>

        <!-- Grid 3 kolom: tiap item = card upload -->
        <div class="grid md:grid-cols-3 gap-4">
          <!-- KTP -->
          <label
            for="file-ktp"
            class="group relative flex flex-col justify-between gap-3 rounded-2xl
                   border border-slate-200/80 dark:border-slate-800
                   bg-white/95 dark:bg-slate-950/70
                   px-3 py-3 cursor-pointer
                   shadow-sm shadow-slate-900/5
                   hover:border-sky-400/80 hover:shadow-[0_12px_35px_rgba(56,189,248,0.25)]
                   hover:bg-sky-50/70 dark:hover:bg-slate-900
                   transition-all"
          >
            <input
              id="file-ktp"
              type="file"
              accept="image/*,application/pdf"
              class="hidden"
              @change="(e) => handleFileChange('ktp', e)"
            />

            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2">
                <div
                  class="h-8 w-8 flex items-center justify-center rounded-xl
                         bg-sky-500/10 text-sky-500
                         dark:bg-sky-500/15 dark:text-sky-300"
                >
                  <ArrowUpTrayIcon class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-[11px] font-semibold uppercase tracking-wide">
                    a. KTP <span class="text-red-500">*</span>
                  </p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">
                    JPG/PNG/PDF, maks ±2 MB
                  </p>
                </div>
              </div>

              <span
                class="rounded-full px-2 py-0.5 text-[9px] font-semibold
                       bg-rose-500/10 text-rose-500 border border-rose-400/40
                       dark:bg-rose-500/15 dark:text-rose-300"
              >
                WAJIB
              </span>
            </div>

            <div class="flex items-center justify-between gap-2">
              <p
                class="flex-1 text-[10px] text-slate-500 dark:text-slate-300 truncate"
              >
                {{ fileKtpName || 'Belum ada file yang dipilih.' }}
              </p>
              <span
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1
                       text-[10px] font-semibold
                       bg-sky-500/10 text-sky-600
                       group-hover:bg-sky-500 group-hover:text-white
                       dark:bg-sky-500/15 dark:text-sky-300
                       dark:group-hover:bg-sky-400/90 dark:group-hover:text-slate-950
                       transition-colors"
              >
                <ArrowUpTrayIcon class="w-3 h-3" />
                <span>Unggah</span>
              </span>
            </div>
          </label>

          <!-- KK -->
          <label
            for="file-kk"
            class="group relative flex flex-col justify-between gap-3 rounded-2xl
                   border border-slate-200/80 dark:border-slate-800
                   bg-white/95 dark:bg-slate-950/70
                   px-3 py-3 cursor-pointer
                   shadow-sm shadow-slate-900/5
                   hover:border-sky-400/80 hover:shadow-[0_12px_35px_rgba(56,189,248,0.25)]
                   hover:bg-sky-50/70 dark:hover:bg-slate-900
                   transition-all"
          >
            <input
              id="file-kk"
              type="file"
              accept="image/*,application/pdf"
              class="hidden"
              @change="(e) => handleFileChange('kk', e)"
            />

            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2">
                <div
                  class="h-8 w-8 flex items-center justify-center rounded-xl
                         bg-sky-500/10 text-sky-500
                         dark:bg-sky-500/15 dark:text-sky-300"
                >
                  <ArrowUpTrayIcon class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-[11px] font-semibold uppercase tracking-wide">
                    b. Kartu Keluarga <span class="text-red-500">*</span>
                  </p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">
                    JPG/PNG/PDF, maks ±2 MB
                  </p>
                </div>
              </div>

              <span
                class="rounded-full px-2 py-0.5 text-[9px] font-semibold
                       bg-rose-500/10 text-rose-500 border border-rose-400/40
                       dark:bg-rose-500/15 dark:text-rose-300"
              >
                WAJIB
              </span>
            </div>

            <div class="flex items-center justify-between gap-2">
              <p
                class="flex-1 text-[10px] text-slate-500 dark:text-slate-300 truncate"
              >
                {{ fileKkName || 'Belum ada file yang dipilih.' }}
              </p>
              <span
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1
                       text-[10px] font-semibold
                       bg-sky-500/10 text-sky-600
                       group-hover:bg-sky-500 group-hover:text-white
                       dark:bg-sky-500/15 dark:text-sky-300
                       dark:group-hover:bg-sky-400/90 dark:group-hover:text-slate-950
                       transition-colors"
              >
                <ArrowUpTrayIcon class="w-3 h-3" />
                <span>Unggah</span>
              </span>
            </div>
          </label>

          <!-- Rapor -->
          <label
            for="file-rapor"
            class="group relative flex flex-col justify-between gap-3 rounded-2xl
                   border border-slate-200/80 dark:border-slate-800
                   bg-white/95 dark:bg-slate-950/70
                   px-3 py-3 cursor-pointer
                   shadow-sm shadow-slate-900/5
                   hover:border-sky-400/80 hover:shadow-[0_12px_35px_rgba(56,189,248,0.25)]
                   hover:bg-sky-50/70 dark:hover:bg-slate-900
                   transition-all"
          >
            <input
              id="file-rapor"
              type="file"
              accept="image/*,application/pdf"
              multiple
              class="hidden"
              @change="(e) => handleFileChange('rapor', e)"
            />

            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2">
                <div
                  class="h-8 w-8 flex items-center justify-center rounded-xl
                         bg-sky-500/10 text-sky-500
                         dark:bg-sky-500/15 dark:text-sky-300"
                >
                  <ArrowUpTrayIcon class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-[11px] font-semibold uppercase tracking-wide">
                    c. Scan Rapor Kelas X & XI <span class="text-red-500">*</span>
                  </p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">
                    Bisa beberapa file sekaligus
                  </p>
                </div>
              </div>

              <span
                class="rounded-full px-2 py-0.5 text-[9px] font-semibold
                       bg-rose-500/10 text-rose-500 border border-rose-400/40
                       dark:bg-rose-500/15 dark:text-rose-300"
              >
                WAJIB
              </span>
            </div>

            <div class="flex items-center justify-between gap-2">
              <p
                class="flex-1 text-[10px] text-slate-500 dark:text-slate-300 truncate"
              >
                {{ fileRaporName || 'Belum ada file yang dipilih.' }}
              </p>
              <span
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1
                       text-[10px] font-semibold
                       bg-sky-500/10 text-sky-600
                       group-hover:bg-sky-500 group-hover:text-white
                       dark:bg-sky-500/15 dark:text-sky-300
                       dark:group-hover:bg-sky-400/90 dark:group-hover:text-slate-950
                       transition-colors"
              >
                <ArrowUpTrayIcon class="w-3 h-3" />
                <span>Unggah</span>
              </span>
            </div>
          </label>
        </div>

        <p
          v-if="form.berkas_terunggah"
          class="mt-3 text-[11px] text-emerald-500"
        >
          Semua berkas wajib sudah ditandai sebagai <strong>terunggah</strong>
          (simulasi).
        </p>
      </div>
    </div>

 

    <!-- ================= BAGIAN 2: JALUR PRESTASI ================= -->
   

    <div
      class="h-px bg-gradient-to-r from-transparent via-slate-300/70 to-transparent
             dark:via-slate-700/70"
    ></div>

    <!-- ================= BAGIAN 3: PERSETUJUAN ================= -->
    <div class="space-y-3">
      <div class="flex items-center gap-2">
        <div
          class="h-8 w-8 flex items-center justify-center rounded-xl
                 bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300"
        >
          <ShieldCheckIcon class="w-4 h-4" />
        </div>
        <h2 class="text-sm font-semibold tracking-wide">
          Persetujuan
        </h2>
      </div>

      <p class="text-xs">
        Silakan membaca
        <a
          href="#"
          class="text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300 underline underline-offset-2"
        >
          Syarat dan Ketentuan
        </a>
        terlebih dahulu.
      </p>

      <!-- setuju syarat -->
      <label
        class="group flex items-center gap-3 justify-between rounded-xl border
               border-slate-200/80 dark:border-slate-800
               bg-white/90 dark:bg-slate-900/80
               px-4 py-3 cursor-pointer transition-colors
               hover:border-sky-400/70 hover:bg-sky-50/60
               dark:hover:border-sky-500/70 dark:hover:bg-slate-900"
      >
        <p class="flex-1 leading-relaxed">
          Saya telah membaca dan menyetujui semua persyaratan dan ketentuan yang
          berlaku sebagai calon Mahasiswa Baru STMIK KHARISMA.
        </p>

        <input
          type="checkbox"
          v-model="form.setuju_syarat"
          class="switch-input"
        />

        <span
          class="switch-track"
          :class="{ 'switch-track--on': form.setuju_syarat }"
        >
          <span class="switch-thumb">
            <CheckIcon />
          </span>
        </span>
      </label>

      <!-- setuju kebenaran data -->
      <label
        class="group flex items-center gap-3 justify-between rounded-xl border
               border-slate-200/80 dark:border-slate-800
               bg-white/90 dark:bg-slate-900/80
               px-4 py-3 cursor-pointer transition-colors
               hover:border-sky-400/70 hover:bg-sky-50/60
               dark:hover:border-sky-500/70 dark:hover:bg-slate-900"
      >
        <p class="flex-1 leading-relaxed">
          Semua data yang saya berikan dalam formulir isian ini adalah benar
          sesuai dengan keadaan yang sebenarnya.
        </p>

        <input
          type="checkbox"
          v-model="form.setuju_kebenaran_data"
          class="switch-input"
        />

        <span
          class="switch-track"
          :class="{ 'switch-track--on': form.setuju_kebenaran_data }"
        >
          <span class="switch-thumb">
            <CheckIcon />
          </span>
        </span>
      </label>

      <p class="text-[11px] text-red-500">
        * Kedua switch di atas wajib diaktifkan sebelum lanjut.
      </p>
    </div>
  </div>
</template>

<style scoped>
/* input asli disembunyikan tapi tetap accessible */
.switch-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

/* =================== BASE (MODE TERANG) =================== */
.switch-track {
  width: 44px;
  height: 24px;
  border-radius: 999px;
  background: #f3f4f6;
  border: 1px solid rgba(148, 163, 184, 0.85);
  display: inline-flex;
  align-items: center;
  padding: 2px;
  box-sizing: border-box;
  transition:
    background 0.22s ease-out,
    border-color 0.22s ease-out,
    box-shadow 0.22s ease-out;
  box-shadow:
    0 0 0 1px rgba(148, 163, 184, 0.35),
    0 6px 14px rgba(15, 23, 42, 0.12);
}

.switch-thumb {
  width: 18px;
  height: 18px;
  border-radius: 999px;
  background: #ffffff;
  color: #0f172a;
  display: flex;
  align-items: center;
  justify-content: center;
  transform: translateX(0);
  box-shadow: 0 2px 5px rgba(15, 23, 42, 0.25);
  transition:
    transform 0.22s cubic-bezier(0.23, 1, 0.32, 1),
    background 0.22s ease-out,
    box-shadow 0.22s ease-out,
    color 0.22s ease-out;
}

.switch-thumb svg {
  width: 11px;
  height: 11px;
  opacity: 0;
  transform: scale(0.5);
  stroke-width: 2.2;
  transition:
    opacity 0.18s ease-out,
    transform 0.18s ease-out;
}

/* ON state – LIGHT MODE */
.switch-track--on {
  background:
    radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.3), transparent 60%),
    radial-gradient(circle at 100% 100%, rgba(129, 140, 248, 0.35), transparent 55%),
    #e0f2fe;
  border-color: rgba(37, 99, 235, 0.8);
  box-shadow:
    0 0 0 1px rgba(56, 189, 248, 0.9),
    0 0 18px rgba(56, 189, 248, 0.85),
    0 0 26px rgba(129, 140, 248, 0.8);
}

.switch-track--on .switch-thumb {
  transform: translateX(18px);
  background: #0f172a;
  color: #e0f2fe;
  box-shadow:
    0 0 0 1px rgba(248, 250, 252, 0.85),
    0 4px 14px rgba(15, 23, 42, 0.85);
}

.switch-track--on .switch-thumb svg {
  opacity: 1;
  transform: scale(1);
}

/* hover effect */
.switch-track:hover .switch-thumb {
  box-shadow:
    0 0 0 1px rgba(148, 163, 184, 0.8),
    0 4px 12px rgba(15, 23, 42, 0.4);
}

/* =================== OVERRIDE MODE GELAP =================== */
.dark .switch-track {
  background: #020617;
  border-color: rgba(51, 65, 85, 0.9);
  box-shadow:
    0 0 0 1px rgba(15, 23, 42, 0.95),
    0 6px 16px rgba(15, 23, 42, 0.95);
}

.dark .switch-thumb {
  background: #e5e7eb;
  color: #0f172a;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.7);
}

.dark .switch-track--on {
  background:
    radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.45), transparent 55%),
    radial-gradient(circle at 100% 100%, rgba(129, 140, 248, 0.4), transparent 55%),
    #020617;
  border-color: rgba(248, 250, 252, 0.8);
  box-shadow:
    0 0 0 1px rgba(56, 189, 248, 0.9),
    0 0 16px rgba(56, 189, 248, 1),
    0 0 28px rgba(129, 140, 248, 1);
}

.dark .switch-track--on .switch-thumb {
  background: #020617;
  color: #fefce8;
  box-shadow:
    0 0 0 1px rgba(248, 250, 252, 0.85),
    0 4px 14px rgba(15, 23, 42, 1);
}

.dark .switch-track--on:hover {
  box-shadow:
    0 0 0 1px rgba(56, 189, 248, 1),
    0 0 20px rgba(56, 189, 248, 1),
    0 0 32px rgba(129, 140, 248, 1);
}
</style>
