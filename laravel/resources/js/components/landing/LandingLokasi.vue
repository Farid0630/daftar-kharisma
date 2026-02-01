<script setup>
import {
  ClipboardDocumentCheckIcon,
  MapPinIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
</script>

<template>
  <section
    id="lokasi"
    class="relative max-w-6xl mx-auto px-4 py-10 md:py-12
           text-[15px] sm:text-base"
  >
    <!-- background aksen halus -->
    <div
      class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-40
             bg-gradient-to-b from-sky-50 via-white to-transparent
             dark:from-slate-900 dark:via-slate-950"
    ></div>

    <div class="grid lg:grid-cols-2 gap-8 lg:gap-10 items-start">
      <!-- KIRI: TEKS & CTA -->
      <div class="space-y-4 md:space-y-5">
        <!-- label kecil -->
        <p
          class="inline-flex items-center gap-1.5 rounded-full
                 bg-sky-50 text-sky-700 px-3 py-1 text-[11px] font-medium
                 border border-sky-100
                 dark:bg-sky-500/10 dark:text-sky-200 dark:border-sky-500/40"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-sky-500 animate-pulse" />
          Lokasi Kampus &amp; Kontak PMB
        </p>

        <h2 class="text-xl md:text-2xl lg:text-3xl font-semibold tracking-tight">
          Lokasi Kampus &amp; Kontak Pendaftaran
        </h2>

        <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
          Silakan datang langsung ke kampus atau hubungi panitia PMB untuk
          konsultasi jurusan, beasiswa, dan proses pendaftaran. Tim kami siap
          membantu menjawab pertanyaan seputar perkuliahan di STMIK KHARISMA.
        </p>

        <!-- info alamat & kontak -->
        <ul class="space-y-2.5 text-sm sm:text-base">
          <li class="flex items-start gap-2.5">
            <div
              class="mt-0.5 flex h-8 w-8 items-center justify-center
                     rounded-xl bg-sky-500/10 text-sky-500
                     dark:bg-sky-500/20 dark:text-sky-200"
            >
              <MapPinIcon class="w-4 h-4" />
            </div>
            <div>
              <p class="font-semibold text-slate-800 dark:text-slate-100">
                Alamat Kampus
              </p>
              <p class="text-slate-600 dark:text-slate-300">
                Jl. Baji Ateka No. 20 Makassar <br />
                <span class="text-slate-500 dark:text-slate-400 text-[14px]">
                  STMIK KHARISMA Makassar
                </span>
              </p>
            </div>
          </li>

          <li class="flex items-start gap-2.5">
            <div
              class="mt-0.5 flex h-8 w-8 items-center justify-center
                     rounded-xl bg-sky-500/10 text-sky-500
                     dark:bg-sky-500/20 dark:text-sky-200"
            >
              <UsersIcon class="w-4 h-4" />
            </div>
            <div>
              <p class="font-semibold text-slate-800 dark:text-slate-100">
                Kontak Panitia PMB
              </p>
              <p class="text-slate-600 dark:text-slate-300">
                Panitia PMB — 6282346810090 <br />
                <span class="text-[14px] text-slate-500 dark:text-slate-400">
                  Contoh: Senin–Jumat, pukul 09.00–15.00 WITA
                </span>
              </p>
            </div>
          </li>
        </ul>

        <!-- info kecil + CTA -->
        <div class="space-y-2">
          <p class="text-[12px] text-slate-500 dark:text-slate-400">
            Kamu juga dapat melakukan seluruh proses pendaftaran secara online
            melalui portal PMB tanpa harus datang langsung ke kampus.
          </p>

          <a
            href="/pmb/register"
            class="btn btn-primary rounded-full px-6 mt-1
                   w-full sm:w-auto justify-center
                   min-h-[2.9rem] border-0 normal-case
                   bg-gradient-to-r from-sky-500 to-blue-600
                   text-white shadow-[0_10px_30px_rgba(56,189,248,0.5)]
                   hover:brightness-110 hover:-translate-y-px
                   active:translate-y-0
                   transition-transform"
          >
            <ClipboardDocumentCheckIcon class="w-5 h-5" />
            <span>Buka Formulir Pendaftaran</span>
          </a>
        </div>
      </div>

      <!-- KANAN: MAP CARD -->
      <div
        class="rounded-3xl overflow-hidden border border-slate-200
               bg-slate-200/60 shadow-lg shadow-slate-900/10
               dark:border-slate-700 dark:bg-slate-900/90 dark:shadow-slate-900/60"
      >
        <!-- header kecil di atas map -->
        <div
          class="flex items-center justify-between px-4 py-2.5
                 border-b border-slate-200/80
                 bg-white/80 backdrop-blur-sm
                 dark:bg-slate-900/90 dark:border-slate-700/80"
        >
          <p class="text-xs font-medium text-slate-700 dark:text-slate-100">
            Peta Lokasi Kampus
          </p>
          <span
            class="inline-flex items-center rounded-full
                   bg-sky-50 text-sky-700 px-2 py-0.5 text-[10px]
                   border border-sky-100
                   dark:bg-sky-500/10 dark:text-sky-200 dark:border-sky-500/40"
          >
            Google Maps
          </span>
        </div>

        <!-- Ganti src dengan embed Google Maps kampus -->
        <iframe
          class="w-full h-64 sm:h-72 md:h-80"
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3818.3838353618903!2d119.410210574795!3d-5.167436894809962!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dbf1d63ec571e31%3A0x601270777e3f8020!2sSekolah%20Tinggi%20Manajemen%20Informatika%20dan%20Komputer%20Kharisma!5e1!3m2!1sid!2sid!4v1764254092952!5m2!1sid!2sid"
          style="border: 0"
          allowfullscreen=""
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
      </div>
    </div>
  </section>
</template>
