<script setup>
import { onMounted, ref, computed } from "vue";
import AppSidebar from "@/components/layout/AppSidebar.vue";

const csrf = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

const loading = ref(true);
const error = ref("");

const user = ref(null);
const accounts = ref([]);
const selected = ref(null);

const val = (v) => {
  if (v === null || v === undefined) return "-";
  const s = String(v).trim();
  return s.length ? s : "-";
};

const formatDate = (v) => {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return val(v);
  return d.toLocaleDateString("id-ID", { year: "numeric", month: "long", day: "2-digit" });
};

const jalurLabel = (v) => {
  const s = String(v || "").toLowerCase().trim();
  if (s === "mandiri") return "Jalur Mandiri";
  if (s === "yayasan") return "Beasiswa Yayasan";
  if (s === "kip") return "Jalur KIP";
  return v || "-";
};

const statusBayarLabel = (v) => {
  const s = String(v || "").toLowerCase().trim();
  return s === "paid" ? "LUNAS" : "PENDING";
};

const sidebarUser = computed(() => {
  const u = user.value || {};
  return {
    name: u?.name || "Pengguna",
    email: u?.email || "",
    phone: u?.phone || "",
    photo_url: "",
    role: "user",
  };
});

const source = computed(() => {
  const s = String(
    selected.value?.source ||
      selected.value?.jalur ||
      selected.value?.jalur_pendaftaran ||
      ""
  )
    .toLowerCase()
    .trim();

  if (s.includes("mandiri")) return "mandiri";
  if (s.includes("yayasan")) return "yayasan";
  if (s.includes("kip")) return "kip";
  return s || "mandiri";
});

// ===== Helpers dokumen =====
const isHttp = (u) => /^https?:\/\//i.test(String(u || ""));

const pickFileName = (url, fallback = "berkas") => {
  try {
    const u = String(url || "");
    const base = u.split("?")[0].split("#")[0];
    const name = base.split("/").pop() || fallback;
    return name;
  } catch {
    return fallback;
  }
};

const fileExt = (url) => {
  const n = pickFileName(url, "");
  const parts = n.split(".");
  return parts.length > 1 ? parts.pop().toLowerCase() : "";
};

const fileKind = (url) => {
  const ext = fileExt(url);
  if (["jpg", "jpeg", "png", "webp", "gif"].includes(ext)) return "image";
  if (["pdf"].includes(ext)) return "pdf";
  if (["doc", "docx"].includes(ext)) return "doc";
  if (["xls", "xlsx", "csv"].includes(ext)) return "sheet";
  return "file";
};

const pmbDisplayName = computed(() => val(selected.value?.nama || selected.value?.nama_lengkap));
const pmbDisplayEmail = computed(() => val(selected.value?.email || selected.value?.alamat_email));
const pmbDisplayPhone = computed(() => val(selected.value?.phone || selected.value?.nomor_hp));
const statusBayar = computed(() => statusBayarLabel(selected.value?.status_pembayaran));

const badges = computed(() => {
  if (!selected.value) return [];
  return [
    { text: jalurLabel(source.value), tone: "neutral" },
    { text: `Status: ${statusBayar.value}`, tone: statusBayar.value === "LUNAS" ? "success" : "warn" },
    { text: `Sumber: ${source.value}`, tone: "neutral" },
  ];
});

// Kumpulkan semua link berkas (tanpa path)
const docLinks = computed(() => {
  const s = selected.value || {};
  const list = [];

  // foto
  if (isHttp(s.foto_url)) list.push({ label: "Foto", url: s.foto_url });

  // mandiri
  if (Array.isArray(s.berkas_list)) {
    s.berkas_list.forEach((u, idx) => {
      if (isHttp(u)) list.push({ label: `Berkas ${idx + 1}`, url: u });
    });
  }

  // kip
  if (isHttp(s.kip_ktp_url)) list.push({ label: "KTP (KIP)", url: s.kip_ktp_url });
  if (isHttp(s.kip_kk_url)) list.push({ label: "KK (KIP)", url: s.kip_kk_url });

  // yayasan
  if (isHttp(s.file_ktp_url)) list.push({ label: "KTP", url: s.file_ktp_url });
  if (isHttp(s.file_kk_url)) list.push({ label: "KK", url: s.file_kk_url });
  if (isHttp(s.file_lk_url)) list.push({ label: "Surat/Lainnya", url: s.file_lk_url });
  if (isHttp(s.bukti_prestasi_url)) list.push({ label: "Bukti Prestasi", url: s.bukti_prestasi_url });

  if (Array.isArray(s.file_rapor_list)) {
    s.file_rapor_list.forEach((u, idx) => {
      if (isHttp(u)) list.push({ label: `Rapor ${idx + 1}`, url: u });
    });
  }

  // de-duplicate
  const seen = new Set();
  return list.filter((x) => {
    const key = x.label + "::" + x.url;
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });
});

// ===== Field Sets =====
const fieldSets = {
  mandiri: {
    title: "Data Mandiri",
    sections: [
      {
        title: "Biodata",
        fields: [
          { label: "Nama", key: "nama" },
          { label: "Jenis Kelamin", key: "jenis_kelamin" },
          { label: "Tempat Lahir", key: "tempat_lahir" },
          { label: "Tanggal Lahir", key: "tanggal_lahir", fmt: "date" },
        ],
      },
      {
        title: "Sekolah",
        fields: [
          { label: "Nama Sekolah", key: "nama_sekolah" },
          { label: "Jurusan Sekolah", key: "jurusan" }, // FIX mandiri
          { label: "Kota Sekolah", key: "kota_sekolah" },
          { label: "Tahun Lulus", key: "tahun_lulus" },
          { label: "NISN", key: "nisn" },
        ],
      },
      {
        title: "Pilihan Program Studi",
        fields: [
          { label: "Program Studi 1", key: "program_studi_1" },
          { label: "Program Studi 2", key: "program_studi_2" },
        ],
      },
      {
        title: "Kontak & Status",
        fields: [
          { label: "Email", key: "alamat_email" },
          { label: "Nomor HP", key: "nomor_hp" },
          { label: "OTP Terverifikasi", key: "otp_terverifikasi", fmt: "bool" },
          { label: "Berkas Terunggah", key: "berkas_terunggah", fmt: "bool" },
          { label: "Metode Pembayaran", key: "metode_pembayaran" },
          { label: "Status Pembayaran", key: "status_pembayaran" },
        ],
      },
    ],
  },

  kip: {
    title: "Data KIP",
    sections: [
      {
        title: "Biodata",
        fields: [
          { label: "Nama Lengkap", key: "nama_lengkap" },
          { label: "Jenis Kelamin", key: "jenis_kelamin" },
          { label: "Tempat Lahir", key: "tempat_lahir" },
          { label: "Tanggal Lahir", key: "tanggal_lahir", fmt: "date" },
          { label: "NIK", key: "nik" },
          { label: "Nomor KK", key: "nomor_kk" },
        ],
      },
      {
        title: "Sekolah",
        fields: [
          { label: "Nama Sekolah", key: "nama_sekolah" },
          { label: "Jurusan Sekolah", key: "jurusan_sekolah" },
          { label: "Kab/Kota Sekolah", key: "kabkota_sekolah" },
          { label: "Tahun Lulus", key: "tahun_lulus" },
          { label: "NPSN", key: "npsn_sekolah" },
          { label: "NISN", key: "nisn" },
        ],
      },
      {
        title: "Pilihan Program Studi",
        fields: [
          { label: "Program Studi 1", key: "program_studi_1" },
          { label: "Program Studi 2", key: "program_studi_2" },
        ],
      },
      {
        title: "Akun & Status",
        fields: [
          { label: "Username", key: "username" },
          { label: "Email", key: "alamat_email" },
          { label: "Nomor HP", key: "nomor_hp" },
          { label: "OTP Terverifikasi", key: "otp_terverifikasi", fmt: "bool" },
          { label: "Status Pembayaran", key: "status_pembayaran" },
          { label: "Berkas Terunggah", key: "berkas_terunggah", fmt: "bool" },
        ],
      },
    ],
  },

  yayasan: {
    title: "Data Beasiswa Yayasan",
    sections: [
      {
        title: "Biodata",
        fields: [
          { label: "Nama Lengkap", key: "nama_lengkap" },
          { label: "Jenis Kelamin", key: "jenis_kelamin" },
          { label: "Tempat Lahir", key: "tempat_lahir" },
          { label: "Tanggal Lahir", key: "tanggal_lahir", fmt: "date" },
          { label: "Kewarganegaraan", key: "kewarganegaraan" },
        ],
      },
      {
        title: "Sekolah",
        fields: [
          { label: "Nama Sekolah", key: "nama_sekolah" },
          { label: "Jurusan Sekolah", key: "jurusan_sekolah" },
          { label: "Kab/Kota Sekolah", key: "kabkota_sekolah" },
          { label: "Provinsi Sekolah", key: "provinsi_sekolah" },
          { label: "Tahun Lulus", key: "tahun_lulus" },
        ],
      },
      {
        title: "Pilihan Program Studi",
        fields: [
          { label: "Program Studi", key: "program_studi" },
          { label: "Program Studi 1", key: "program_studi_1" },
          { label: "Program Studi 2", key: "program_studi_2" },
        ],
      },
      {
        title: "Beasiswa & Prestasi",
        fields: [
          { label: "Jenis Beasiswa", key: "jenis_beasiswa" },
          { label: "Kategori Prestasi", key: "kategori_prestasi" },
          { label: "Deskripsi Prestasi", key: "deskripsi_prestasi" },
        ],
      },
      {
        title: "Akun & Status",
        fields: [
          { label: "Username", key: "username" },
          { label: "Email", key: "alamat_email" },
          { label: "Nomor HP", key: "nomor_hp" },
          { label: "OTP Terverifikasi", key: "otp_terverifikasi", fmt: "bool" },
          { label: "Status Pembayaran", key: "status_pembayaran" },
          { label: "Berkas Terunggah", key: "berkas_terunggah", fmt: "bool" },
        ],
      },
    ],
  },
};

const activeConfig = computed(() => fieldSets[source.value] || fieldSets.mandiri);

const resolveFieldValue = (field) => {
  const raw = selected.value?.[field.key];
  if (field.fmt === "date") return formatDate(raw);
  if (field.fmt === "bool") return raw ? "Ya" : "Belum";
  return val(raw);
};

async function fetchUserMe() {
  const res = await fetch("/api/user", {
    method: "GET",
    headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
    credentials: "include",
  });

  const json = await res.json().catch(() => ({}));
  if (res.status === 401) throw new Error(json?.message || "Anda belum login.");
  if (!res.ok) throw new Error(json?.message || `HTTP ${res.status}`);
  return json?.data || null;
}

const pickDefaultSelected = () => {
  if (!accounts.value.length) {
    selected.value = null;
    return;
  }
  const sorted = [...accounts.value].sort((a, b) => (b?.id || 0) - (a?.id || 0));
  selected.value = sorted[0];
};

onMounted(async () => {
  try {
    loading.value = true;
    error.value = "";

    const data = await fetchUserMe();
    user.value = data;
    accounts.value = Array.isArray(data?.pmb_accounts) ? data.pmb_accounts : [];
    pickDefaultSelected();
  } catch (e) {
    error.value = e?.message || "Terjadi kesalahan";
    user.value = null;
    accounts.value = [];
    selected.value = null;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="h-[100dvh] w-full overflow-hidden bg-slate-100 dark:bg-slate-950">
    <div class="h-full w-full md:flex">
      <AppSidebar class="md:shrink-0" variant="user" :user="sidebarUser" />

      <!-- Futuristic background -->
      <main class="w-full md:flex-1 min-w-0 h-full overflow-y-auto relative">
        <div class="pointer-events-none absolute inset-0">
          <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full blur-3xl opacity-30 bg-sky-400"></div>
          <div class="absolute top-24 -right-24 h-72 w-72 rounded-full blur-3xl opacity-25 bg-emerald-400"></div>
          <div class="absolute bottom-0 left-1/2 -translate-x-1/2 h-80 w-[36rem] rounded-full blur-3xl opacity-15 bg-indigo-400"></div>
          <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,0.06),transparent_55%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,0.05),transparent_55%)]"></div>
          <div class="absolute inset-0 [background-image:linear-gradient(to_right,rgba(148,163,184,0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,0.08)_1px,transparent_1px)] [background-size:48px_48px] opacity-20"></div>
        </div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
          <!-- Header -->
          <div class="mb-6 sm:mb-8">
            <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] tracking-[0.28em] uppercase
                        bg-white/60 dark:bg-slate-950/40 border border-slate-200/60 dark:border-slate-700/60
                        text-slate-600 dark:text-slate-300 backdrop-blur">
              User Portal
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
              Premium View
            </div>

            <div class="mt-3">
              <h1 class="text-2xl sm:text-3xl font-semibold text-slate-900 dark:text-slate-50">
                User Dashboard
              </h1>
              <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                Menampilkan akun login + data pendaftaran PMB Anda (Mandiri/KIP/Yayasan).
              </p>
            </div>
          </div>

          <div
            v-if="error"
            class="mb-4 rounded-2xl border border-rose-200/60 dark:border-rose-700/40
                   bg-rose-50/70 dark:bg-rose-900/20 px-4 py-3 text-xs text-rose-700 dark:text-rose-200 backdrop-blur"
          >
            {{ error }}
          </div>

          <div v-if="loading" class="text-sm text-slate-600 dark:text-slate-300">
            Memuat...
          </div>

          <template v-else>
            <!-- SUMMARY + ACCOUNTS (responsive) -->
            <div class="grid lg:grid-cols-12 gap-4 sm:gap-5 mb-6">
              <!-- Summary Card -->
              <div class="lg:col-span-5">
                <div class="rounded-3xl p-[1px] bg-gradient-to-br from-sky-400/40 via-emerald-400/20 to-indigo-400/30">
                  <div class="rounded-3xl bg-white/70 dark:bg-slate-950/40 border border-slate-200/60 dark:border-slate-700/60 backdrop-blur p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                      <div class="min-w-0">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Nama Akun</p>
                        <p class="mt-1 text-lg sm:text-xl font-semibold text-slate-900 dark:text-slate-50 truncate">
                          {{ val(user?.name) }}
                        </p>

                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Email Akun</p>
                        <p class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100 break-words">
                          {{ val(user?.email) }}
                        </p>
                      </div>

                      <div class="shrink-0">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-sky-500/30 to-emerald-500/20
                                    border border-slate-200/60 dark:border-slate-700/60 backdrop-blur grid place-items-center">
                          <div class="h-6 w-6 rounded-full bg-emerald-400/80 shadow-[0_0_30px_rgba(16,185,129,0.35)]"></div>
                        </div>
                      </div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-slate-200/60 dark:border-slate-700/60
                                bg-white/40 dark:bg-slate-900/20 px-4 py-3">
                      <p class="text-xs text-slate-500 dark:text-slate-400">Total data PMB terhubung</p>
                      <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-50">
                        {{ accounts.length }}
                      </p>
                    </div>

                    <p class="mt-4 text-[11px] text-slate-500 dark:text-slate-400">
                      Pilih salah satu akun PMB untuk melihat detailnya.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Accounts List -->
              <div class="lg:col-span-7">
                <div class="rounded-3xl bg-white/70 dark:bg-slate-950/40 border border-slate-200/60 dark:border-slate-700/60 backdrop-blur p-5 sm:p-6">
                  <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Akun PMB Anda</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                      Klik untuk memilih
                    </span>
                  </div>

                  <div v-if="accounts.length === 0" class="text-sm text-slate-600 dark:text-slate-300">
                    Belum ada data PMB yang cocok dengan email akun login ini.
                  </div>

                  <div v-else class="grid sm:grid-cols-2 gap-3">
                    <button
                      v-for="acc in accounts"
                      :key="acc.source + '-' + acc.id"
                      type="button"
                      @click="selected = acc"
                      class="group text-left rounded-2xl border px-4 py-4 transition backdrop-blur
                             focus:outline-none focus:ring-2 focus:ring-sky-400/40"
                      :class="
                        selected?.id === acc.id && selected?.source === acc.source
                          ? 'border-sky-400/60 bg-sky-50/70 dark:bg-sky-900/15 dark:border-sky-700/60'
                          : 'border-slate-200/60 bg-white/40 dark:bg-slate-900/15 dark:border-slate-700/60 hover:bg-white/60 dark:hover:bg-slate-900/25'
                      "
                    >
                      <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                          <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            {{ jalurLabel(acc.source) }} • ID: {{ acc.id }}
                          </p>
                          <p class="mt-1 font-semibold text-slate-900 dark:text-slate-50 truncate">
                            {{ val(acc.nama || acc.nama_lengkap) }}
                          </p>
                          <p class="mt-2 text-xs text-slate-600 dark:text-slate-300 break-words">
                            {{ val(acc.email || acc.alamat_email) }}
                          </p>
                          <p class="text-xs text-slate-600 dark:text-slate-300 break-words">
                            {{ val(acc.phone || acc.nomor_hp) }}
                          </p>
                        </div>

                        <div class="shrink-0">
                          <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold"
                            :class="
                              statusBayarLabel(acc.status_pembayaran) === 'LUNAS'
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                                : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'
                            "
                          >
                            {{ statusBayarLabel(acc.status_pembayaran) }}
                          </span>
                        </div>
                      </div>

                      <div class="mt-4 h-[1px] w-full bg-gradient-to-r from-transparent via-slate-200/70 to-transparent dark:via-slate-700/60"></div>
                      <div class="mt-3 flex items-center justify-between">
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Lihat detail</span>
                        <span class="text-[11px] font-semibold text-sky-600 dark:text-sky-300 group-hover:translate-x-0.5 transition">
                          →
                        </span>
                      </div>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- DETAIL -->
            <div v-if="selected" class="grid gap-4 sm:gap-5">
              <!-- Ringkasan pendaftar -->
              <div class="rounded-3xl bg-white/70 dark:bg-slate-950/40 border border-slate-200/60 dark:border-slate-700/60 backdrop-blur p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                  <div class="min-w-0">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Nama Pendaftar</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-50 truncate">
                      {{ pmbDisplayName }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                      <span
                        v-for="(b, i) in badges"
                        :key="'b'+i"
                        class="inline-flex items-center px-2.5 py-1 rounded-full font-semibold"
                        :class="
                          b.tone === 'success'
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                            : b.tone === 'warn'
                              ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'
                              : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
                        "
                      >
                        {{ b.text }}
                      </span>
                    </div>
                  </div>

                  <!-- mini actions area (future-ready) -->
                  <div class="sm:text-right">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Jenis Data</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-50">
                      {{ activeConfig.title }}
                    </p>
                  </div>
                </div>

                <div class="mt-5 grid sm:grid-cols-3 gap-3 text-sm">
                  <div class="rounded-2xl border border-slate-200/60 dark:border-slate-700/60 bg-white/40 dark:bg-slate-900/15 px-4 py-3">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Email</p>
                    <p class="mt-1 font-semibold text-slate-900 dark:text-slate-50 break-words">{{ pmbDisplayEmail }}</p>
                  </div>
                  <div class="rounded-2xl border border-slate-200/60 dark:border-slate-700/60 bg-white/40 dark:bg-slate-900/15 px-4 py-3">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Nomor HP</p>
                    <p class="mt-1 font-semibold text-slate-900 dark:text-slate-50 break-words">{{ pmbDisplayPhone }}</p>
                  </div>
                  <div class="rounded-2xl border border-slate-200/60 dark:border-slate-700/60 bg-white/40 dark:bg-slate-900/15 px-4 py-3">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Status Bayar</p>
                    <p class="mt-1 font-semibold"
                       :class="statusBayar === 'LUNAS' ? 'text-emerald-400' : 'text-amber-400'">
                      {{ statusBayar }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- BERKAS (mobile stack, desktop 2 col) -->
              <section class="rounded-3xl bg-white/70 dark:bg-slate-950/40 border border-slate-200/60 dark:border-slate-700/60 backdrop-blur p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Berkas</h2>
                  <span class="text-[11px] text-slate-500 dark:text-slate-400">Preview & link</span>
                </div>

                <div class="grid lg:grid-cols-12 gap-4">
                  <!-- Foto -->
                  <div class="lg:col-span-5">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Foto</p>

                    <div
                      class="rounded-2xl overflow-hidden border border-slate-200/60 dark:border-slate-700/60
                             bg-white/30 dark:bg-slate-900/10"
                    >
                      <div v-if="selected?.foto_url" class="relative">
                        <img
                          :src="selected.foto_url"
                          alt="Foto Pendaftar"
                          class="w-full h-56 sm:h-64 object-cover"
                          loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/50 via-transparent to-transparent"></div>
                      </div>

                      <div v-else class="h-56 sm:h-64 grid place-items-center text-sm text-slate-600 dark:text-slate-300">
                        Tidak ada foto
                      </div>
                    </div>

                    <a
                      v-if="selected?.foto_url"
                      :href="selected.foto_url"
                      target="_blank"
                      class="inline-flex mt-3 text-xs font-semibold text-sky-600 dark:text-sky-300 hover:underline break-words"
                    >
                      Buka foto (tab baru)
                    </a>
                  </div>

                  <!-- Dokumen -->
                  <div class="lg:col-span-7">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">File Pendukung</p>

                    <div v-if="docLinks.length" class="grid sm:grid-cols-2 gap-3">
                      <a
                        v-for="(d, i) in docLinks.filter(x => x.label !== 'Foto')"
                        :key="d.label + i"
                        :href="d.url"
                        target="_blank"
                        class="group rounded-2xl border border-slate-200/60 dark:border-slate-700/60
                               bg-white/40 dark:bg-slate-900/15 hover:bg-white/60 dark:hover:bg-slate-900/25
                               px-4 py-3 transition backdrop-blur"
                      >
                        <div class="flex items-start justify-between gap-3">
                          <div class="min-w-0">
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ d.label }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-50 truncate">
                              {{ pickFileName(d.url, d.label) }}
                            </p>
                          </div>

                          <div class="shrink-0">
                            <span
                              class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold
                                     bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                              {{ fileKind(d.url).toUpperCase() }}
                            </span>
                          </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                          <span class="text-[11px] text-slate-500 dark:text-slate-400">Buka</span>
                          <span class="text-[11px] font-semibold text-sky-600 dark:text-sky-300 group-hover:translate-x-0.5 transition">
                            →
                          </span>
                        </div>
                      </a>
                    </div>

                    <div v-else class="text-sm text-slate-600 dark:text-slate-300">
                      Tidak ada file pendukung.
                    </div>
                  </div>
                </div>
              </section>

              <!-- DETAIL sections -->
              <section
                v-for="(sec, sIdx) in activeConfig.sections"
                :key="'sec'+sIdx"
                class="rounded-3xl bg-white/70 dark:bg-slate-950/40 border border-slate-200/60 dark:border-slate-700/60 backdrop-blur p-5 sm:p-6"
              >
                <div class="flex items-center justify-between gap-3 mb-4">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">{{ sec.title }}</h2>
                  <div class="h-[1px] flex-1 bg-gradient-to-r from-slate-200/70 to-transparent dark:from-slate-700/60"></div>
                </div>

                <div class="grid sm:grid-cols-2 gap-3 sm:gap-4 text-sm">
                  <div
                    v-for="(f, fIdx) in sec.fields"
                    :key="'f'+sIdx+'-'+fIdx"
                    class="rounded-2xl border border-slate-200/60 dark:border-slate-700/60
                           bg-white/40 dark:bg-slate-900/15 px-4 py-3"
                  >
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ f.label }}</p>
                    <p class="mt-1 font-semibold text-slate-900 dark:text-slate-50 break-words whitespace-pre-line">
                      {{ resolveFieldValue(f) }}
                    </p>
                  </div>
                </div>
              </section>
            </div>

            <div v-else class="text-sm text-slate-600 dark:text-slate-300">
              Pilih salah satu akun PMB untuk melihat detailnya.
            </div>
          </template>
        </div>
      </main>
    </div>
  </div>
</template>
