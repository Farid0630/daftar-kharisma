<!-- resources/js/pages/pmb/mandiri/steps/StepMandiriAccount.vue -->
<script setup>
import { inject, computed } from 'vue'
import { KeyIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'

const form = inject('pmbForm')

const passwordStrength = computed(() => {
  const p = form.kata_sandi || ''
  let score = 0
  if (p.length >= 8) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++

  if (!p) return { label: ' ', percent: 0, barClass: '', textClass: '' }

  if (score <= 1) {
    return { label: 'Lemah', percent: 30, barClass: 'bg-rose-500', textClass: 'text-rose-500' }
  } else if (score === 2 || score === 3) {
    return { label: 'Sedang', percent: 60, barClass: 'bg-amber-400', textClass: 'text-amber-500' }
  }
  return { label: 'Kuat', percent: 90, barClass: 'bg-emerald-500', textClass: 'text-emerald-500' }
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-2">
      <div
        class="h-8 w-8 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600
               dark:bg-sky-500/20 dark:text-sky-300"
      >
        <KeyIcon class="w-4 h-4" />
      </div>
      <div>
        <h2 class="text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200">
          Akun PMB & Kontak Pendaftaran Mandiri
        </h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Akun ini dipakai untuk login portal PMB, menerima informasi seleksi, dan pengumuman hasil.
        </p>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 md:gap-6">
      <!-- Username -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Username PMB <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.username"
          type="text"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-slate-300
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Minimal 4–20 karakter"
          required
        />
        <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">
          Gunakan kombinasi huruf/angka yang mudah diingat. Hindari spasi.
        </p>
      </div>

      <!-- Email (wajib untuk Mandiri) -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Alamat Email <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <span
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3
                   text-slate-400 dark:text-slate-300"
          >
            <EnvelopeIcon class="w-4 h-4" />
          </span>
          <input
            v-model="form.alamat_email"
            type="email"
            class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                   bg-white dark:bg-slate-900/80
                   pl-9 pr-3 py-2.5 text-sm
                   placeholder-slate-400 dark:placeholder-slate-300
                   focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                   dark:focus:ring-sky-500"
            placeholder="contoh: nama@email.com"
            required
          />
        </div>
        <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">
          Email dipakai untuk notifikasi akun dan informasi seleksi.
        </p>
      </div>

      <!-- Nomor WhatsApp -->
      <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Nomor WhatsApp <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.nomor_hp"
          type="tel"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-slate-300
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="08xxxxxxxxxx"
          required
        />
        <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">
          Pastikan nomor aktif. Informasi jadwal, pembayaran, dan pengumuman dapat dikirim lewat WhatsApp.
        </p>
      </div>

      <!-- Kata sandi -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Kata Sandi <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.kata_sandi"
          type="password"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-slate-300
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Minimal 8 karakter (huruf besar, angka, simbol)"
          required
        />

        <!-- indikator kekuatan kata sandi -->
        <div v-if="form.kata_sandi" class="mt-1 flex items-center gap-2">
          <div class="flex-1 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="passwordStrength.barClass"
              :style="{ width: passwordStrength.percent + '%' }"
            ></div>
          </div>
          <span class="text-[10px] font-medium" :class="passwordStrength.textClass">
            {{ passwordStrength.label }}
          </span>
        </div>
      </div>

      <!-- Konfirmasi Kata Sandi -->
      <div>
        <label class="block text-xs font-medium text-slate-700 dark:text-slate-200 mb-1">
          Konfirmasi Kata Sandi <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.konfirmasi_kata_sandi"
          type="password"
          class="w-full rounded-xl border border-slate-300/80 dark:border-slate-600
                 bg-white dark:bg-slate-900/80
                 px-3 py-2.5 text-sm
                 placeholder-slate-400 dark:placeholder-slate-300
                 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                 dark:focus:ring-sky-500"
          placeholder="Ulangi kata sandi"
          required
        />
        <p
          v-if="form.konfirmasi_kata_sandi && form.kata_sandi !== form.konfirmasi_kata_sandi"
          class="mt-1 text-[11px] text-rose-500"
        >
          Kata sandi dan konfirmasi belum sama.
        </p>
      </div>
    </div>

    <p class="text-[11px] text-slate-500 dark:text-slate-400">
      Kata sandi digunakan untuk login ke portal PMB dan mengecek status pendaftaran Mandiri.
      Jangan membagikan kata sandi ke orang lain.
    </p>
  </div>
</template>
