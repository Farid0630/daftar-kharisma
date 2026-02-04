<!-- resources/js/components/landing/FloatingWhatsApp.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  // nomor WA dalam format internasional tanpa +
  // contoh: 6281234567890
  number: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    default:
      'Halo Kak, saya ingin bertanya tentang Penerimaan Mahasiswa Baru STMIK KHARISMA 😊',
  },
  // tampilkan bubble teks "Butuh bantuan? Chat CS PMB"
  showLabel: {
    type: Boolean,
    default: true,
  },
})

const whatsappUrl = computed(
  () =>
    `https://wa.me/${props.number}?text=${encodeURIComponent(props.message)}`,
)
</script>

<template>
  <a
    :href="whatsappUrl"
    target="_blank"
    rel="noopener"
    class="fixed bottom-4 right-4 md:bottom-6 md:right-6 z-40
           group inline-flex items-center gap-2"
  >
    <!-- bubble teks (muncul mulai sm, bisa dimatikan via showLabel) -->
    <span
      v-if="showLabel"
      class="hidden sm:inline-flex items-center gap-1.5
             rounded-full px-3 py-1.5
             text-[11px] font-medium
             bg-slate-900/90 text-emerald-100
             border border-emerald-500/40
             shadow-[0_12px_30px_rgba(15,23,42,0.8)]
             backdrop-blur-md
             group-hover:bg-slate-900 group-hover:border-emerald-400
             transition-all"
    >
      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
      <span>Butuh bantuan? Chat CS PMB</span>
    </span>

    <!-- icon WA -->
    <div class="relative">
      <!-- efek ping -->
      <span
        class="pointer-events-none absolute -top-1 -right-1 h-3 w-3
               rounded-full bg-emerald-400/70
               animate-ping"
      ></span>

      <div
        class="flex h-12 w-12 md:h-14 md:w-14 items-center justify-center
               rounded-full
               bg-gradient-to-br from-emerald-400 via-emerald-500 to-teal-500
               shadow-[0_0_25px_rgba(16,185,129,0.85)]
               border border-emerald-300/70
               text-white
               group-hover:scale-105 group-active:scale-95
               transition-transform duration-150"
      >
        <!-- logo WA SVG -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 32 32"
          class="w-6 h-6 md:w-7 md:h-7"
        >
          <path
            d="M16.01 5.333c-5.12 0-9.28 4.16-9.28 9.28 0 1.76.48 3.36 1.44 4.8L7.2 26.667l7.52-1.92c1.36.48 2.72.64 4.16.64 5.12 0 9.28-4.16 9.28-9.28 0-2.48-.96-4.8-2.72-6.56-1.76-1.76-4.16-2.88-6.56-2.88h-.77z"
            fill="currentColor"
            fill-opacity="0.95"
          />
          <path
            d="M13.973 11.013c-.187-.427-.373-.437-.56-.445l-.48-.01c-.166 0-.437.063-.667.313-.23.25-.877.858-.877 2.092 0 1.234.898 2.43 1.024 2.598.128.166 1.733 2.773 4.384 3.777 2.17.857 2.614.686 3.085.646.47-.042 1.52-.62 1.733-1.22.213-.603.213-1.12.15-1.234-.064-.114-.24-.177-.502-.313-.262-.135-1.52-.75-1.754-.836-.234-.084-.405-.135-.576.136-.17.27-.662.836-.81 1.007-.15.167-.299.187-.56.063-.262-.125-1.106-.408-2.11-1.302-.78-.694-1.306-1.55-1.457-1.82-.15-.27-.016-.416.11-.55.114-.115.262-.302.393-.453.13-.15.173-.25.26-.416.086-.167.045-.313-.02-.448-.064-.136-.567-1.412-.78-1.94z"
            fill="#f9fafb"
          />
        </svg>
      </div>
    </div>
  </a>
</template>
