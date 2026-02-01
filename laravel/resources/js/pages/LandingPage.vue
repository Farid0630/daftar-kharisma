<script setup>
import AppHeader from '../components/layout/AppHeader.vue'
import AppFooter from '../components/layout/AppFooter.vue'

import LandingHero from '../components/landing/LandingHero.vue'
import LandingProfil from '../components/landing/LandingProfil.vue'
import LandingJalurBeasiswa from '../components/landing/LandingJalurBeasiswa.vue'
import LandingMitra from '../components/landing/LandingMitra.vue'
import LandingLokasi from '../components/landing/LandingLokasi.vue'

import FloatingWhatsApp from '../components/landing/FloatingWhatsApp.vue'
</script>

<template>
  <div
    class="min-h-screen flex flex-col
           bg-slate-50 text-slate-900
           dark:bg-slate-950 dark:text-slate-50
           text-[15px] sm:text-[26px]"
  >
    <AppHeader />

    <main class="flex-1">
      <section id="home" class="scroll-mt-24">
        <LandingHero />
      </section>

      <LandingProfil />

      <section id="jalur" class="scroll-mt-24">
        <LandingJalurBeasiswa />
      </section>

      <section id="mitra" class="scroll-mt-24">
        <LandingMitra />
      </section>

      <section id="lokasi" class="scroll-mt-24">
        <LandingLokasi />
      </section>
    </main>

    <AppFooter />

    <!-- Floating WhatsApp -->
    <FloatingWhatsApp
      number="6285157599524"
      message="Halo Kak, saya ingin bertanya tentang Penerimaan Mahasiswa Baru STMIK KHARISMA ?"
      :showLabel="true"
    />
  </div>
</template>
