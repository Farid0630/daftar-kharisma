<script setup>
const partners = [
  { name: 'Arta Boga', src: 'storage/landing/ArtaBoga.jpg' },
  { name: 'Bank BCA', src: 'storage/landing/BankBCA.png' },
  { name: 'Bank Bumi Arta', src: 'storage/landing/BankBumiArta.jpg' },
  { name: 'Arta Boga', src: 'storage/landing/BankDanamon.png' },
  { name: 'Bank BCA', src: 'storage/landing/BankPanin.jpg' },
  { name: 'Bank Bumi Arta', src: 'storage/landing/BrownBagFilms.png' },
  { name: 'Arta Boga', src: 'storage/landing/CIMBNIAGA.png' },
  { name: 'Bank BCA', src: 'storage/landing/GandivaStudio.jpg' },
  { name: 'Bank Bumi Arta', src: 'storage/landing/J&T.png' },
  { name: 'Arta Boga', src: 'storage/landing/JIVA.png' },
  { name: 'Bank BCA', src: 'storage/landing/KementerianPendidikan&budaya.jpg' },
  { name: 'Bank Bumi Arta', src: 'storage/landing/KompasTVMakassar.png' },
  { name: 'Arta Boga', src: 'storage/landing/ArtaBoga.jpg' },
  { name: 'Bank BCA', src: 'storage/landing/MikrotikAcademy.jpeg' },
  { name: 'Bank Bumi Arta', src: 'storage/landing/PTKristalabSuryaMedika.jpg' },
  { name: 'Bank Bumi Arta', src: 'storage/landing/S3Indonesia.png' },
  { name: 'Bank Bumi Arta', src: 'storage/landing/SchoolOfChild.png' },
  { name: 'Bank Bumi Arta', src: 'storage/landing/WASDLABS.jpg' },
]
</script>

<template>
  <section
    id="mitra"
    class="relative max-w-6xl mx-auto px-4 py-10 md:py-12
           border-b border-slate-200/80 dark:border-slate-800/80
           text-[15px] sm:text-base"
  >
    <div
      class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-40
             bg-gradient-to-b from-sky-50 via-white to-transparent
             dark:from-slate-900 dark:via-slate-950"
    ></div>

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
      <div class="space-y-1.5">
        <p
          class="inline-flex items-center gap-1.5 rounded-full
                 bg-emerald-50 text-emerald-700 px-3 py-1 text-[11px] font-medium
                 border border-emerald-100
                 dark:bg-emerald-500/10 dark:text-emerald-200 dark:border-emerald-500/40"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
          Jaringan Mitra Industri &amp; Institusi
        </p>
        <h2 class="text-xl md:text-2xl lg:text-3xl font-semibold tracking-tight">
          Mitra Kerja &amp; Industri
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 mt-0.5">
          Dukungan dunia usaha, industri, dan institusi pendidikan untuk program
          magang, kolaborasi proyek, dan rekrutmen lulusan.
        </p>
      </div>

      <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 max-w-xs">
        Logo resmi dan daftar mitra lengkap dapat disesuaikan dengan kerja sama
        terbaru KHARISMA College setiap tahun akademik.
      </p>
    </div>

    <!-- GRID MITRA -->
    <div
      class="rounded-3xl border border-slate-200 bg-white/90
             px-4 py-4 sm:py-5
             dark:bg-slate-900/90 dark:border-slate-700
             shadow-sm"
    >
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
        <div
          v-for="(partner, idx) in partners"
          :key="idx"
          class="group relative flex items-center justify-center text-center
                 rounded-2xl border border-slate-200 bg-slate-50/90
                 px-3 py-3 sm:py-4
                 text-xs sm:text-sm text-slate-600
                 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300
                 hover:border-sky-400/80 hover:bg-sky-50/80
                 dark:hover:border-sky-500/80 dark:hover:bg-slate-800
                 transition-all duration-200"
          :class="idx === 0 ? 'ring-1 ring-emerald-300/70 dark:ring-emerald-500/70' : ''"
        >
          <span
            class="pointer-events-none absolute inset-x-6 -top-px h-0.5
                   rounded-full bg-gradient-to-r
                   from-sky-300 via-emerald-300 to-sky-300
                   opacity-0 group-hover:opacity-100
                   transition-opacity duration-200"
          ></span>

          <!-- Jika ada gambar -->
          <img
            v-if="partner.src"
            :src="partner.src"
            :alt="partner.name"
            class="h-8 sm:h-10 md:h-12 w-auto max-w-[160px]
                   object-contain
                   opacity-90 group-hover:opacity-100
                   transition"
            loading="lazy"
          />

          <!-- Jika tidak ada gambar -->
          <span v-else class="leading-snug">
            {{ partner.name }}
          </span>
        </div>
      </div>

      <div
        class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between
               gap-2 text-[11px] sm:text-xs text-slate-500 dark:text-slate-400"
      >
        <p>Keterangan: kotak di atas dapat diganti dengan logo resmi perusahaan / institusi mitra.</p>
        <p class="italic">* Daftar mitra dapat bertambah seiring kerja sama baru yang terjalin.</p>
      </div>
    </div>
  </section>
</template>
