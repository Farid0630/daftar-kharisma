<!-- resources/js/components/layout/AppHeader.vue -->
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import {
  ArrowRightOnRectangleIcon,
  MoonIcon,
  SunIcon,
  Bars3Icon,
  XMarkIcon,
  LockClosedIcon,
  UserPlusIcon,
  BanknotesIcon,
  HeartIcon,
} from '@heroicons/vue/24/outline'

const navItems = [
  { id: 'home', label: 'Beranda' },
  { id: 'jalur', label: 'Jalur & Beasiswa' },
  { id: 'mitra', label: 'Mitra' },
  { id: 'lokasi', label: 'Lokasi' },
]

const logo = '/storage/landing/logo_kharisma.png'
const isDark = ref(false)
const isScrolled = ref(false)
const isMobileNavOpen = ref(false)
const isRegisterModalOpen = ref(false)

// LOGIN URL
const loginOption = {
  url: '/login',
}

// OPSI PENDAFTARAN
const registerOptions = [
  {
    key: 'mandiri',
    label: 'Jalur Mandiri',
    badge: 'Umum',
    tagline: 'Jalur reguler untuk semua calon mahasiswa.',
    desc: 'Pendaftaran reguler dengan seleksi ujian tulis atau nilai rapor.',
    gradient: 'from-sky-500 via-sky-600 to-blue-500',
    accentText: 'text-sky-600 dark:text-sky-300',
    registerUrl: '/pmb/register',
    icon: UserPlusIcon,
  },
  {
    key: 'kip',
    label: 'Jalur EXPO Kuliah',
    badge: '...',
    tagline: 'Untuk siswa berprestasi dengan keterbatasan ekonomi.',
    desc: 'Biaya kuliah ditanggung sesuai ketentuan resmi program EXPO Kuliah.',
    gradient: 'from-amber-500 via-amber-600 to-orange-500',
    accentText: 'text-amber-500 dark:text-amber-300',
    registerUrl: '/pmb/register/kip',
    icon: BanknotesIcon,
  },
  {
    key: 'yayasan',
    label: 'Beasiswa Yayasan',
    badge: 'Prestasi & Mitra',
    tagline: 'Kerja sama sekolah / industri mitra.',
    desc: 'Beasiswa dari yayasan dan mitra industri bagi siswa berprestasi atau anak karyawan mitra.',
    gradient: 'from-emerald-500 via-emerald-600 to-teal-500',
    accentText: 'text-emerald-500 dark:text-emerald-300',
    registerUrl: '/pmb/register/yayasan',
    icon: HeartIcon,
  },
]

const applyTheme = (enabled) => {
  if (typeof document === 'undefined') return
  const root = document.documentElement

  if (enabled) {
    root.classList.add('dark')
  } else {
    root.classList.remove('dark')
  }

  root.setAttribute('data-theme', enabled ? 'dark' : 'light')
  localStorage.setItem('theme', enabled ? 'dark' : 'light')
  isDark.value = enabled
}

const toggleTheme = () => {
  applyTheme(!isDark.value)
}

const handleScroll = () => {
  if (typeof window === 'undefined') return
  isScrolled.value = window.scrollY > 8
}

/**
 * Klik menu navbar:
 * - Kalau lagi di '/' → scroll halus ke section
 * - Kalau lagi di halaman lain (misal /pmb/register) → redirect ke /#id
 */
const handleNavClick = (id) => {
  if (typeof window === 'undefined' || typeof document === 'undefined') return

  const currentPath = window.location.pathname || '/'

  if (currentPath === '/') {
    // Sedang di landing page → scroll
    const el = document.getElementById(id)
    if (el) {
      const offset = 80 // tinggi header
      const top = el.getBoundingClientRect().top + window.scrollY - offset
      window.scrollTo({ top, behavior: 'smooth' })
    }
  } else {
    // Sedang di halaman lain → pindah ke landing + anchor
    window.location.href = `/#${id}`
  }

  isMobileNavOpen.value = false
}

const openRegisterModal = () => {
  isRegisterModalOpen.value = true
}

const closeRegisterModal = () => {
  isRegisterModalOpen.value = false
}

onMounted(() => {
  const saved = localStorage.getItem('theme')
  if (saved === 'dark') {
    applyTheme(true)
  } else if (saved === 'light') {
    applyTheme(false)
  } else {
    const systemDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches
    applyTheme(systemDark)
  }

  window.addEventListener('scroll', handleScroll, { passive: true })
  handleScroll()
})

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('scroll', handleScroll)
  }
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.18s ease-out;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>

<template>
  <header
    :class="[
      'sticky top-0 z-40 w-full border-b transition-all duration-300 backdrop-blur-xl',
      isScrolled
        ? 'bg-sky-50/80 dark:bg-slate-950/80 shadow-[0_8px_30px_rgba(15,23,42,0.7)] border-sky-100/70 dark:border-slate-800/80'
        : 'bg-gradient-to-r from-sky-50 via-blue-100 to-sky-200 dark:from-blue-950 dark:via-blue-900 dark:to-sky-900 border-blue-200/60 dark:border-blue-800/60 shadow-lg',
    ]"
  >
    <!-- dekorasi glow halus -->
    <div
      class="pointer-events-none absolute inset-0 -z-10 opacity-70"
      aria-hidden="true"
    >
      <div
        class="absolute -right-16 -top-10 h-28 w-28 rounded-full bg-sky-300/40 blur-3xl dark:bg-sky-500/50"
      />
      <div
        class="absolute left-8 -bottom-8 h-24 w-24 rounded-full bg-blue-300/30 blur-3xl dark:bg-blue-600/40"
      />
    </div>

    <div class="relative max-w-6xl mx-auto px-3 sm:px-4 md:px-6">
      <div class="h-14 sm:h-16 flex items-center justify-between gap-3">
        <!-- LOGO / BRAND -->
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">

          <div
  class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl
         bg-white/80 dark:bg-slate-900/70
         border border-sky-100/70 dark:border-slate-700/80
         shadow-md shadow-sky-500/20 overflow-hidden"
>
  <img
    :src="logo"
    alt="Logo Kharisma College"
    class="h-full w-full object-contain p-1"
    draggable="false"
  />
</div>

          <div class="flex flex-col leading-tight">
            <span
              class="text-[13px] sm:text-sm font-semibold tracking-tight text-slate-900 dark:text-slate-50 truncate"
            >
              KHARISMA College
            </span>
            <span
              class="text-[11px] text-slate-600 dark:text-slate-400 truncate"
            >
              Portal Penerimaan Mahasiswa Baru
            </span>
          </div>
        </div>

        <!-- NAV DESKTOP -->
        <nav class="hidden md:flex items-center gap-1.5">
          <button
            v-for="item in navItems"
            :key="item.id"
            type="button"
            @click="handleNavClick(item.id)"
            class="relative group flex items-center px-3.5 py-1.5 rounded-full text-[13px] font-medium tracking-tight text-slate-800/90 dark:text-slate-100/90 transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-400/80 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 dark:focus-visible:ring-offset-slate-950"
          >
            <!-- background pill -->
            <span
              class="pointer-events-none absolute inset-0 rounded-full bg-sky-500/0 dark:bg-sky-500/0 group-hover:bg-sky-500/10 dark:group-hover:bg-sky-500/15 group-active:bg-sky-500/20 transition-all duration-200"
            ></span>

            <!-- label -->
            <span class="relative z-10">
              {{ item.label }}
            </span>

            <!-- underline glow -->
            <span
              class="pointer-events-none absolute left-3 right-3 -bottom-1 h-[2px] rounded-full bg-gradient-to-r from-sky-400/0 via-sky-400/80 to-sky-400/0 opacity-0 scale-x-50 group-hover:opacity-100 group-hover:scale-x-100 transition-all duration-200 origin-center"
            ></span>
          </button>
        </nav>

        <!-- KANAN: TOGGLE THEME + BUTTON + BURGER -->
        <div class="flex items-center gap-1.5 sm:gap-2">
          <!-- CAPSULE THEME + CTA (desktop) -->
          <div
            class="hidden sm:inline-flex items-center gap-1.5 rounded-full border border-white/60 dark:border-slate-700/70 bg-white/60 dark:bg-slate-900/70 px-2.5 py-1.5 backdrop-blur-md shadow-sm shadow-sky-200/50 dark:shadow-slate-900/40"
          >
            <!-- Night / Day -->
            <button
              type="button"
              @click="toggleTheme"
              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] sm:text-xs text-blue-900 dark:text-blue-50 hover:bg-white/80 dark:hover:bg-slate-800/80 transition-colors"
            >
              <MoonIcon
                v-if="isDark"
                class="w-4 h-4 text-sky-400 dark:text-sky-300"
              />
              <SunIcon v-else class="w-4 h-4 text-yellow-400" />
              <span class="whitespace-nowrap">
                {{ isDark ? 'Night UI' : 'Day UI' }}
              </span>
            </button>

            <!-- garis pemisah -->
            <span
              class="h-6 w-px bg-blue-300/60 dark:bg-slate-600/80"
            ></span>

            <!-- CTA DAFTAR (open modal) -->
            <button
              type="button"
              @click="openRegisterModal"
              class="inline-flex items-center gap-1.5 px-3 py-1 text-[11px] sm:text-xs md:text-sm rounded-full border border-sky-500/80 bg-sky-500 text-slate-950 hover:bg-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.6)] dark:bg-sky-500/20 dark:text-sky-50 dark:hover:bg-sky-400/30 transition-colors"
            >
              <ArrowRightOnRectangleIcon class="w-4 h-4" />
              <span class="whitespace-nowrap">Daftar</span>
            </button>
          </div>

          <!-- ICON THEME (mobile) -->
          <button
            type="button"
            @click="toggleTheme"
            class="sm:hidden flex items-center justify-center h-8 w-8 rounded-full bg-white/70 dark:bg-slate-900/80 border border-sky-100/70 dark:border-slate-700/80 text-blue-900 dark:text-blue-50 shadow-sm shadow-slate-200/70 dark:shadow-slate-900/70"
          >
            <MoonIcon v-if="isDark" class="w-4 h-4 text-sky-400" />
            <SunIcon v-else class="w-4 h-4 text-yellow-400" />
          </button>

          <!-- CTA DAFTAR (mobile icon) -->
          <button
            type="button"
            @click="openRegisterModal"
            class="sm:hidden flex items-center justify-center h-8 w-8 rounded-full bg-sky-500 text-white shadow-[0_0_12px_rgba(56,189,248,0.7)] border border-sky-400/70"
          >
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
          </button>

          <!-- BURGER MENU -->
          <button
            type="button"
            class="inline-flex items-center justify-center md:hidden h-9 w-9 rounded-full bg-white/70 dark:bg-slate-900/80 border border-sky-100/70 dark:border-slate-700/80 text-slate-800 dark:text-slate-100 shadow-sm shadow-slate-200/70 dark:shadow-slate-900/70"
            @click="isMobileNavOpen = !isMobileNavOpen"
          >
            <Bars3Icon v-if="!isMobileNavOpen" class="w-5 h-5" />
            <XMarkIcon v-else class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- NAV MOBILE DROPDOWN -->
    <div
      v-if="isMobileNavOpen"
      class="md:hidden border-t border-sky-100/70 dark:border-slate-800/80 bg-sky-50/95 dark:bg-slate-950/95 backdrop-blur-xl"
    >
      <nav class="max-w-6xl mx-auto px-3 py-2 flex flex-col gap-0.5">
        <button
          v-for="item in navItems"
          :key="item.id + '-mobile'"
          type="button"
          @click="handleNavClick(item.id)"
          class="flex items-center justify-between px-3 py-2 rounded-2xl text-[13px] font-medium text-slate-800 dark:text-slate-100 hover:bg-sky-100 dark:hover:bg-slate-800 transition-colors"
        >
          <span>{{ item.label }}</span>
          <span
            class="h-1.5 w-1.5 rounded-full bg-sky-500/80 dark:bg-sky-400/90"
          />
        </button>
      </nav>
    </div>

    <!-- garis glow di bawah header -->
    <div
      class="h-0.5 w-full bg-gradient-to-r from-transparent via-sky-400/70 to-transparent opacity-90"
    ></div>
  </header>

  <!-- ================= MODAL LOGIN / DAFTAR ================= -->
  <Transition name="modal-fade">
    <div
      v-if="isRegisterModalOpen"
      class="fixed inset-0 z-50"
    >
      <!-- overlay -->
      <div
        class="absolute inset-0 bg-slate-900/70 backdrop-blur-md"
        @click="closeRegisterModal"
      ></div>

      <!-- center container -->
      <div
        class="relative flex min-h-screen items-center justify-center
               p-3 sm:p-4"
      >
        <!-- CARD MODAL UTAMA -->
        <div
          class="relative w-full max-w-5xl
                 rounded-2xl sm:rounded-3xl
                 border border-slate-200/80 dark:border-slate-700/80
                 bg-slate-50/98 dark:bg-slate-950/98
                 shadow-[0_20px_60px_rgba(15,23,42,0.85)]
                 px-3.5 sm:px-5 md:px-6 py-4 sm:py-5
                 max-h-[calc(100vh-2.5rem)] overflow-y-auto"
        >
          <!-- HEADER MODAL -->
          <div class="flex items-start justify-between gap-3 mb-4 sm:mb-5">
            <div class="space-y-1">
              <p
                class="text-[11px] font-semibold uppercase tracking-[0.18em]
                       text-sky-600 dark:text-sky-300"
              >
                PORTAL PMB KHARISMA
              </p>
              <h3
                class="text-sm sm:text-lg md:text-xl font-semibold
                       text-slate-900 dark:text-slate-50"
              >
                Mau login atau daftar akun baru?
              </h3>
              <p
                class="mt-1 text-[11px] sm:text-xs
                       text-slate-600 dark:text-slate-400"
              >
                Kalau sudah pernah daftar, pilih
                <strong>Login Akun PMB</strong>. Jika belum, pilih salah satu
                <strong>Jalur Pendaftaran Baru</strong> di sebelah kanan.
              </p>
            </div>

            <button
              type="button"
              @click="closeRegisterModal"
              class="flex h-8 w-8 items-center justify-center
                     rounded-full border border-slate-200 dark:border-slate-700
                     bg-white/80 dark:bg-slate-900/80
                     text-slate-500 dark:text-slate-300
                     hover:bg-slate-100 dark:hover:bg-slate-800
                     transition-colors shrink-0"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- ISI MODAL -->
          <div
            class="grid gap-4 md:gap-5
                   md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]"
          >
            <!-- LOGIN (KIRI) -->
            <div
              class="relative rounded-3xl border border-slate-200/80 dark:border-slate-700
                     bg-gradient-to-br from-slate-50 via-white to-slate-100
                     dark:from-slate-950 dark:via-slate-900 dark:to-slate-950
                     text-slate-900 dark:text-slate-50
                     px-4 sm:px-5 py-4 sm:py-5
                     shadow-[0_16px_40px_rgba(15,23,42,0.85)]
                     overflow-hidden"
            >
              <!-- dekorasi glow -->
              <div
                class="pointer-events-none absolute -left-10 -top-10 h-24 w-24
                       rounded-full bg-sky-400/20 dark:bg-sky-500/25 blur-3xl"
              ></div>
              <div
                class="pointer-events-none absolute -right-16 -bottom-10 h-28 w-28
                       rounded-full bg-cyan-300/25 dark:bg-cyan-400/20 blur-3xl"
              ></div>

              <!-- konten login -->
              <div class="relative flex flex-col gap-3 md:gap-4 h-full">
                <!-- judul -->
                <div class="flex items-start gap-3 sm:gap-4">
                  <div
                    class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center
                           rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-500
                           text-slate-50 shadow-md shadow-sky-500/60 shrink-0"
                  >
                    <LockClosedIcon class="w-5 h-5" />
                  </div>

                  <div class="space-y-0.5">
                    <p
                      class="text-[10px] sm:text-[11px] font-semibold uppercase
                             tracking-[0.22em]
                             text-sky-700 dark:text-sky-300"
                    >
                      SUDAH PUNYA AKUN?
                    </p>
                    <h4
                      class="text-sm sm:text-base md:text-lg font-semibold
                             text-slate-900 dark:text-slate-50"
                    >
                      Login Akun PMB
                    </h4>
                    <p
                      class="text-[11px] sm:text-xs font-medium
                             text-sky-700 dark:text-sky-200"
                    >
                      Untuk kamu yang sudah punya akun di jalur apa pun.
                    </p>
                  </div>
                </div>

                <!-- deskripsi -->
                <p
                  class="text-[11px] sm:text-xs leading-relaxed
                         text-slate-600 dark:text-slate-300"
                >
                  Masuk untuk melanjutkan pendaftaran, mengunggah berkas,
                  atau melihat status seleksi.
                </p>

                <!-- CTA login -->
                <div
                  class="mt-6 sm:mt-8 md:mt-auto
                         flex flex-col items-center gap-2
                         text-center"
                >
                  <a
                    :href="loginOption.url"
                    class="inline-flex items-center justify-center gap-2
                           px-5 sm:px-7 md:px-9
                           py-2.5 sm:py-3
                           rounded-full
                           bg-gradient-to-r from-sky-500 to-cyan-400
                           text-[13px] sm:text-[14px] md:text-[16px]
                           text-slate-950 font-semibold
                           shadow-[0_0_22px_rgba(56,189,248,0.95)]
                           hover:brightness-110 hover:-translate-y-[1px]
                           active:translate-y-0
                           transition-all
                           w-full sm:w-auto mx-auto"
                  >
                    <ArrowRightOnRectangleIcon class="w-5 h-5" />
                    <span>Masuk ke Akun PMB</span>
                  </a>

                  <span
                    class="max-w-xs mx-auto
                           text-[11px] sm:text-[12px] md:text-[13px]
                           text-sky-700/80 dark:text-sky-200/90"
                  >
                    Kamu akan diarahkan ke halaman login.
                  </span>
                </div>
              </div>
            </div>

            <!-- PENDAFTARAN BARU (KANAN) -->
            <div class="space-y-3 sm:space-y-4 mt-1 md:mt-0">
              <p
                class="text-[11px] sm:text-xs font-semibold uppercase tracking-[0.18em]
                       text-slate-500 dark:text-slate-400"
              >
                Jalur pendaftaran baru
              </p>

              <div
                v-for="opt in registerOptions"
                :key="opt.key"
                class="rounded-2xl border border-slate-200/90 dark:border-slate-700/80
                       bg-slate-50/95 dark:bg-slate-900
                       px-3.5 sm:px-4 py-3.5 sm:py-4
                       flex flex-col gap-2.5"
              >
                <div class="flex items-start gap-3">
                  <div
                    class="flex h-10 w-10 items-center justify-center rounded-2xl
                           text-slate-50 bg-gradient-to-br"
                    :class="opt.gradient"
                  >
                    <component :is="opt.icon" class="w-5 h-5" />
                  </div>
                  <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <p
                        class="text-sm sm:text-base font-semibold
                               text-slate-900 dark:text-slate-50"
                      >
                        {{ opt.label }}
                      </p>
                      <span
                        class="inline-flex items-center rounded-full
                               px-2 py-0.5 text-[10px] font-medium
                               bg-slate-100 dark:bg-slate-800
                               text-slate-600 dark:text-slate-300"
                      >
                        {{ opt.badge }}
                      </span>
                    </div>
                    <p
                      class="text-[11px] sm:text-xs font-medium mt-0.5"
                      :class="opt.accentText"
                    >
                      {{ opt.tagline }}
                    </p>
                    <p
                      class="mt-1 text-[11px] sm:text-xs
                             text-slate-600 dark:text-slate-300"
                    >
                      {{ opt.desc }}
                    </p>
                  </div>
                </div>

                <div class="flex justify-end">
                  <a
                    :href="opt.registerUrl"
                    class="inline-flex items-center justify-center gap-1.5
                           px-3.5 py-1.5 rounded-full
                           text-[11px] sm:text-xs font-medium
                           text-slate-50
                           shadow-[0_0_14px_rgba(56,189,248,0.65)]
                           hover:brightness-110 hover:-translate-y-[1px]
                           active:translate-y-0
                           transition-all bg-gradient-to-br"
                    :class="opt.gradient"
                  >
                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                    <span>Daftar Jalur Ini</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
          <!-- end grid isi modal -->
        </div>
      </div>
    </div>
  </Transition>
</template>
