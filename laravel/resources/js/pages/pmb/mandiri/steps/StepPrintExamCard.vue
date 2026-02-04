<script setup>
import { inject, computed } from 'vue'
import {
  IdentificationIcon,
  PrinterIcon,
  ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

import jsPDF from 'jspdf'

const form = inject('pmbForm')

// opsional: info siap cetak
const siapCetak = computed(
  () =>
    form.status_pembayaran === 'paid' &&
    form.otp_terverifikasi &&
    form.berkas_terunggah,
)

const printCard = () => {
  window.print()
}

const downloadCard = () => {
  try {
    const pdf = new jsPDF('p', 'mm', 'a4')
    const pageWidth = pdf.internal.pageSize.getWidth()
    const centerX = pageWidth / 2

    let y = 16

    // === HEADER CAMPUS (DIPERBESAR) ===
    pdf.setFont('helvetica', 'bold')
    pdf.setFontSize(18)
    pdf.text('STMIK KHARISMA MAKASSAR', centerX, y, { align: 'center' })

    y += 8
    pdf.setFontSize(13)
    pdf.setFont('helvetica', 'normal')
    pdf.text('Kartu Ujian Penerimaan Mahasiswa Baru', centerX, y, {
      align: 'center',
    })

    y += 6
    pdf.setFontSize(11)
    pdf.text('Tahun Akademik 2026 / 2027', centerX, y, { align: 'center' })

    // garis tipis
    y += 5
    pdf.setLineWidth(0.3)
    pdf.line(18, y, pageWidth - 18, y)

    // === KOTAK KARTU ===
    const cardX = 15
    const cardY = y + 7
    const cardW = pageWidth - 30
    const cardH = 105 // ✅ sedikit ditambah agar muat Prodi 1 & 2

    pdf.setLineWidth(0.45)
    pdf.roundedRect(cardX, cardY, cardW, cardH, 3, 3)

    // === HEADER KARTU (JALUR + DIPERBESAR) ===
    let headY = cardY + 10
    pdf.setFont('helvetica', 'bold')
    pdf.setFontSize(13)
    pdf.text('KARTU UJIAN – JALUR MANDIRI', cardX + 6, headY)

    headY += 6
    pdf.setFont('helvetica', 'normal')
    pdf.setFontSize(10.5)
    pdf.text('Harap tunjukkan kartu ini saat pelaksanaan ujian seleksi.', cardX + 6, headY)

    // garis kecil dalam kartu
    const innerLineY = headY + 4
    pdf.setLineWidth(0.25)
    pdf.line(cardX + 6, innerLineY, cardX + cardW - 6, innerLineY)

    // === DATA ===
    const leftX = cardX + 6
    const rightX = cardX + cardW / 2 + 2

    let dataY = innerLineY + 8
    let dataRightY = innerLineY + 8

    const nama = form.nama_lengkap || '-'
    const gender =
      form.jenis_kelamin === 'P'
        ? 'Perempuan'
        : form.jenis_kelamin === 'L'
          ? 'Laki-laki'
          : '-'

    // ✅ PRODI 1 & 2 (kompatibilitas: kalau program_studi_1 kosong pakai program_studi)
    const prodi1 = form.program_studi_1 || form.program_studi || '-'
    const prodi2 = form.program_studi_2 || '-'

    const sekolah = form.nama_sekolah || '-'
    const hp = form.nomor_hp || '-'
    const email = form.alamat_email || '-'
    const statusBayar = form.status_pembayaran === 'paid' ? 'LUNAS' : 'Belum Lunas'

    pdf.setFontSize(11)

    // helper row kiri
    const addRowLeft = (label, value) => {
      pdf.setFont('helvetica', 'bold')
      pdf.text(label, leftX, dataY)
      pdf.setFont('helvetica', 'normal')
      pdf.text(`: ${String(value)}`, leftX + 35, dataY)
      dataY += 7
    }

    // helper row kanan
    const addRowRight = (label, value) => {
      pdf.setFont('helvetica', 'bold')
      pdf.text(label, rightX, dataRightY)
      pdf.setFont('helvetica', 'normal')
      pdf.text(`: ${String(value)}`, rightX + 30, dataRightY)
      dataRightY += 7
    }

    addRowLeft('Nama Lengkap', nama)
    addRowLeft('Jenis Kelamin', gender)

    // ✅ tampilkan Prodi 1 & 2
    addRowLeft('Prodi Pilihan 1', prodi1)
    addRowLeft('Prodi Pilihan 2', prodi2)

    addRowLeft('Sekolah Asal', sekolah)

    addRowRight('No. HP', hp)
    addRowRight('Email', email)
    addRowRight('Status Bayar', statusBayar)

    // === FOTO (opsional) ===
    if (form.foto_preview) {
      try {
        const imgData = form.foto_preview
        const isPng = imgData.startsWith('data:image/png')
        const isJpg =
          imgData.startsWith('data:image/jpeg') || imgData.startsWith('data:image/jpg')

        if (isPng || isJpg) {
          const format = isPng ? 'PNG' : 'JPEG'

          const fotoW = 30
          const fotoH = 38
          const fotoX = cardX + cardW - fotoW - 8
          const fotoY = cardY + cardH - fotoH - 12

          pdf.addImage(imgData, format, fotoX, fotoY, fotoW, fotoH)
          pdf.rect(fotoX, fotoY, fotoW, fotoH)
        }
      } catch (e) {
        console.error('Gagal memasukkan foto ke PDF:', e)
      }
    }

    // === CATATAN BAWAH ===
    let footerY = cardY + cardH + 10
    pdf.setFontSize(9.5)
    pdf.setFont('helvetica', 'normal')
    pdf.text(
      'Harap membawa kartu ini (cetak / PDF di HP) dan KTP / identitas lain saat ujian seleksi.',
      cardX,
      footerY,
    )

    pdf.save('kartu-ujian-pmb.pdf')
  } catch (e) {
    console.error('Gagal membuat PDF kartu ujian:', e)
    alert('Maaf, kartu ujian gagal di-download. Coba lagi beberapa saat.')
  }
}
</script>

<template>
  <div id="print-section" class="space-y-4 print:p-0 print:bg-white">
    <div class="flex items-center gap-2 print:hidden">
      <div
        class="h-8 w-8 flex items-center justify-center rounded-xl
               bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300"
      >
        <IdentificationIcon class="w-4 h-4" />
      </div>
      <h2 class="text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200">
        Cetak Kartu Ujian
      </h2>
    </div>

    <p class="text-xs text-slate-600 dark:text-slate-300 print:hidden">
      Klik <strong>Cetak</strong> untuk print / simpan sebagai PDF (via browser),
      atau <strong>Download</strong> untuk unduh PDF otomatis.
    </p>

    <p v-if="!siapCetak" class="text-[11px] text-amber-500 mb-1 print:hidden">
      Disarankan mencetak kartu setelah OTP terverifikasi, pembayaran LUNAS,
      dan berkas sudah terunggah.
    </p>

    <!-- KARTU UJIAN -->
    <div
      id="exam-card"
      class="rounded-2xl border border-sky-500/40 bg-slate-50/80 dark:bg-slate-900/80
             px-6 py-5 text-sm text-slate-800 dark:text-slate-100
             shadow-[0_10px_30px_rgba(15,23,42,0.45)]
             print:shadow-none print:border print:border-slate-300
             print:bg-white print:text-black"
    >
      <div class="flex justify-between items-start mb-4">
        <div class="space-y-1">
          <p class="text-base md:text-lg font-semibold tracking-wide">
            Kartu Ujian – Jalur Mandiri
          </p>

          <p class="text-lg md:text-xl font-bold leading-tight">
            STMIK KHARISMA MAKASSAR
          </p>

          <p class="text-sm md:text-base text-slate-600 dark:text-slate-300 print:text-slate-700">
            Tahun Akademik 2026/2027
          </p>
        </div>
      </div>

      <!-- BLOK DATA + FOTO -->
      <div class="grid sm:grid-cols-3 gap-5 items-start">
        <!-- Data (2 kolom) -->
        <div class="sm:col-span-2 grid sm:grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-slate-500">Nama Lengkap</p>
            <p class="font-semibold text-base md:text-lg">{{ form.nama_lengkap || '-' }}</p>

            <p class="mt-3 text-xs text-slate-500">Jenis Kelamin</p>
            <p class="text-base">
              {{
                form.jenis_kelamin === 'P'
                  ? 'Perempuan'
                  : form.jenis_kelamin === 'L'
                    ? 'Laki-laki'
                    : '-'
              }}
            </p>

            <!-- ✅ PRODI 1 -->
            <p class="mt-3 text-xs text-slate-500">Program Studi (Pilihan 1)</p>
            <p class="text-base">{{ form.program_studi_1 || form.program_studi || '-' }}</p>

            <!-- ✅ PRODI 2 -->
            <p class="mt-3 text-xs text-slate-500">Program Studi (Pilihan 2)</p>
            <p class="text-base">{{ form.program_studi_2 || '-' }}</p>

            <p class="mt-3 text-xs text-slate-500">Sekolah Asal</p>
            <p class="text-base">{{ form.nama_sekolah || '-' }}</p>
          </div>

          <div>
            <p class="text-xs text-slate-500">No. HP</p>
            <p class="text-base">{{ form.nomor_hp || '-' }}</p>

            <p class="mt-3 text-xs text-slate-500">Email</p>
            <p class="text-base break-words">{{ form.alamat_email || '-' }}</p>

            <p class="mt-3 text-xs text-slate-500">Status Bayar</p>
            <p class="text-base font-semibold">
              {{ form.status_pembayaran === 'paid' ? 'LUNAS' : 'Belum Lunas' }}
            </p>
          </div>
        </div>

        <!-- Foto -->
        <div class="flex sm:justify-end">
          <div
            class="w-28 h-36 md:w-32 md:h-40 rounded-md border border-slate-300 bg-slate-100
                   overflow-hidden flex items-center justify-center"
          >
            <img
              v-if="form.foto_preview"
              :src="form.foto_preview"
              alt="Foto peserta"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-xs text-slate-400 text-center px-2">
              Foto belum diunggah
            </span>
          </div>
        </div>
      </div>

      <p class="mt-4 text-sm text-slate-600 dark:text-slate-300 print:text-slate-700">
        Tunjukkan kartu ini (cetak atau PDF di HP) saat pelaksanaan ujian seleksi.
      </p>
    </div>

    <!-- Tombol aksi (non-print) -->
    <div class="flex flex-wrap gap-3 print:hidden">
      <button
        type="button"
        @click="printCard"
        class="inline-flex items-center gap-2 rounded-full px-6 md:px-8 py-2.5
               text-sm font-semibold tracking-wide
               bg-gradient-to-r from-sky-500 to-blue-500
               text-white shadow-[0_12px_30px_rgba(37,99,235,0.45)]
               hover:from-sky-400 hover:to-blue-500
               focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2
               focus:ring-offset-slate-100 dark:focus:ring-offset-slate-900
               transition-all"
      >
        <PrinterIcon class="w-5 h-5" />
        Cetak Kartu Ujian (print / PDF)
      </button>

      <button
        type="button"
        @click="downloadCard"
        class="inline-flex items-center gap-2 rounded-full px-6 md:px-8 py-2.5
               text-sm font-semibold tracking-wide
               bg-gradient-to-r from-emerald-500 to-teal-500
               text-white shadow-[0_12px_30px_rgba(16,185,129,0.45)]
               hover:from-emerald-400 hover:to-teal-500
               focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2
               focus:ring-offset-slate-100 dark:focus:ring-offset-slate-900
               transition-all"
      >
        <ArrowDownTrayIcon class="w-5 h-5" />
        Download Kartu Ujian (PDF)
      </button>
    </div>
  </div>
</template>

<style>
@media print {
  @page {
    size: A4 portrait;
    margin: 10mm;
  }

  body {
    margin: 0;
    background: #ffffff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  body * {
    visibility: hidden;
  }

  #print-section,
  #print-section * {
    visibility: visible;
  }

  #print-section {
    position: absolute;
    inset: 0;
    margin: 0;
    padding: 12mm;
    box-sizing: border-box;
    background: #ffffff;
  }

  #exam-card {
    max-width: 100%;
    margin: 0 auto;
    box-shadow: none !important;
    border-radius: 0 !important;
  }
}
</style>
