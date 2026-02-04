<script setup>
import {
  AcademicCapIcon,
  ServerStackIcon,
  UsersIcon,
  GlobeAltIcon,
} from '@heroicons/vue/24/outline'
</script>

<template>
  <section
    id="profil"
    class="relative max-w-6xl mx-auto px-4 py-10 md:py-12
           border-b border-slate-200/80 dark:border-slate-800/80
           text-[15px] sm:text-base"
  >
    <!-- aksen background halus -->
    <div
      class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-40
             bg-gradient-to-b from-sky-50 via-white to-transparent
             dark:from-slate-900 dark:via-slate-950"
    ></div>

    <div class="grid md:grid-cols-2 gap-8 md:gap-10 items-start">
      <!-- teks kiri -->
      <div class="space-y-4 md:space-y-5">
        <p
          class="inline-flex items-center gap-1.5 rounded-full
                 bg-sky-50 text-sky-700 px-3 py-1 text-[11px] font-medium
                 border border-sky-100
                 dark:bg-sky-500/10 dark:text-sky-200 dark:border-sky-500/40"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-sky-500 animate-pulse" />
          Tentang STMIK KHARISMA
        </p>

        <h2 class="text-xl md:text-2xl lg:text-3xl font-semibold tracking-tight">
          Profil Singkat KHARISMA
        </h2>

        <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
          STMIK KHARISMA Makassar adalah perguruan tinggi yang berfokus pada
          bidang informatika dan komputer. Kampus ini menyiapkan lulusan yang
          siap kerja, inovatif, dan beretika melalui kurikulum terkini dan
          dosen berpengalaman.
        </p>
        <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
          Portal pendaftaran ini dirancang agar calon mahasiswa dapat melakukan
          seluruh proses secara online, mulai dari pengisian data diri,
          pemilihan jalur masuk, unggah berkas, hingga pengumuman hasil seleksi.
        </p>

        <!-- poin tambahan kecil -->
        <ul class="mt-1 space-y-1.5 text-[13px] text-slate-600 dark:text-slate-300">
          <li>• Terakreditasi dan fokus pada pengembangan kompetensi digital.</li>
          <li>• Lingkungan belajar yang dekat dengan dunia industri &amp; startup.</li>
        </ul>
      </div>

      <!-- kartu kanan -->
      <div class="grid sm:grid-cols-2 gap-3 sm:gap-4">
        <!-- card 1 -->
        <div
          class="group rounded-2xl border border-slate-200 bg-white/95 p-4
                 shadow-sm hover:shadow-md hover:border-sky-300
                 transition-all duration-200
                 dark:bg-slate-900 dark:border-slate-700 dark:hover:border-sky-500/70"
        >
          <div
            class="mb-2 inline-flex h-9 w-9 items-center justify-center
                   rounded-xl bg-sky-500/10 text-sky-500
                   group-hover:bg-sky-500/20"
          >
            <AcademicCapIcon class="w-5 h-5" />
          </div>
          <p class="font-semibold mb-1 text-slate-900 dark:text-slate-50">
            Fokus pada IT &amp; Bisnis
          </p>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            Program studi yang relevan dengan kebutuhan industri digital
            dan kewirausahaan teknologi.
          </p>
        </div>

        <!-- card 2 -->
        <div
          class="group rounded-2xl border border-slate-200 bg-white/95 p-4
                 shadow-sm hover:shadow-md hover:border-sky-300
                 transition-all duration-200
                 dark:bg-slate-900 dark:border-slate-700 dark:hover:border-sky-500/70"
        >
          <div
            class="mb-2 inline-flex h-9 w-9 items-center justify-center
                   rounded-xl bg-sky-500/10 text-sky-500
                   group-hover:bg-sky-500/20"
          >
            <ServerStackIcon class="w-5 h-5" />
          </div>
          <p class="font-semibold mb-1 text-slate-900 dark:text-slate-50">
            Fasilitas Terkini
          </p>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            Laboratorium komputer, jaringan internet, dan LMS terintegrasi
            untuk menunjang proses belajar.
          </p>
        </div>

        <!-- card 3 -->
        <div
          class="group rounded-2xl border border-slate-200 bg-white/95 p-4
                 shadow-sm hover:shadow-md hover:border-sky-300
                 transition-all duration-200
                 dark:bg-slate-900 dark:border-slate-700 dark:hover:border-sky-500/70"
        >
          <div
            class="mb-2 inline-flex h-9 w-9 items-center justify-center
                   rounded-xl bg-sky-500/10 text-sky-500
                   group-hover:bg-sky-500/20"
          >
            <UsersIcon class="w-5 h-5" />
          </div>
          <p class="font-semibold mb-1 text-slate-900 dark:text-slate-50">
            Komunitas Aktif
          </p>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            Berbagai komunitas dan organisasi mahasiswa untuk mengasah soft skill
            dan jejaring pertemanan.
          </p>
        </div>

        <!-- card 4 -->
        <div
          class="group rounded-2xl border border-slate-200 bg-white/95 p-4
                 shadow-sm hover:shadow-md hover:border-sky-300
                 transition-all duration-200
                 dark:bg-slate-900 dark:border-slate-700 dark:hover:border-sky-500/70"
        >
          <div
            class="mb-2 inline-flex h-9 w-9 items-center justify-center
                   rounded-xl bg-sky-500/10 text-sky-500
                   group-hover:bg-sky-500/20"
          >
            <GlobeAltIcon class="w-5 h-5" />
          </div>
          <p class="font-semibold mb-1 text-slate-900 dark:text-slate-50">
            Terhubung ke Dunia Kerja
          </p>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            Kerja sama dengan perusahaan, startup, dan instansi pemerintah
            untuk magang dan rekrutmen lulusan.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
