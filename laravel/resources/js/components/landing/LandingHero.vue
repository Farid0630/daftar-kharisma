<script setup>
import { ref } from "vue";
import {
  AcademicCapIcon,
  ClipboardDocumentCheckIcon,
  InformationCircleIcon,
  ShieldCheckIcon,
} from "@heroicons/vue/24/outline";

// hanya dipakai untuk scroll ke section lain
const scrollTo = (id) => {
  const el = document.getElementById(id);
  if (!el) return;
  const offset = 80;
  const top = el.getBoundingClientRect().top + window.scrollY - offset;
  window.scrollTo({ top, behavior: "smooth" });
};

const heroImage = 'storage/landing/ADM00243.JPG'
</script>

<template>
  <section
    id="home"
    class="relative overflow-hidden
           border-b border-slate-200/70 dark:border-slate-800/70
           bg-slate-50
           dark:bg-gradient-to-b dark:from-slate-950 dark:via-slate-950 dark:to-slate-900"
  >
    <!-- dekorasi background -->
    <div class="pointer-events-none absolute inset-0 -z-10">
      <!-- sedikit gradien juga di mode terang, tapi sangat halus -->
      <div
        class="absolute inset-x-0 -top-32 h-40 bg-gradient-to-b from-sky-100/60 via-slate-50 to-transparent
               dark:hidden"
      />
      <div
        class="hidden dark:block absolute -top-32 -right-12 h-72 w-72 rounded-full bg-sky-500/25 blur-3xl"
      />
      <div
        class="hidden dark:block absolute -bottom-40 -left-16 h-80 w-80 rounded-full bg-indigo-500/25 blur-3xl"
      />
      <div
        class="hidden dark:block absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-slate-950 to-transparent"
      />
    </div>

    <div class="max-w-6xl mx-auto px-4 pt-6 pb-10 sm:pt-8 sm:pb-14">
      <!-- mini nav: chip scrollable + status PMB -->
      <div
        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between
               mb-6 sm:mb-8 text-[11px] sm:text-xs"
      >
        <!-- chip scroll -->
       
        <!-- status PMB -->
        <p
          class="flex items-center gap-2 text-[11px] sm:text-xs
                 text-emerald-600/90 dark:text-emerald-300/90"
        >
          <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse" />
          PMB TA 2026 / 2027 — Pendaftaran online dibuka
        </p>
      </div>

      <!-- isi hero -->
      <div
        class="grid gap-8 lg:gap-12 items-center
               lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]"
      >
        <!-- kiri: teks -->
        <div class="space-y-4 sm:space-y-5">
          <p
            class="inline-flex items-center gap-2 rounded-full
                   border border-sky-500/40 bg-sky-500/10
                   px-3 py-1.5 text-[11px] sm:text-xs font-medium
                   uppercase tracking-[0.20em]
                   text-sky-700 dark:text-sky-200 dark:bg-sky-500/15"
          >
            <AcademicCapIcon class="w-4 h-4" />
            <span>KHARISMA COLLEGE — Mahasiswa Baru</span>
          </p>

          <h1
            class="text-[1.9rem] sm:text-4xl md:text-5xl font-semibold
                   leading-tight sm:leading-tight tracking-tight
                   text-slate-900 dark:text-slate-50"
          >
            Pintu gerbang menuju
            <span
              class="text-transparent bg-clip-text
                     bg-gradient-to-r from-sky-500 via-cyan-400 to-blue-500"
            >
              karier digital
            </span>
            di bidang Informatika, Sistem Informasi, dan Bisnis Digital.
          </h1>

          <p
            class="text-sm sm:text-[15px] text-slate-600 dark:text-slate-300
                   max-w-xl leading-relaxed"
          >
            Di KHARISMA College, kamu belajar di kampus yang fokus pada
            <span
              class="font-semibold text-sky-700 dark:text-sky-200"
            >
              digital entrepreneurship
            </span>
            dengan kurikulum up-to-date, dosen praktisi, dan dukungan jaringan
            mitra industri. Seluruh proses pendaftaran dapat dilakukan secara
            online melalui portal ini.
          </p>

          <!-- poin kecil benefit -->
          <ul
            class="mt-1 space-y-1.5 text-[12px] sm:text-[13px]
                   text-slate-600 dark:text-slate-300"
          >
            <li>• Pendaftaran 100% online &amp; terintegrasi.</li>
            <li>• Jadwal ujian &amp; pengumuman dapat dipantau lewat portal.</li>
          </ul>

          <!-- info kampus -->
          <div class="grid gap-3 sm:grid-cols-2 text-xs sm:text-sm mt-2">
            <div
              class="rounded-2xl border border-slate-200 bg-white/90
                     px-3.5 py-3 shadow-sm
                     dark:bg-slate-900/80 dark:border-slate-700"
            >
              <p
                class="text-[11px] uppercase tracking-[0.16em]
                       text-slate-500 dark:text-slate-400 mb-1"
              >
                Lokasi Kampus
              </p>
              <p
                class="font-semibold text-slate-900 dark:text-slate-100"
              >
                Jl. Baji Ateka No. 20, Makassar
              </p>
            </div>
            <div
              class="rounded-2xl border border-slate-200 bg-white/90
                     px-3.5 py-3 shadow-sm
                     dark:bg-slate-900/80 dark:border-slate-700"
            >
              <p
                class="text-[11px] uppercase tracking-[0.16em]
                       text-slate-500 dark:text-slate-400 mb-1"
              >
                Program Studi
              </p>
              <p
                class="font-semibold text-slate-900 dark:text-slate-100"
              >
                Sistem Informasi · Informatika · Bisnis Digital
              </p>
            </div>
          </div>

          <!-- CTA -->
          <div
            class="flex flex-col sm:flex-row gap-2.5 sm:gap-3 pt-1
                   text-sm sm:text-base"
          >
            <a
              href="/pmb/register"
              class="btn btn-primary w-full sm:w-auto justify-center
                     rounded-full px-6 min-h-[2.8rem] border-0 normal-case
                     bg-gradient-to-r from-sky-500 to-blue-600
                     text-white shadow-[0_8px_25px_rgba(56,189,248,0.55)]
                     hover:brightness-110 hover:-translate-y-px
                     active:translate-y-0 transition-transform"
            >
              <ClipboardDocumentCheckIcon class="w-5 h-5" />
              <span>Mulai Daftar Sekarang</span>
            </a>

            <button
              type="button"
              class="btn btn-outline w-full sm:w-auto justify-center
                     rounded-full px-5 min-h-[2.8rem]
                     border-slate-400 text-slate-800 bg-white/80
                     hover:border-sky-500 hover:text-sky-600
                     dark:border-slate-600 dark:bg-slate-900/60
                     dark:text-slate-100 dark:hover:border-sky-400
                     dark:hover:text-sky-200 normal-case"
              @click="scrollTo('jalur')"
            >
              <InformationCircleIcon class="w-5 h-5" />
              <span>Lihat Jalur &amp; Beasiswa</span>
            </button>
          </div>
        </div>

        <!-- kanan: gambar + statistik -->
        <div
          class="space-y-4 w-full max-w-md lg:max-w-lg
                 mx-auto lg:mx-0"
        >
          <!-- gambar hero -->
          <div
            class="relative aspect-video rounded-3xl overflow-hidden
                   border border-slate-200 shadow-xl shadow-slate-900/10
                   bg-slate-200/60
                   dark:border-slate-700 dark:shadow-slate-900/70
                   dark:bg-slate-900/80"
          >
            <img
              :src="heroImage"
              alt="Suasana belajar di laboratorium komputer KHARISMA"
              class="w-full h-full object-cover"
            />
            <div
              class="absolute inset-0 bg-gradient-to-t
                     from-slate-950/85 via-slate-950/35 to-transparent"
            />

            <div
              class="absolute left-4 right-4 bottom-4
                     text-xs sm:text-sm text-slate-50
                     flex flex-col gap-1"
            >
            </div>
          </div>

          <!-- statistik -->
          <div
            class="rounded-3xl border border-slate-200 bg-white/95
                   text-slate-900 p-4 sm:p-5 shadow-lg
                   dark:border-slate-700 dark:bg-slate-900/95
                   dark:text-slate-100"
          >
            <div class="flex items-center justify-between mb-4">
              <div>
                <p
                  class="text-[11px] text-slate-500 dark:text-slate-400
                         uppercase tracking-[0.18em]"
                >
                  Statistik singkat
                </p>
                <p class="text-sm font-semibold mt-1">
                  Data ilustrasi kampus
                </p>
              </div>
              <span
                class="px-3 py-1 rounded-full text-[11px]
                       bg-emerald-500/10 text-emerald-700
                       border border-emerald-400/60
                       dark:bg-emerald-500/15 dark:text-emerald-200"
              >
                Ilustrasi
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div
                class="rounded-2xl bg-sky-500/10 px-3 py-2.5
                       flex flex-col justify-between
                       dark:bg-sky-500/20"
              >
                <p
                  class="text-[11px] text-slate-700 dark:text-slate-100 mb-1"
                >
                  Mahasiswa aktif
                </p>
                <p
                  class="text-xl sm:text-2xl font-semibold
                         text-sky-700 dark:text-sky-100"
                >
                  700+
                </p>
              </div>
              <div
                class="rounded-2xl bg-slate-100 px-3 py-2.5
                       flex flex-col justify-between
                       dark:bg-slate-800/90"
              >
                <p
                  class="text-[11px] text-slate-600 dark:text-slate-300 mb-1"
                >
                  Program Sarjana
                </p>
                <p class="text-xl sm:text-2xl font-semibold">3</p>
              </div>
              <div
                class="rounded-2xl bg-slate-100 px-3 py-2.5
                       flex flex-col justify-between
                       dark:bg-slate-800/90"
              >
                <p
                  class="text-[11px] text-slate-600 dark:text-slate-300 mb-1"
                >
                  Mitra Industri
                </p>
                <p class="text-xl sm:text-2xl font-semibold">20+</p>
              </div>
            </div>

            <p
              class="mt-3 text-[11px] sm:text-xs
                     text-slate-500 dark:text-slate-400
                     flex items-center gap-1.5"
            >
              <ShieldCheckIcon class="w-4 h-4 text-emerald-500" />
              Sesuaikan angka di atas dengan data resmi kampus.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

</style>
