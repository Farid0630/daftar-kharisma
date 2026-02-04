<script setup>
import { onMounted, ref, computed, watch } from "vue";

import {
  ArrowPathIcon,
  FunnelIcon,
  Squares2X2Icon,
  CheckBadgeIcon,
  ClockIcon,
  UsersIcon,

  // UI icons
  ChevronUpDownIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDoubleLeftIcon,
  ChevronDoubleRightIcon,
} from "@heroicons/vue/24/outline";

const csrf = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
  "";

const loading = ref(true);
const error = ref("");
const summary = ref({
  total: 0,
  mandiri: 0,
  yayasan: 0,
  kip: 0,
  paid: 0,
  pending: 0,
});

const filterJalur = ref("all"); // all|mandiri|yayasan|kip
const rows = ref([]);
const loadingTable = ref(false);
const refreshing = ref(false);

const jalurLabel = (v) => {
  const s = String(v || "").toLowerCase();
  if (s === "mandiri") return "Mandiri";
  if (s === "yayasan") return "Beasiswa Yayasan";
  if (s === "kip") return "KIP";
  return v || "-";
};

const statusLabel = (v) =>
  String(v || "").toLowerCase() === "paid" ? "LUNAS" : "PENDING";

const formatDate = (v) => {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return v;
  return d.toLocaleString("id-ID", {
    year: "numeric",
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
};

/** Normalisasi value untuk table agar kompatibel semua sumber (mandiri/yayasan/kip) */
const rowJalur = (r) => r?.jalur ?? r?.jalur_pendaftaran ?? r?.source ?? "";
const rowNama = (r) => r?.nama ?? r?.nama_lengkap ?? "-";
const rowEmail = (r) => r?.email ?? r?.alamat_email ?? "-";
const rowPhone = (r) => r?.phone ?? r?.nomor_hp ?? "-";
const rowProdi1 = (r) => r?.program_studi_1 ?? r?.prodi_1 ?? r?.program_studi ?? "-";
const rowProdi2 = (r) => r?.program_studi_2 ?? r?.prodi_2 ?? "-";
const rowStatus = (r) => r?.status_pembayaran ?? r?.status ?? "pending";

/** KUNCI UTAMA: key harus unik lintas sumber */
const rowKey = (r, i) => {
  const source = String(r?.source ?? rowJalur(r) ?? "row");
  const id = r?.id ?? r?.uuid ?? r?.key ?? i;
  return `${source}-${id}`;
};

const fetchSummary = async () => {
  const res = await fetch("/api/admin/dashboard/summary", {
    headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
    credentials: "same-origin",
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.message || "Gagal memuat summary");
  summary.value = data;
};

const fetchRegistrations = async () => {
  loadingTable.value = true;
  try {
    const qs = new URLSearchParams();
    if (filterJalur.value !== "all") qs.set("jalur", filterJalur.value);

    const res = await fetch(`/api/admin/registrations?${qs.toString()}`, {
      headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
      credentials: "same-origin",
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data?.message || "Gagal memuat data pendaftar");
    rows.value = data?.data || [];
  } finally {
    loadingTable.value = false;
  }
};

const refreshAll = async () => {
  try {
    refreshing.value = true;
    loading.value = true;
    error.value = "";
    await fetchSummary();
    await fetchRegistrations();
  } catch (e) {
    error.value = e?.message || "Terjadi kesalahan";
  } finally {
    refreshing.value = false;
    loading.value = false;
  }
};

const applyFilter = async () => {
  try {
    error.value = "";
    await fetchRegistrations();
  } catch (e) {
    error.value = e?.message || "Terjadi kesalahan";
  }
};

onMounted(refreshAll);

const filteredCount = computed(() => rows.value.length);

const activeFilterLabel = computed(() => {
  if (filterJalur.value === "all") return "Semua Jalur";
  return jalurLabel(filterJalur.value);
});

const paidPct = computed(() => {
  const t = Number(summary.value.total || 0);
  const p = Number(summary.value.paid || 0);
  if (!t) return 0;
  return Math.round((p / t) * 100);
});

/* =========================================================
   UI ONLY (Sorting + Pagination)
========================================================= */

// sorting
const sortKey = ref("created_at");
const sortDir = ref("desc"); // asc|desc

const getSortVal = (r, key) => {
  if (key === "jalur") return String(rowJalur(r)).toLowerCase();
  if (key === "nama") return String(rowNama(r)).toLowerCase();
  if (key === "email") return String(rowEmail(r)).toLowerCase();
  if (key === "phone") return String(rowPhone(r)).toLowerCase();
  if (key === "program_studi_1") return String(rowProdi1(r)).toLowerCase();
  if (key === "program_studi_2") return String(rowProdi2(r)).toLowerCase();
  if (key === "status_pembayaran") return String(rowStatus(r)).toLowerCase();
  if (key === "created_at") {
    const d = new Date(r?.created_at);
    return Number.isNaN(d.getTime()) ? 0 : d.getTime();
  }
  return String(r?.[key] ?? "").toLowerCase();
};

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
  else {
    sortKey.value = key;
    sortDir.value = "asc";
  }
  page.value = 1;
};

const sortState = (key) => {
  if (sortKey.value !== key) return "none";
  return sortDir.value;
};

const sortedRows = computed(() => {
  const arr = [...rows.value];
  arr.sort((a, b) => {
    const va = getSortVal(a, sortKey.value);
    const vb = getSortVal(b, sortKey.value);

    let cmp = 0;
    if (typeof va === "number" && typeof vb === "number") cmp = va - vb;
    else
      cmp = String(va).localeCompare(String(vb), "id-ID", {
        numeric: true,
        sensitivity: "base",
      });

    return sortDir.value === "asc" ? cmp : -cmp;
  });
  return arr;
});

// pagination
const page = ref(1);
const pageSize = ref(10);
const pageSizeOptions = [10, 25, 50, 100];

const totalRows = computed(() => sortedRows.value.length);
const pageCount = computed(() => Math.max(1, Math.ceil(totalRows.value / pageSize.value)));

const showingFrom = computed(() => (totalRows.value ? (page.value - 1) * pageSize.value + 1 : 0));
const showingTo = computed(() => (totalRows.value ? Math.min(page.value * pageSize.value, totalRows.value) : 0));

const displayRows = computed(() => {
  const start = (page.value - 1) * pageSize.value;
  return sortedRows.value.slice(start, start + pageSize.value);
});

const goPage = (n) => {
  const next = Math.max(1, Math.min(pageCount.value, Number(n) || 1));
  page.value = next;
};
const goFirst = () => goPage(1);
const goLast = () => goPage(pageCount.value);
const goPrev = () => goPage(page.value - 1);
const goNext = () => goPage(page.value + 1);

const pageButtons = computed(() => {
  const total = pageCount.value;
  const cur = page.value;
  const windowSize = 2;

  const out = [];
  const push = (x) => out.push(x);

  if (total <= 7) {
    for (let i = 1; i <= total; i++) push(i);
    return out;
  }

  push(1);
  const start = Math.max(2, cur - windowSize);
  const end = Math.min(total - 1, cur + windowSize);

  if (start > 2) push("...");
  for (let i = start; i <= end; i++) push(i);
  if (end < total - 1) push("...");

  push(total);
  return out;
});

// clamp page kalau data berubah/ukuran berubah
watch([rows, pageSize], () => {
  if (page.value > pageCount.value) page.value = pageCount.value;
  if (page.value < 1) page.value = 1;
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-950">
    <main class="min-h-screen overflow-y-auto">
      <section class="py-6 md:py-8">
        <div class="max-w-6xl mx-auto px-4">
          <!-- Header halaman -->
          <div class="mb-4 md:mb-5 flex flex-col md:flex-row md:items-start md:justify-between gap-3">
            <div class="flex items-start gap-3">
              <div
                class="h-11 w-11 rounded-2xl border border-sky-500/15 dark:border-slate-700/80
                       bg-white/95 dark:bg-slate-900/80 flex items-center justify-center shadow-sm
                       transition hover:-translate-y-0.5 hover:shadow"
              >
                <Squares2X2Icon class="h-6 w-6 text-sky-500 dark:text-sky-300" />
              </div>

              <div>
                <p class="text-[11px] tracking-[0.22em] text-slate-500 dark:text-slate-400">
                  ADMIN PANEL
                </p>
                <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
                  Dashboard PMB
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                  Ringkasan pendaftar Mandiri, Yayasan, dan KIP.
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px]
                           border border-slate-200/70 dark:border-slate-700/70
                           bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200"
                  >
                    <FunnelIcon class="h-4 w-4 text-slate-500 dark:text-slate-300" />
                    Filter:
                    <span class="font-semibold text-sky-600 dark:text-sky-300">{{ activeFilterLabel }}</span>
                  </span>

                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px]
                           border border-emerald-200/70 dark:border-emerald-400/30
                           bg-emerald-50/70 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-200"
                  >
                    <CheckBadgeIcon class="h-4 w-4" />
                    Lunas:
                    <span class="font-semibold">{{ paidPct }}%</span>
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 md:justify-end">
              <button
                type="button"
                @click="refreshAll"
                :disabled="refreshing"
                class="group inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold
                       border border-slate-200/70 dark:border-slate-700/70
                       bg-white/80 dark:bg-slate-900/70 text-slate-800 dark:text-slate-100
                       shadow-sm hover:shadow transition
                       disabled:opacity-60 disabled:cursor-not-allowed active:scale-[0.98]"
              >
                <ArrowPathIcon
                  class="h-5 w-5 transition"
                  :class="refreshing ? 'animate-spin' : 'group-hover:rotate-180'"
                />
                Refresh
              </button>
            </div>
          </div>

          <!-- Error banner -->
          <div
            v-if="error"
            class="mb-4 rounded-xl border border-rose-200/70 dark:border-rose-400/30
                   bg-rose-50/70 dark:bg-rose-900/20 px-4 py-3 text-sm
                   text-rose-700 dark:text-rose-200"
          >
            {{ error }}
          </div>

          <!-- Summary Cards -->
          <div
            class="rounded-2xl border border-blue-100/70 dark:border-slate-700
                   bg-white/95 dark:bg-slate-900/90
                   shadow-xl shadow-sky-900/10 dark:shadow-sky-900/30 overflow-hidden"
          >
            <div class="h-1.5 bg-gradient-to-r from-sky-400 via-blue-500 to-emerald-400"></div>

            <div class="px-5 py-5 md:px-6 md:py-6">
              <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80
                         bg-white/80 dark:bg-slate-950/30 p-4 shadow-sm
                         transition hover:-translate-y-0.5 hover:shadow"
                >
                  <div class="flex items-start justify-between">
                    <p class="text-xs text-slate-600 dark:text-slate-300">Total Pendaftar</p>
                    <UsersIcon class="h-5 w-5 text-sky-500 dark:text-sky-300" />
                  </div>
                  <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
                    {{ summary.total }}
                  </p>
                  <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Semua jalur pendaftaran
                  </p>
                </div>

                <div
                  class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80
                         bg-white/80 dark:bg-slate-950/30 p-4 shadow-sm
                         transition hover:-translate-y-0.5 hover:shadow"
                >
                  <p class="text-xs text-slate-600 dark:text-slate-300">Mandiri</p>
                  <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
                    {{ summary.mandiri }}
                  </p>
                  <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Jalur reguler</p>
                </div>

                <div
                  class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80
                         bg-white/80 dark:bg-slate-950/30 p-4 shadow-sm
                         transition hover:-translate-y-0.5 hover:shadow"
                >
                  <p class="text-xs text-slate-600 dark:text-slate-300">Beasiswa Yayasan</p>
                  <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
                    {{ summary.yayasan }}
                  </p>
                  <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Program Yayasan</p>
                </div>

                <div
                  class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80
                         bg-white/80 dark:bg-slate-950/30 p-4 shadow-sm
                         transition hover:-translate-y-0.5 hover:shadow"
                >
                  <p class="text-xs text-slate-600 dark:text-slate-300">KIP</p>
                  <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
                    {{ summary.kip }}
                  </p>
                  <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Kartu Indonesia Pintar</p>
                </div>

                <div
                  class="rounded-2xl border border-emerald-200/70 dark:border-emerald-400/30
                         bg-emerald-50/70 dark:bg-emerald-900/20 p-4 shadow-sm
                         transition hover:-translate-y-0.5 hover:shadow"
                >
                  <div class="flex items-start justify-between">
                    <p class="text-xs text-emerald-700 dark:text-emerald-200">Pembayaran Lunas</p>
                    <CheckBadgeIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-200" />
                  </div>
                  <p class="mt-2 text-3xl font-semibold tracking-tight text-emerald-800 dark:text-emerald-100">
                    {{ summary.paid }}
                  </p>
                  <p class="mt-1 text-[11px] text-emerald-700/80 dark:text-emerald-100/75">
                    Sudah terverifikasi
                  </p>
                </div>

                <div
                  class="rounded-2xl border border-amber-200/70 dark:border-amber-400/30
                         bg-amber-50/70 dark:bg-amber-900/20 p-4 shadow-sm
                         transition hover:-translate-y-0.5 hover:shadow"
                >
                  <div class="flex items-start justify-between">
                    <p class="text-xs text-amber-700 dark:text-amber-200">Pembayaran Pending</p>
                    <ClockIcon class="h-5 w-5 text-amber-600 dark:text-amber-200" />
                  </div>
                  <p class="mt-2 text-3xl font-semibold tracking-tight text-amber-800 dark:text-amber-100">
                    {{ summary.pending }}
                  </p>
                  <p class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-100/75">
                    Menunggu konfirmasi
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Table Section -->
          <div
            class="mt-6 rounded-2xl border border-blue-100/70 dark:border-slate-700
                   bg-white/95 dark:bg-slate-900/90
                   shadow-xl shadow-sky-900/10 dark:shadow-sky-900/30 overflow-hidden"
          >
            <div class="h-1.5 bg-gradient-to-r from-sky-400 via-blue-500 to-emerald-400"></div>

            <div
              class="flex flex-col md:flex-row md:items-center md:justify-between gap-3
                     px-5 py-4 md:px-6 border-b border-slate-200/70 dark:border-slate-700/80"
            >
              <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">Daftar Pendaftar</p>
                <p class="text-xs text-slate-600 dark:text-slate-300">
                  Total tampil:
                  <span class="font-semibold text-sky-600 dark:text-sky-300">{{ filteredCount }}</span>
                </p>
              </div>

              <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <div
                  class="inline-flex items-center gap-2 rounded-full px-3 py-2
                         border border-slate-200/70 dark:border-slate-700/70
                         bg-white/80 dark:bg-slate-900/70"
                >
                  <FunnelIcon class="h-4 w-4 text-slate-500 dark:text-slate-300" />
                  <select
                    v-model="filterJalur"
                    class="bg-transparent text-sm outline-none text-slate-800 dark:text-slate-100"
                  >
                    <option value="all">Semua Jalur</option>
                    <option value="mandiri">Mandiri</option>
                    <option value="yayasan">Beasiswa Yayasan</option>
                    <option value="kip">KIP</option>
                  </select>
                </div>

                <button
                  type="button"
                  @click="applyFilter"
                  :disabled="loadingTable"
                  class="inline-flex items-center justify-center gap-2 rounded-full px-4 py-2 text-sm font-semibold
                         bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-500
                         text-white shadow-[0_12px_30px_rgba(37,99,235,0.25)]
                         disabled:opacity-60 disabled:cursor-not-allowed transition active:scale-[0.98]"
                >
                  <ArrowPathIcon class="h-5 w-5" :class="loadingTable ? 'animate-spin' : ''" />
                  Terapkan
                </button>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-950/40 border-b border-slate-200/70 dark:border-slate-700/80">
                  <tr class="text-left text-[11px] tracking-wide text-slate-600 dark:text-slate-300">
                    <th class="px-5 py-3 font-semibold">
                      <button
                        type="button"
                        class="group inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition"
                        @click="setSort('jalur')"
                      >
                        Jalur
                        <span class="inline-flex">
                          <ArrowUpIcon v-if="sortState('jalur') === 'asc'" class="w-4 h-4" />
                          <ArrowDownIcon v-else-if="sortState('jalur') === 'desc'" class="w-4 h-4" />
                          <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                        </span>
                      </button>
                    </th>

                    <th class="px-5 py-3 font-semibold">
                      <button
                        type="button"
                        class="group inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition"
                        @click="setSort('nama')"
                      >
                        Nama
                        <span class="inline-flex">
                          <ArrowUpIcon v-if="sortState('nama') === 'asc'" class="w-4 h-4" />
                          <ArrowDownIcon v-else-if="sortState('nama') === 'desc'" class="w-4 h-4" />
                          <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                        </span>
                      </button>
                    </th>

                    <th class="px-5 py-3 font-semibold">
                      <button
                        type="button"
                        class="group inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition"
                        @click="setSort('email')"
                      >
                        Email
                        <span class="inline-flex">
                          <ArrowUpIcon v-if="sortState('email') === 'asc'" class="w-4 h-4" />
                          <ArrowDownIcon v-else-if="sortState('email') === 'desc'" class="w-4 h-4" />
                          <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                        </span>
                      </button>
                    </th>

                    <th class="px-5 py-3 font-semibold">
                      <button
                        type="button"
                        class="group inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition"
                        @click="setSort('phone')"
                      >
                        No. HP
                        <span class="inline-flex">
                          <ArrowUpIcon v-if="sortState('phone') === 'asc'" class="w-4 h-4" />
                          <ArrowDownIcon v-else-if="sortState('phone') === 'desc'" class="w-4 h-4" />
                          <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                        </span>
                      </button>
                    </th>

                    <th class="px-5 py-3 font-semibold">
                      <button
                        type="button"
                        class="group inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition"
                        @click="setSort('program_studi_1')"
                      >
                        Prodi 1
                        <span class="inline-flex">
                          <ArrowUpIcon v-if="sortState('program_studi_1') === 'asc'" class="w-4 h-4" />
                          <ArrowDownIcon v-else-if="sortState('program_studi_1') === 'desc'" class="w-4 h-4" />
                          <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                        </span>
                      </button>
                    </th>

                    <th class="px-5 py-3 font-semibold">
                      <button
                        type="button"
                        class="group inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition"
                        @click="setSort('program_studi_2')"
                      >
                        Prodi 2
                        <span class="inline-flex">
                          <ArrowUpIcon v-if="sortState('program_studi_2') === 'asc'" class="w-4 h-4" />
                          <ArrowDownIcon v-else-if="sortState('program_studi_2') === 'desc'" class="w-4 h-4" />
                          <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                        </span>
                      </button>
                    </th>

                    <th class="px-5 py-3 font-semibold">
                      <button
                        type="button"
                        class="group inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition"
                        @click="setSort('status_pembayaran')"
                      >
                        Status Bayar
                        <span class="inline-flex">
                          <ArrowUpIcon v-if="sortState('status_pembayaran') === 'asc'" class="w-4 h-4" />
                          <ArrowDownIcon v-else-if="sortState('status_pembayaran') === 'desc'" class="w-4 h-4" />
                          <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                        </span>
                      </button>
                    </th>

                    <th class="px-5 py-3 font-semibold">
                      <button
                        type="button"
                        class="group inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition"
                        @click="setSort('created_at')"
                      >
                        Created
                        <span class="inline-flex">
                          <ArrowUpIcon v-if="sortState('created_at') === 'asc'" class="w-4 h-4" />
                          <ArrowDownIcon v-else-if="sortState('created_at') === 'desc'" class="w-4 h-4" />
                          <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                        </span>
                      </button>
                    </th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-700/80">
                  <tr v-if="loading || loadingTable">
                    <td colspan="8" class="px-5 py-6">
                      <div class="space-y-3">
                        <div class="h-3 w-1/2 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                        <div class="h-3 w-3/4 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                        <div class="h-3 w-2/3 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                      </div>
                      <p class="mt-4 text-xs text-slate-500 dark:text-slate-300/70">Memuat data...</p>
                    </td>
                  </tr>

                  <tr v-else-if="rows.length === 0">
                    <td colspan="8" class="px-5 py-10 text-center">
                      <div class="mx-auto max-w-md">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">Tidak ada data</p>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                          Coba ubah filter jalur atau tekan refresh.
                        </p>
                      </div>
                    </td>
                  </tr>

                  <!-- KUNCI: key gabungan source/jalur + id agar tidak bentrok antar tabel -->
                  <tr
                    v-else
                    v-for="(r, i) in displayRows"
                    :key="rowKey(r, i)"
                    class="text-slate-800 dark:text-slate-100/90 hover:bg-sky-50/60 dark:hover:bg-white/[0.04] transition"
                  >
                    <td class="px-5 py-3">
                      <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold
                               border border-slate-200/70 dark:border-slate-700/70
                               bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200"
                      >
                        {{ jalurLabel(rowJalur(r)) }}
                      </span>
                    </td>

                    <td class="px-5 py-3 font-semibold text-slate-900 dark:text-slate-50">
                      {{ rowNama(r) }}
                    </td>

                    <td class="px-5 py-3 text-slate-600 dark:text-slate-200/85">
                      {{ rowEmail(r) }}
                    </td>

                    <td class="px-5 py-3 text-slate-600 dark:text-slate-200/85">
                      {{ rowPhone(r) }}
                    </td>

                    <td class="px-5 py-3 text-slate-600 dark:text-slate-200/85">
                      {{ rowProdi1(r) }}
                    </td>

                    <td class="px-5 py-3 text-slate-600 dark:text-slate-200/85">
                      {{ rowProdi2(r) }}
                    </td>

                    <td class="px-5 py-3">
                      <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border"
                        :class="
                          String(rowStatus(r)).toLowerCase() === 'paid'
                            ? 'bg-emerald-50/70 text-emerald-700 border-emerald-200/70 dark:bg-emerald-900/20 dark:text-emerald-200 dark:border-emerald-400/30'
                            : 'bg-amber-50/70 text-amber-700 border-amber-200/70 dark:bg-amber-900/20 dark:text-amber-200 dark:border-amber-400/30'
                        "
                      >
                        {{ statusLabel(rowStatus(r)) }}
                      </span>
                    </td>

                    <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-300/70">
                      {{ formatDate(r.created_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination bar -->
            <div class="px-5 py-4 md:px-6 border-t border-slate-200/70 dark:border-slate-700/80">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <p class="text-xs text-slate-600 dark:text-slate-300">
                  Showing
                  <span class="font-semibold">{{ showingFrom }}</span>
                  to
                  <span class="font-semibold">{{ showingTo }}</span>
                  of
                  <span class="font-semibold">{{ totalRows }}</span>
                  pendaftar
                </p>

                <div class="flex items-center gap-3">
                  <div
                    class="inline-flex items-center gap-2 rounded-full px-3 py-2
                           border border-slate-200/70 dark:border-slate-700/70
                           bg-white/80 dark:bg-slate-900/70"
                  >
                    <span class="text-xs text-slate-600 dark:text-slate-300">Tampil</span>
                    <select
                      v-model.number="pageSize"
                      class="bg-transparent text-sm outline-none text-slate-800 dark:text-slate-100"
                    >
                      <option v-for="n in pageSizeOptions" :key="n" :value="n">{{ n }}</option>
                    </select>
                  </div>

                  <div class="inline-flex items-center gap-1">
                    <button
                      type="button"
                      @click="goFirst"
                      :disabled="page === 1"
                      class="h-9 w-9 inline-flex items-center justify-center rounded-full
                             border border-slate-200/70 dark:border-slate-700/70
                             bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200
                             disabled:opacity-50 disabled:cursor-not-allowed
                             hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                    >
                      <ChevronDoubleLeftIcon class="w-4 h-4" />
                    </button>

                    <button
                      type="button"
                      @click="goPrev"
                      :disabled="page === 1"
                      class="h-9 w-9 inline-flex items-center justify-center rounded-full
                             border border-slate-200/70 dark:border-slate-700/70
                             bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200
                             disabled:opacity-50 disabled:cursor-not-allowed
                             hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                    >
                      <ChevronLeftIcon class="w-4 h-4" />
                    </button>

                    <template v-for="p in pageButtons" :key="String(p) + '-btn'">
                      <span v-if="p === '...'" class="px-2 text-slate-500 dark:text-slate-400">…</span>

                      <button
                        v-else
                        type="button"
                        @click="goPage(p)"
                        class="h-9 min-w-[36px] px-3 inline-flex items-center justify-center rounded-full border transition"
                        :class="p === page
                          ? 'border-sky-400 bg-sky-500 text-white shadow-sm'
                          : 'border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800'"
                      >
                        {{ p }}
                      </button>
                    </template>

                    <button
                      type="button"
                      @click="goNext"
                      :disabled="page === pageCount"
                      class="h-9 w-9 inline-flex items-center justify-center rounded-full
                             border border-slate-200/70 dark:border-slate-700/70
                             bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200
                             disabled:opacity-50 disabled:cursor-not-allowed
                             hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                    >
                      <ChevronRightIcon class="w-4 h-4" />
                    </button>

                    <button
                      type="button"
                      @click="goLast"
                      :disabled="page === pageCount"
                      class="h-9 w-9 inline-flex items-center justify-center rounded-full
                             border border-slate-200/70 dark:border-slate-700/70
                             bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200
                             disabled:opacity-50 disabled:cursor-not-allowed
                             hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                    >
                      <ChevronDoubleRightIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-300/70">
                Catatan: daftar ini menggabungkan tabel Mandiri, Yayasan, dan KIP (hasil normalisasi API).
              </p>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
