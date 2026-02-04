<!-- resources/js/components/layout/AppFooter.vue -->
<template>
  <footer
    class="mt-auto border-t border-blue-200/70 dark:border-blue-900/70
           bg-gradient-to-r from-sky-50 via-blue-50 to-slate-100
           dark:from-blue-950 dark:via-slate-950 dark:to-blue-950
           text-slate-900 dark:text-slate-100"
  >
    <!-- LAPIS GLOW HALUS -->
    <div
      class="pointer-events-none absolute inset-x-0 bottom-0 h-32 -z-10
             bg-gradient-to-t from-blue-500/20 via-sky-400/10 to-transparent
             dark:from-sky-500/20 dark:via-blue-500/10"
    ></div>

    <!-- BAGIAN ATAS -->
    <div class="max-w-6xl mx-auto px-3 sm:px-4 md:px-8 py-6 md:py-8">
      <div
        class="grid gap-6 md:gap-8
               md:grid-cols-2 lg:grid-cols-[1.15fr_0.9fr_1.1fr]
               items-start"
      >
        <!-- Kolom 1: Brand & tagline -->
        <div class="space-y-3">
          <div class="flex items-center gap-2.5">
            <div
              class="flex h-9 w-9 items-center justify-center rounded-xl
                     bg-sky-500 text-white shadow-md shadow-sky-500/60"
            >
              <AcademicCapIcon class="w-5 h-5" />
            </div>
            <div class="leading-tight">
              <p
                class="text-sm md:text-base font-semibold tracking-tight
                       text-slate-900 dark:text-slate-50"
              >
                STMIK KHARISMA Makassar
              </p>
              <p
                class="text-[11px] md:text-xs text-slate-600 dark:text-slate-300"
              >
                Sistem Informasi Akademik — SISKA 5.0
              </p>
            </div>
          </div>

          <p
            class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 max-w-md"
          >
            Portal ini mendukung proses akademik dan penerimaan mahasiswa baru
            secara online, terintegrasi dengan data kampus dan layanan akademik
            lainnya.
          </p>

          <span
            class="inline-flex items-center gap-2 rounded-full
                   bg-sky-100/80 text-sky-800
                   dark:bg-sky-500/15 dark:text-sky-200
                   border border-sky-200/80 dark:border-sky-500/40
                   px-3 py-1 text-[11px] font-medium"
          >
            <SparklesIcon class="w-4 h-4" />
            PMB {{ year + 1}}/{{ year + 2 }} &amp; Layanan Akademik
          </span>
        </div>

        <!-- Kolom 2: Menu cepat -->
        <div class="space-y-3">
          <h3
            class="text-[11px] font-semibold uppercase tracking-[0.18em]
                   text-blue-700 dark:text-blue-300"
          >
            Menu Cepat
          </h3>

          <ul class="space-y-1.5 text-xs md:text-sm">
            <li>
              <button
                type="button"
                @click="scrollTo('home')"
                class="inline-flex items-center justify-between w-full
                       rounded-2xl px-3 py-2
                       bg-white/80 dark:bg-slate-900/70
                       border border-slate-200/80 dark:border-slate-700/80
                       hover:border-sky-400 hover:bg-sky-50/90
                       dark:hover:border-sky-400/80 dark:hover:bg-sky-900/40
                       text-left transition-colors"
              >
                <span class="inline-flex items-center gap-2">
                  <SparklesIcon class="w-4 h-4 text-sky-500" />
                  <span>Informasi PMB</span>
                </span>
                <span
                  class="text-[10px] text-sky-600 dark:text-sky-300
                         hidden sm:inline"
                >
                  Beranda
                </span>
              </button>
            </li>

            <li>
              <button
                type="button"
                @click="scrollTo('profil')"
                class="inline-flex items-center justify-between w-full
                       rounded-2xl px-3 py-2
                       bg-white/80 dark:bg-slate-900/70
                       border border-slate-200/80 dark:border-slate-700/80
                       hover:border-sky-400 hover:bg-sky-50/90
                       dark:hover:border-sky-400/80 dark:hover:bg-sky-900/40
                       text-left transition-colors"
              >
                <span class="inline-flex items-center gap-2">
                  <BookOpenIcon class="w-4 h-4 text-sky-500" />
                  <span>Program Studi &amp; Profil Kampus</span>
                </span>
                <span
                  class="text-[10px] text-slate-500 dark:text-slate-400
                         hidden sm:inline"
                >
                  Lihat profil
                </span>
              </button>
            </li>

            <li>
              <a
                href="https://www.stmikkharisma.ac.id"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center justify-between w-full
                       rounded-2xl px-3 py-2
                       bg-white/80 dark:bg-slate-900/70
                       border border-slate-200/80 dark:border-slate-700/80
                       hover:border-emerald-400 hover:bg-emerald-50/80
                       dark:hover:border-emerald-400/80 dark:hover:bg-emerald-900/30
                       text-left transition-colors"
              >
                <span class="inline-flex items-center gap-2">
                  <GlobeAltIcon class="w-4 h-4 text-emerald-500" />
                  <span>Website Resmi Kampus</span>
                </span>
                <span
                  class="text-[10px] text-emerald-600 dark:text-emerald-300
                         hidden sm:inline"
                >
                  Buka tab baru
                </span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Kolom 3: Kontak & lokasi -->
        <div class="space-y-3 lg:justify-self-end">
          <h3
            class="text-[11px] font-semibold uppercase tracking-[0.18em]
                   text-blue-700 dark:text-blue-300"
          >
            Kontak &amp; Lokasi
          </h3>

          <ul class="space-y-1.5 text-xs md:text-sm">
            <li class="flex items-start gap-2">
              <MapPinIcon
                class="w-4 h-4 mt-0.5 text-sky-600 dark:text-sky-300"
              />
              <span>
                Kampus STMIK KHARISMA Makassar<br />
                <span class="text-slate-600 dark:text-slate-300">
                  Jl. Baji Ateka No. 20 Makassar 
                </span>
              </span>
            </li>
            <li class="flex items-center gap-2">
              <PhoneIcon
                class="w-4 h-4 text-sky-600 dark:text-sky-300"
              />
              <span>+6282346810090</span>
            </li>
            <li class="flex items-center gap-2">
              <EnvelopeIcon
                class="w-4 h-4 text-sky-600 dark:text-sky-300"
              />
              <span>marcomm@kharisma.ac.id</span>
            </li>
          </ul>

          <button
            type="button"
            @click="scrollTo('lokasi')"
            class="inline-flex items-center gap-2 mt-2
                   rounded-full border border-sky-400/80
                   bg-sky-500/10 px-3 py-1.5
                   text-[11px] font-medium text-sky-800
                   hover:bg-sky-500/20
                   dark:border-sky-500/80 dark:text-sky-100
                   dark:hover:bg-sky-900/40 transition-colors"
          >
            <MapPinIcon class="w-4 h-4" />
            <span>Lihat peta lokasi kampus</span>
          </button>
        </div>
      </div>
    </div>

    <!-- GARIS TIPIS -->
    <div
      class="h-px w-full bg-gradient-to-r
             from-transparent via-sky-400/60 to-transparent
             opacity-80"
    ></div>

    <!-- BARIS BAWAH -->
    <div
      class="max-w-6xl mx-auto px-3 sm:px-4 md:px-8 py-2.5
             flex flex-col md:flex-row items-center justify-between gap-1.5"
    >
      <p
        class="text-[11px] text-slate-600 dark:text-slate-300
               text-center md:text-left"
      >
        © {{ year + 1 }} SISKA 5.0 — STMIK KHARISMA Makassar. All rights reserved.
      </p>

      <p
        class="text-[11px] flex items-center gap-1.5
               text-slate-500 dark:text-slate-400"
      >
        <CodeBracketIcon class="w-4 h-4" />
        <span>Built for a smarter academic experience</span>
      </p>
    </div>
  </footer>
</template>

<script setup>
import {
  AcademicCapIcon,
  BookOpenIcon,
  SparklesIcon,
  GlobeAltIcon,
  MapPinIcon,
  PhoneIcon,
  EnvelopeIcon,
  CodeBracketIcon,
} from '@heroicons/vue/24/outline'

const year = new Date().getFullYear()

const scrollTo = (id) => {
  if (typeof window === 'undefined' || typeof document === 'undefined') return

  const currentPath = window.location.pathname || '/'

  if (currentPath === '/') {
    // Lagi di landing page → scroll halus ke section
    const el = document.getElementById(id)

    if (el) {
      const offset = 80 // offset tinggi header
      const top = el.getBoundingClientRect().top + window.scrollY - offset
      window.scrollTo({ top, behavior: 'smooth' })
    } else {
      // Kalau elemennya belum ada / id beda, fallback ke redirect
      window.location.href = `/#${id}`
    }
  } else {
    // Kalau lagi di halaman lain → redirect ke landing + anchor
    window.location.href = `/#${id}`
  }
}
</script>
