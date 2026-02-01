<script setup>
import { ref } from 'vue'
import {
  UserPlusIcon,
  BanknotesIcon,
  HeartIcon,
  ChevronDownIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline'

const jalurItems = [
  {
    key: 'mandiri',
    label: 'Jalur Mandiri',
    badge: 'Umum',
    tag: 'Paling banyak dipilih',
    accent: 'Cocok untuk semua siswa di berbagai jurusan sekolah.',
    colorClass: 'bg-sky-500/10 text-sky-700 dark:text-sky-200',
    icon: UserPlusIcon,
    highlight: false,
    desc: 'Jalur mandiri dengan proses kuliah lebih mudah.',
    bullets: [
      'Biaya pendaftaran & UKT terjangkau.',
      'Tersedia skema angsuran biaya kuliah.',
      'Tes wawancara dan psikotes.',
    ],
    registerUrl: '/pmb/register',
    buttonText: 'Daftar Jalur Mandiri',
  },
  {
    key: 'kip',
    label: 'Jalur EXPO Kuliah',
    badge: 'EXPO',
    tag: 'Paling banyak dipilih',
    accent: 'Cocok untuk semua siswa di berbagai jurusan sekolah.',
    colorClass: 'bg-amber-500/10 text-amber-800 dark:text-amber-100',
    icon: BanknotesIcon,
    highlight: true,
    desc: 'Jalur EXPO dengan proses kuliah lebih mudah.',
    bullets: [
      'Biaya pendaftaran & UKT terjangkau.',
      'Tersedia skema angsuran biaya kuliah.',
      'Tes wawancara dan psikotes.',
    ],
    registerUrl: '/pmb/register/kip',
    buttonText: 'Daftar Jalur EXPO',
  },
  {
    key: 'yayasan',
    label: 'Beasiswa Yayasan',
    badge: 'Prestasi & Mitra',
    tag: 'Kerja sama sekolah / industri',
    accent: 'Ideal bagi siswa rekomendasi sekolah atau mitra industri.',
    colorClass: 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-100',
    icon: HeartIcon,
    highlight: true,
    desc: 'Beasiswa dari yayasan untuk siswa berprestasi akademik dan non akademik.',
    bullets: [
      'Potongan biaya kuliah hingga 100% (s&k berlaku).',
      'Rekomendasi dari sekolah .',
      'Melampirkan bukti prestasi .',
    ],
    registerUrl: '/pmb/register/yayasan',
    buttonText: 'Daftar Beasiswa Yayasan',
  },
]

// multi-open: simpan key yang aktif
const activeKeys = ref(['mandiri'])

const isActive = (key) => activeKeys.value.includes(key)

const toggleItem = (key) => {
  if (isActive(key)) {
    activeKeys.value = activeKeys.value.filter((k) => k !== key)
  } else {
    activeKeys.value.push(key)
  }
}
</script>

<template>
  <section
    id="jalur"
    class="relative max-w-6xl mx-auto px-3 sm:px-4 py-8 md:py-12
           border-b border-slate-200/80 dark:border-slate-800/80
           text-[14px] sm:text-[15px]"
  >
    <!-- background aksen halus -->
    <div
      class="pointer-events-none absolute inset-x-0 top-2 -z-10
             h-40 bg-gradient-to-b from-sky-50 via-white to-transparent
             dark:from-slate-900 dark:via-slate-950"
    ></div>

    <!-- HEADER SECTION -->
    <div
      class="flex flex-col md:flex-row md:items-end md:justify-between
             gap-3 md:gap-4 mb-5 sm:mb-6"
    >
      <div class="space-y-1.5">
        <p
          class="inline-flex items-center gap-1.5 rounded-full
                 bg-sky-50 text-sky-700 px-3 py-1 text-[11px] font-medium
                 border border-sky-100
                 dark:bg-sky-500/10 dark:text-sky-200 dark:border-sky-500/40"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-sky-500 animate-pulse" />
          Informasi Jalur Masuk &amp; Beasiswa
        </p>
        <h2 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-semibold">
          Jalur Pendaftaran &amp; Beasiswa
        </h2>
        <p class="text-[13px] sm:text-sm text-slate-600 dark:text-slate-300 mt-0.5">
          Pilih jalur yang paling sesuai dengan profil dan rencana studimu.
        </p>
      </div>

      <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 max-w-xs">
        Detail biaya, kuota, dan persyaratan resmi akan diumumkan melalui
        website &amp; media sosial KHARISMA College. Pastikan mengikuti update
        terbaru.
      </p>
    </div>

    <!-- LIST JALUR (CUSTOM ACCORDION) -->
    <div class="space-y-3 sm:space-y-4">
      <div
        v-for="item in jalurItems"
        :key="item.key"
        class="rounded-3xl p-[1px] transition-all duration-200"
        :class="
          isActive(item.key)
            ? 'bg-gradient-to-r from-sky-300/80 via-blue-300/60 to-emerald-300/80 dark:from-sky-500/60 dark:via-blue-500/40 dark:to-emerald-500/60 shadow-md shadow-sky-200/60 dark:shadow-sky-900/40'
            : 'bg-slate-200/60 dark:bg-slate-800/80'
        "
      >
        <!-- HEADER CARD (BUTTON) -->
        <button
          type="button"
          class="w-full rounded-3xl
                 bg-slate-100/95 border border-slate-200/70
                 dark:bg-slate-900/95 dark:border-slate-700/80
                 group cursor-pointer
                 px-3.5 sm:px-4 py-3 sm:py-3.5
                 flex flex-col xs:flex-row xs:items-center xs:justify-between gap-3"
          @click="toggleItem(item.key)"
          :aria-expanded="isActive(item.key)"
        >
          <!-- kiri: icon + teks -->
          <div class="flex items-center gap-3 text-left">
            <div
              class="flex h-11 w-11 items-center justify-center rounded-2xl
                     bg-sky-500/10 text-sky-500
                     group-hover:bg-sky-500/15 shrink-0"
            >
              <component :is="item.icon" class="w-5 h-5" />
            </div>

            <div class="min-w-0">
              <p class="text-sm sm:text-base font-semibold text-slate-800 dark:text-slate-50">
                {{ item.label }}
              </p>

              <p class="text-[11px] font-medium text-sky-700 dark:text-sky-300">
                {{ item.tag }}
              </p>

              <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                {{ item.accent }}
              </p>
            </div>
          </div>

          <!-- kanan: badge + dot + panah  -->
          <div class="flex items-center justify-between xs:justify-end gap-3">
            <div class="flex items-center gap-2">
              <span
                class="badge badge-sm border-0 text-[11px] sm:text-xs
                       px-3 py-1 rounded-full whitespace-nowrap"
                :class="item.colorClass"
              >
                {{ item.badge }}
              </span>

              <span v-if="item.highlight" class="h-2 w-2 rounded-full bg-rose-400 animate-pulse"></span>
            </div>

            <ChevronDownIcon
              class="w-4 h-4 text-slate-500 dark:text-slate-300 transition-transform duration-200"
              :class="isActive(item.key) ? 'rotate-180' : ''"
            />
          </div>
        </button>

        <!-- CONTENT -->
        <Transition name="accordion">
          <div
            v-if="isActive(item.key)"
            class="mt-2 sm:mt-3 rounded-3xl border border-slate-200 bg-white
                   p-3.5 sm:p-5 text-sm shadow-sm
                   dark:bg-slate-900 dark:border-slate-700"
          >
            <p class="mb-3 text-[13px] sm:text-sm text-slate-700 dark:text-slate-200">
              {{ item.desc }}
            </p>

            <div class="grid gap-3 sm:gap-4 sm:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
              <!-- bullet utama -->
              <ul class="list-disc list-inside space-y-1.5 text-[13px] sm:text-sm text-slate-600 dark:text-slate-300">
                <li v-for="(b, i) in item.bullets" :key="i">{{ b }}</li>
              </ul>

              <!-- box info kecil di samping -->
              <div
                class="rounded-2xl border border-dashed border-sky-300/60 bg-sky-50/80
                       p-3 text-[11px] space-y-1.5
                       dark:bg-sky-900/20 dark:border-sky-500/70 dark:text-sky-100"
              >
                <p class="font-semibold text-sky-800 dark:text-sky-100">
                  Gambaran singkat jalur
                </p>
                <p>{{ item.accent }}</p>
                <p class="text-[10px] text-sky-700/80 dark:text-sky-200/90">
                  Rekomendasi: baca brosur PMB terbaru untuk melihat contoh
                  kasus dan simulasi pembiayaan sesuai jalur ini.
                </p>
              </div>
            </div>

            <!-- CTA DAFTAR -->
            <div
              class="mt-4 pt-3 border-t border-dashed border-slate-200 dark:border-slate-700
                     flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3 justify-between"
            >
              <p class="text-[11px] text-slate-500 dark:text-slate-400 max-w-md">
                Setelah membaca persyaratan jalur ini, kamu bisa langsung menuju
                halaman formulir pendaftaran yang sesuai.
              </p>

              <!-- ✅ Tombol CTA: Mandiri biru, KIP & Yayasan hijau gradasi -->
              <a
                :href="item.registerUrl"
                class="inline-flex items-center gap-1.5
                       rounded-full px-4 py-2 text-[12px] sm:text-sm
                       border-0
                       transition-transform transition-colors
                       hover:-translate-y-px active:translate-y-0
                       focus:outline-none focus:ring-2 focus:ring-offset-2
                       focus:ring-offset-white dark:focus:ring-offset-slate-900"
                :class="
                  item.key === 'mandiri'
                    ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-[0_8px_20px_rgba(56,189,248,0.55)] hover:brightness-110 focus:ring-sky-400'
                    : item.key === 'kip'
                      ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-[0_8px_20px_rgba(16,185,129,0.45)] hover:brightness-110 focus:ring-emerald-400'
                      : 'bg-gradient-to-r from-emerald-500 to-sky-500 text-white shadow-[0_8px_20px_rgba(16,185,129,0.45)] hover:brightness-110 focus:ring-emerald-400'
                "
              >
                <ClipboardDocumentCheckIcon class="w-4 h-4" />
                <span>{{ item.buttonText }}</span>
              </a>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* animasi konten accordion */
.accordion-enter-active,
.accordion-leave-active {
  transition: all 0.18s ease-out;
}
.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
