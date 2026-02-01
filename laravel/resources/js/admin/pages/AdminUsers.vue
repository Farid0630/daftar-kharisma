<script setup>
import { onMounted, ref, computed, watch } from "vue";
import Swal from "sweetalert2";

import AdminUserDetailModal from "@/admin/components/AdminUserDetailModal.vue";
import AdminUserEditModal from "@/admin/components/AdminUserEditModal.vue";

import {
  ArrowPathIcon,
  PencilSquareIcon,
  TrashIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  MagnifyingGlassIcon,
  ArrowDownIcon,
  ArrowUpIcon,
  ChevronUpDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDoubleLeftIcon,
  ChevronDoubleRightIcon,
} from "@heroicons/vue/24/outline";

/* =========================
   Endpoint
========================= */
const API_USERS_LIST = "/api/admin/users"; // GET ?per_page=&page=
const API_USER_UPDATE = (id) => `/api/admin/users/${encodeURIComponent(id)}`; // PUT
const API_USER_DELETE = (id) => `/api/admin/users/${encodeURIComponent(id)}`; // DELETE
const API_USER_DETAIL = (id) => `/api/admin/users/${encodeURIComponent(id)}`; // GET
const API_USER_PDF = (id) => `/api/admin/users/${encodeURIComponent(id)}/pdf`; // GET (PDF)
const API_USERS_CSV = "/api/admin/users-download"; // GET

// PMB list union + PMB detail
const API_PMB_LIST = "/api/admin/pmb/registrations/all"; // GET ?per_page=&page=
const API_PMB_DETAIL = (source, id) => `/api/admin/pmb/${encodeURIComponent(source)}/${encodeURIComponent(id)}`;

// PMB endpoints
const API_ADMIN_PMB_UPDATE = (source, id) => `/api/admin/pmb/${encodeURIComponent(source)}/${encodeURIComponent(id)}`;
const API_ADMIN_PMB_DELETE = (source, id) => `/api/admin/pmb/${encodeURIComponent(source)}/${encodeURIComponent(id)}`;

/* =========================
   Helpers
========================= */
const csrf = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

const safeStr = (v) => String(v ?? "").trim();

const normalizeId = (v) => {
  if (v === null || v === undefined) return "";
  const s = String(v).trim();
  if (!s) return "";
  return s.startsWith("#") ? s.slice(1) : s;
};

const normalizeSource = (v) => safeStr(v).toLowerCase();

const formatDate = (v) => {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleString("id-ID", {
    year: "numeric",
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const prettyKey = (k) =>
  String(k)
    .replaceAll("_", " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());

const stringifyVal = (v) => {
  if (v === null || v === undefined) return "-";
  if (typeof v === "boolean") return v ? "true" : "false";
  if (typeof v === "object") {
    try {
      return JSON.stringify(v, null, 2);
    } catch {
      return String(v);
    }
  }
  return String(v);
};

// helper parse JSON aman (handle 204 No Content)
const safeJson = async (res) => {
  if (!res) return {};
  if (res.status === 204) return {};
  const txt = await res.text().catch(() => "");
  if (!txt) return {};
  try {
    return JSON.parse(txt);
  } catch {
    return { message: txt };
  }
};

/* =========================
   Mode
========================= */
const dataMode = ref("users"); // users | pmb

/* =========================
   State (List)
========================= */
const loading = ref(true);
const refreshing = ref(false);
const error = ref("");

const users = ref([]); // raw list dari API (page subset jika server paginated, atau full jika tidak)
const page = ref(1);
const perPage = ref(25);
const total = ref(0); // total dari server jika paginated, atau total raw list jika non-paginated

// deteksi apakah endpoint mengembalikan paginator Laravel
const serverPaginated = ref(false);

/* UI filter/search/sort */
const q = ref("");
const filterRole = ref("all"); // users: all|admin|user|custom ; pmb: all|mandiri|kip|yayasan
const filterIsAdmin = ref("all"); // all|yes|no (hanya users)

const sortKey = ref("created_at");
const sortDir = ref("desc"); // asc|desc

/* =========================
   State (Edit Modal)
========================= */
const editOpen = ref(false);
const editing = ref(null);
const saving = ref(false);

/* =========================
  State (PMB Edit Modal)
========================= */
const autoOpenPmb = ref(null);

/* =========================
   State (Detail Modal)
========================= */
const detailOpen = ref(false);
const detailLoading = ref(false);
const detailUser = ref(null);
const lastDetailRow = ref(null);

/* =========================
   Fetch List (Users / PMB)
========================= */
const fetchUsers = async () => {
  loading.value = true;
  error.value = "";

  try {
    const listUrl = dataMode.value === "users" ? API_USERS_LIST : API_PMB_LIST;

    const res = await fetch(`${listUrl}?per_page=${perPage.value}&page=${page.value}`, {
      headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
      credentials: "same-origin",
    });

    const data = await safeJson(res);
    if (!res.ok) throw new Error(data?.message || "Gagal memuat data");

    // Deteksi paginator Laravel: biasanya punya total & current_page
    const looksPaginated =
      data &&
      Array.isArray(data?.data) &&
      (Object.prototype.hasOwnProperty.call(data, "total") ||
        Object.prototype.hasOwnProperty.call(data, "current_page") ||
        Object.prototype.hasOwnProperty.call(data, "last_page") ||
        Object.prototype.hasOwnProperty.call(data, "per_page"));

    serverPaginated.value = !!looksPaginated;

    const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];

    // Normalisasi data list agar id/source selalu clean
    users.value =
      dataMode.value === "users"
        ? list.map((u) => ({ ...u, id: normalizeId(u.id) }))
        : list.map((u) => ({
            ...u,
            id: normalizeId(u.id),
            source: normalizeSource(u.source),
          }));

    // total:
    // - jika server paginated -> pakai data.total
    // - jika non paginated -> pakai panjang list (karena full data diambil)
    total.value = serverPaginated.value ? Number(data?.total || 0) : users.value.length;

    // jika user sedang ada di page yang sudah melebihi pageCount (misal setelah delete), kita rapikan
    if (!serverPaginated.value) {
      const pc = Math.max(1, Math.ceil((users.value.length || 0) / perPage.value));
      if (page.value > pc) page.value = pc;
    }
  } catch (e) {
    error.value = e?.message || "Terjadi kesalahan";
  } finally {
    loading.value = false;
  }
};

const refresh = async () => {
  refreshing.value = true;
  try {
    await fetchUsers();
  } finally {
    refreshing.value = false;
  }
};

onMounted(fetchUsers);

// jika mode ganti: reset pagination + filter, lalu fetch
watch(dataMode, async () => {
  page.value = 1;
  q.value = "";
  filterRole.value = "all";
  filterIsAdmin.value = "all";
  sortKey.value = "created_at";
  sortDir.value = "desc";
  await fetchUsers();
});

watch(perPage, async () => {
  page.value = 1;
  await fetchUsers();
});

// jika filter/search berubah, reset page agar tidak blank
watch([q, filterRole, filterIsAdmin], () => {
  page.value = 1;
});

/* =========================
   Normalisasi rows (Users / PMB)
========================= */
const normalizedRows = computed(() => {
  return (users.value || []).map((u) => {
    if (dataMode.value === "users") {
      const id = normalizeId(u.id);
      return {
        kind: "user",
        uid: `user-${id || "x"}`,
        id,
        name: u.name ?? u.nama ?? u.nama_lengkap ?? "-",
        email: u.email ?? u.alamat_email ?? "-",
        role: u.role ?? "",
        is_admin: !!u.is_admin,
        created_at: u.created_at,
        source: null,
        raw: { ...u, id },
      };
    }

    // mode PMB: list union berisi {id,name,email,source,created_at}
    const id = normalizeId(u.id);
    const source = normalizeSource(u.source);
    return {
      kind: "pmb",
      uid: `pmb-${source}-${id || "x"}`,
      id,
      name: u.name ?? u.nama_lengkap ?? u.nama ?? u.username ?? "-",
      email: u.email ?? u.alamat_email ?? "-",
      role: source, // tampilkan source di kolom Role
      is_admin: false,
      created_at: u.created_at,
      source,
      raw: { ...u, id, source },
    };
  });
});

/* =========================
   Filter + Search
========================= */
const filteredRows = computed(() => {
  let arr = [...normalizedRows.value];

  const needle = safeStr(q.value).toLowerCase();
  if (needle) {
    arr = arr.filter((r) => {
      return (
        String(r.id).includes(needle) ||
        safeStr(r.name).toLowerCase().includes(needle) ||
        safeStr(r.email).toLowerCase().includes(needle) ||
        safeStr(r.role).toLowerCase().includes(needle)
      );
    });
  }

  if (dataMode.value === "users") {
    if (filterRole.value === "admin") {
      arr = arr.filter((r) => safeStr(r.role).toLowerCase() === "admin");
    } else if (filterRole.value === "user") {
      arr = arr.filter((r) => safeStr(r.role).toLowerCase() === "user");
    } else if (filterRole.value === "custom") {
      arr = arr.filter((r) => {
        const rr = safeStr(r.role).toLowerCase();
        return rr && rr !== "admin" && rr !== "user";
      });
    }

    if (filterIsAdmin.value === "yes") arr = arr.filter((r) => r.is_admin);
    if (filterIsAdmin.value === "no") arr = arr.filter((r) => !r.is_admin);
  } else {
    // mode PMB: filterRole dipakai untuk source
    if (filterRole.value !== "all") {
      arr = arr.filter(
        (r) => safeStr(r.role).toLowerCase() === safeStr(filterRole.value).toLowerCase()
      );
    }
  }

  return arr;
});

/* =========================
   Sorting
========================= */
const getSortVal = (r, key) => {
  if (key === "id") return Number(r.id || 0);
  if (key === "name") return safeStr(r.name).toLowerCase();
  if (key === "email") return safeStr(r.email).toLowerCase();
  if (key === "role") return safeStr(r.role).toLowerCase();
  if (key === "is_admin") return r.is_admin ? 1 : 0;
  if (key === "created_at") {
    const d = new Date(r.created_at);
    return Number.isNaN(d.getTime()) ? 0 : d.getTime();
  }
  return safeStr(r[key]).toLowerCase();
};

const sortedRows = computed(() => {
  const arr = [...filteredRows.value];
  arr.sort((a, b) => {
    const va = getSortVal(a, sortKey.value);
    const vb = getSortVal(b, sortKey.value);

    let cmp = 0;
    if (typeof va === "number" && typeof vb === "number") cmp = va - vb;
    else cmp = String(va).localeCompare(String(vb), "id-ID", { numeric: true });

    return sortDir.value === "asc" ? cmp : -cmp;
  });
  return arr;
});

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
  else {
    sortKey.value = key;
    sortDir.value = "asc";
  }
};

const sortState = (key) => (sortKey.value !== key ? "none" : sortDir.value);

/* =========================
   Pagination (Adaptive)
   - Jika serverPaginated: list sudah dipotong dari server, tidak perlu slice.
   - Jika tidak: lakukan slice di frontend.
========================= */
const effectiveTotal = computed(() => {
  // server paginated: total dari server
  if (serverPaginated.value) return Math.max(0, Number(total.value || 0));
  // non-paginated: total setelah filter (lebih akurat)
  return sortedRows.value.length;
});

const pageCount = computed(() => Math.max(1, Math.ceil(effectiveTotal.value / perPage.value)));

const displayRows = computed(() => {
  if (serverPaginated.value) return sortedRows.value;

  const start = (page.value - 1) * perPage.value;
  return sortedRows.value.slice(start, start + perPage.value);
});

const showingFrom = computed(() => (effectiveTotal.value ? (page.value - 1) * perPage.value + 1 : 0));
const showingTo = computed(() =>
  effectiveTotal.value ? Math.min(page.value * perPage.value, effectiveTotal.value) : 0
);

watch([effectiveTotal, pageCount], () => {
  if (page.value > pageCount.value) page.value = pageCount.value;
});

const goPage = async (n) => {
  const next = Math.max(1, Math.min(pageCount.value, Number(n) || 1));
  if (next === page.value) return;
  page.value = next;

  // server paginated: fetch ulang untuk page baru
  if (serverPaginated.value) {
    await fetchUsers();
  }
};
const goFirst = () => goPage(1);
const goLast = () => goPage(pageCount.value);
const goPrev = () => goPage(page.value - 1);
const goNext = () => goPage(page.value + 1);

const pageButtons = computed(() => {
  const totalP = pageCount.value;
  const cur = page.value;
  const windowSize = 2;
  const out = [];
  const push = (x) => out.push(x);

  if (totalP <= 7) {
    for (let i = 1; i <= totalP; i++) push(i);
    return out;
  }

  push(1);
  const start = Math.max(2, cur - windowSize);
  const end = Math.min(totalP - 1, cur + windowSize);

  if (start > 2) push("...");
  for (let i = start; i <= end; i++) push(i);
  if (end < totalP - 1) push("...");

  push(totalP);
  return out;
});

/* =========================
   Detail (pairs)
========================= */
const excludedDetailKeys = new Set([
  "password",
  "remember_token",
  "two_factor_secret",
  "two_factor_recovery_codes",
]);

const detailPairs = computed(() => {
  const u = detailUser.value;
  if (!u) return [];

  const entries = Object.entries(u).filter(([k]) => !excludedDetailKeys.has(k));
  entries.sort(([a], [b]) => String(a).localeCompare(String(b), "id-ID"));

  return entries.map(([k, v]) => ({
    key: k,
    label: prettyKey(k),
    value: v,
    display: stringifyVal(v),
    isLong: typeof v === "object" || String(v ?? "").length > 80,
  }));
});

const openDetail = async (row) => {
  lastDetailRow.value = row;
  detailOpen.value = true;
  detailLoading.value = true;
  detailUser.value = row?.raw || null;

  try {
    if (row.kind === "user") {
      const id = normalizeId(row.id);
      if (!id) return;

      const res = await fetch(API_USER_DETAIL(id), {
        headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
        credentials: "same-origin",
      });

      if (res.ok) {
        const data = await safeJson(res);
        detailUser.value = data?.data || data || detailUser.value;
      }
      return;
    }

    // PMB: ambil detail lengkap
    const source = normalizeSource(row.source);
    const id = normalizeId(row.id);
    if (!source || !id) return;

    const res = await fetch(API_PMB_DETAIL(source, id), {
      headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
      credentials: "same-origin",
    });

    const data = await safeJson(res);
    if (!res.ok) throw new Error(data?.message || "Gagal memuat detail PMB");

    const acct = data?.data || data || row.raw;

    // bungkus agar modal detail Anda tetap kompatibel (punya pmb_accounts)
    detailUser.value = {
      id: null,
      name: row.name,
      email: row.email,
      role: "",
      is_admin: false,
      pmb_accounts: [{ ...acct, source, id }],
    };

    // pastikan editing tidak null (agar edit modal tidak error)
    if (!editing.value) {
      editing.value = { id: null, name: row.name, email: row.email, role: "", is_admin: false };
    }
  } catch {
    // fallback tetap pakai data list
  } finally {
    detailLoading.value = false;
  }
};

const closeDetail = () => {
  detailOpen.value = false;
  detailUser.value = null;
  detailLoading.value = false;
  lastDetailRow.value = null;
};

const refreshDetail = async () => {
  if (lastDetailRow.value) await openDetail(lastDetailRow.value);
};

/* =========================
   Edit (USER only)
========================= */
const startEdit = async (row) => {
  if (row.kind !== "user") return;

  const id = normalizeId(row.id);
  if (!id) {
    await Swal.fire({ icon: "error", title: "Gagal", text: "User ID tidak valid." });
    return;
  }

  try {
    detailLoading.value = true;

    const res = await fetch(API_USER_DETAIL(id), {
      headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
      credentials: "same-origin",
    });

    if (res.ok) {
      const data = await safeJson(res);
      const full = data?.data || data || row.raw;

      editing.value = {
        id: normalizeId(full.id),
        name: full.name ?? "",
        email: full.email ?? "",
        role: full.role ?? "",
        is_admin: !!full.is_admin,
      };

      detailUser.value = full;
    } else {
      editing.value = {
        id,
        name: row.name ?? "",
        email: row.email ?? "",
        role: row.role ?? "",
        is_admin: !!row.is_admin,
      };
    }
  } catch {
    editing.value = {
      id,
      name: row.name ?? "",
      email: row.email ?? "",
      role: row.role ?? "",
      is_admin: !!row.is_admin,
    };
  } finally {
    detailLoading.value = false;
    editOpen.value = true;
  }
};

// Edit PMB langsung
const startEditPmb = async (row) => {
  if (row.kind !== "pmb") return;

  await openDetail(row);
  const acct = detailUser.value?.pmb_accounts?.[0] || row.raw;

  openPmbEdit(acct);
};

const cancelEdit = () => {
  editOpen.value = false;
  editing.value = null;
  autoOpenPmb.value = null;
};

const saveEdit = async (payload) => {
  // saveEdit hanya untuk USER
  const toSave = payload || editing.value;
  if (!toSave) return;

  const id = normalizeId(toSave.id);
  if (!id) {
    await Swal.fire({ icon: "error", title: "Gagal", text: "User ID tidak valid saat menyimpan." });
    return;
  }

  // WHITELIST field user
  const body = {
    name: safeStr(toSave.name),
    email: safeStr(toSave.email),
    role: safeStr(toSave.role) || null,
    is_admin: !!toSave.is_admin,
  };

  saving.value = true;
  error.value = "";

  try {
    const res = await fetch(API_USER_UPDATE(id), {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf(),
        Accept: "application/json",
      },
      credentials: "same-origin",
      body: JSON.stringify(body),
    });

    const data = await safeJson(res);
    if (!res.ok) throw new Error(data?.message || "Gagal menyimpan");

    cancelEdit();
    await fetchUsers();

    await Swal.fire({
      icon: "success",
      title: "Berhasil",
      text: "Data user berhasil diperbarui.",
      timer: 1400,
      showConfirmButton: false,
    });
  } catch (e) {
    error.value = e?.message || "Gagal menyimpan";
    await Swal.fire({ icon: "error", title: "Gagal", text: error.value });
  } finally {
    saving.value = false;
  }
};

/* =========================
   Delete (User / PMB)
========================= */
const delUser = async (row) => {
  const id = normalizeId(row.id);
  if (!id) {
    await Swal.fire({ icon: "error", title: "Gagal", text: "User ID tidak valid." });
    return;
  }

  const result = await Swal.fire({
    icon: "warning",
    title: "Hapus user?",
    text: `User #${id} (${row.name}) akan dihapus permanen.`,
    showCancelButton: true,
    confirmButtonText: "Ya, hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#ef4444",
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch(API_USER_DELETE(id), {
      method: "DELETE",
      headers: { "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
      credentials: "same-origin",
    });

    const data = await safeJson(res);
    if (!res.ok) throw new Error(data?.message || "Gagal menghapus");

    // jika yang sedang dibuka di detail adalah row ini, tutup modal detail
    if (detailOpen.value && lastDetailRow.value?.uid === row.uid) {
      closeDetail();
    }

    await fetchUsers();

    await Swal.fire({
      icon: "success",
      title: "Dihapus",
      text: "User berhasil dihapus.",
      timer: 1400,
      showConfirmButton: false,
    });
  } catch (e) {
    await Swal.fire({
      icon: "error",
      title: "Gagal",
      text: e?.message || "Gagal menghapus",
    });
  }
};

const delRow = async (row) => {
  if (row.kind === "user") return delUser(row);
  return handlePmbDelete({ source: row.source, id: row.id, uid: row.uid });
};

/* =========================
   Download CSV (Users only)
========================= */
const downloadCsv = async () => {
  if (dataMode.value !== "users") {
    await Swal.fire({
      icon: "info",
      title: "Info",
      text: "CSV saat ini hanya tersedia untuk data Users.",
    });
    return;
  }
  window.location.href = API_USERS_CSV;
};

/* =========================
   Download PDF (Users only)
========================= */
const downloadingId = ref(null);

const downloadPdf = async (row) => {
  const id = normalizeId(row.id);
  if (!id) return;

  try {
    downloadingId.value = id;

    const res = await fetch(API_USER_PDF(id), {
      headers: { Accept: "application/pdf", "X-CSRF-TOKEN": csrf() },
      credentials: "same-origin",
    });

    if (!res.ok) {
      const txt = await res.text().catch(() => "");
      throw new Error(txt || "Gagal download PDF. Pastikan route PDF sudah ada.");
    }

    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = url;
    a.download = `user-${id}.pdf`;
    document.body.appendChild(a);
    a.click();
    a.remove();

    window.URL.revokeObjectURL(url);
  } catch (e) {
    await Swal.fire({
      icon: "error",
      title: "Gagal",
      text: e?.message || "Gagal download PDF",
    });
  } finally {
    downloadingId.value = null;
  }
};

const downloadPdfRow = async (row) => {
  if (row.kind !== "user") {
    await Swal.fire({
      icon: "info",
      title: "Info",
      text: "PDF hanya tersedia untuk data user.",
    });
    return;
  }
  return downloadPdf(row);
};

/* =========================
   PMB actions
========================= */
const openPmbEdit = (acct) => {
  if (!editing.value) {
    editing.value = { id: null, name: "", email: "", role: "", is_admin: false };
  }

  const source = normalizeSource(acct?.source);
  const id = normalizeId(acct?.id);

  editOpen.value = true;
  autoOpenPmb.value = acct ? { ...JSON.parse(JSON.stringify(acct)), source, id } : null;

  setTimeout(() => (autoOpenPmb.value = null), 0);
};

const handlePmbDelete = async (payload) => {
  if (!payload) return;

  const source = normalizeSource(payload.source);
  const id = normalizeId(payload.id);
  if (!source || !id) return;

  const result = await Swal.fire({
    icon: "warning",
    title: "Hapus record PMB?",
    text: `PMB ${source} #${id} akan dihapus permanen.`,
    showCancelButton: true,
    confirmButtonText: "Ya, hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#ef4444",
  });
  if (!result.isConfirmed) return;

  try {
    const res = await fetch(API_ADMIN_PMB_DELETE(source, id), {
      method: "DELETE",
      headers: { "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
      credentials: "same-origin",
    });

    const data = await safeJson(res);
    if (!res.ok) throw new Error(data?.message || "Gagal menghapus PMB");

    // jika yang sedang dibuka di detail adalah PMB yang sama, tutup modal detail
    if (detailOpen.value && lastDetailRow.value?.uid === payload.uid) {
      closeDetail();
    }

    await fetchUsers();

    await Swal.fire({
      icon: "success",
      title: "Dihapus",
      text: "Record PMB berhasil dihapus.",
      timer: 1400,
      showConfirmButton: false,
    });
  } catch (e) {
    await Swal.fire({
      icon: "error",
      title: "Gagal",
      text: e?.message || "Gagal menghapus PMB",
    });
  }
};

const onPmbChanged = async () => {
  await refreshDetail();
  await fetchUsers();
};
</script>

<template>
  <div class="w-full">
    <!-- Header -->
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <p class="text-[11px] tracking-[0.22em] text-slate-500 dark:text-slate-400">ADMIN</p>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50">Manajemen Users</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">
          Kelola akun, role, dan akses admin. (Bisa switch ke data PMB gabungan)
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="refresh"
          :disabled="refreshing"
          class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-800 dark:text-slate-100 hover:shadow transition disabled:opacity-60 disabled:cursor-not-allowed"
        >
          <ArrowPathIcon class="w-5 h-5" :class="refreshing ? 'animate-spin' : ''" />
          Refresh
        </button>

        <button
          type="button"
          @click="downloadCsv"
          class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-800 dark:text-slate-100 hover:shadow transition"
        >
          <ArrowDownTrayIcon class="w-5 h-5" />
          CSV
        </button>
      </div>
    </div>

    <!-- Error banner -->
    <div
      v-if="error"
      class="mb-4 rounded-xl border border-rose-200/70 dark:border-rose-400/30 bg-rose-50/70 dark:bg-rose-900/20 px-4 py-3 text-sm text-rose-700 dark:text-rose-200"
    >
      {{ error }}
    </div>

    <!-- Controls + Table Card -->
    <div
      class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/70 shadow-sm overflow-hidden"
    >
      <!-- Controls -->
      <div class="px-4 md:px-6 py-4 border-b border-slate-200/70 dark:border-slate-700/80">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
          <!-- Search -->
          <div class="flex items-center gap-2">
            <div
              class="flex items-center gap-2 rounded-full px-3 py-2 border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/40"
            >
              <MagnifyingGlassIcon class="w-4 h-4 text-slate-500 dark:text-slate-300" />
              <input
                v-model="q"
                type="text"
                placeholder="Cari id/nama/email/role..."
                class="bg-transparent outline-none text-sm text-slate-800 dark:text-slate-100 w-[240px] max-w-full"
              />
            </div>

            <span
              class="hidden sm:inline-flex items-center rounded-full px-3 py-2 text-xs border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/40 text-slate-600 dark:text-slate-300"
            >
              Rows (halaman ini):
              <span class="ml-1 font-semibold text-sky-600 dark:text-sky-300">{{ displayRows.length }}</span>
            </span>
          </div>

          <!-- Filters + perPage -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <!-- Mode switch -->
            <div
              class="inline-flex items-center gap-2 rounded-full px-3 py-2 border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/40"
            >
              <span class="text-xs text-slate-600 dark:text-slate-300">Data</span>
              <select v-model="dataMode" class="bg-transparent outline-none text-sm text-slate-800 dark:text-slate-100">
                <option value="users">Users</option>
                <option value="pmb">PMB (Mandiri+KIP+Yayasan)</option>
              </select>
            </div>

            <!-- Role / Source -->
            <div
              class="inline-flex items-center gap-2 rounded-full px-3 py-2 border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/40"
            >
              <span class="text-xs text-slate-600 dark:text-slate-300">
                {{ dataMode === "users" ? "Role" : "Sumber" }}
              </span>

              <select v-model="filterRole" class="bg-transparent outline-none text-sm text-slate-800 dark:text-slate-100">
                <template v-if="dataMode === 'users'">
                  <option value="all">Semua</option>
                  <option value="admin">Admin</option>
                  <option value="user">User</option>
                  <option value="custom">Custom</option>
                </template>
                <template v-else>
                  <option value="all">Semua</option>
                  <option value="mandiri">Mandiri</option>
                  <option value="kip">KIP</option>
                  <option value="yayasan">Yayasan</option>
                </template>
              </select>
            </div>

            <!-- Is Admin hanya untuk users -->
            <div
              v-if="dataMode === 'users'"
              class="inline-flex items-center gap-2 rounded-full px-3 py-2 border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/40"
            >
              <span class="text-xs text-slate-600 dark:text-slate-300">Is Admin</span>
              <select v-model="filterIsAdmin" class="bg-transparent outline-none text-sm text-slate-800 dark:text-slate-100">
                <option value="all">Semua</option>
                <option value="yes">Ya</option>
                <option value="no">Tidak</option>
              </select>
            </div>

            <!-- per page -->
            <div
              class="inline-flex items-center gap-2 rounded-full px-3 py-2 border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-950/40"
            >
              <span class="text-xs text-slate-600 dark:text-slate-300">Per Page</span>
              <select v-model.number="perPage" class="bg-transparent outline-none text-sm text-slate-800 dark:text-slate-100">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 dark:bg-slate-950/40 border-b border-slate-200/70 dark:border-slate-700/80">
            <tr class="text-left text-[11px] tracking-wide text-slate-600 dark:text-slate-300">
              <th class="px-4 md:px-6 py-3 font-semibold">
                <button class="inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition" @click="setSort('id')">
                  ID
                  <span class="inline-flex">
                    <ArrowUpIcon v-if="sortState('id') === 'asc'" class="w-4 h-4" />
                    <ArrowDownIcon v-else-if="sortState('id') === 'desc'" class="w-4 h-4" />
                    <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                  </span>
                </button>
              </th>

              <th class="px-4 md:px-6 py-3 font-semibold">
                <button class="inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition" @click="setSort('name')">
                  Nama
                  <span class="inline-flex">
                    <ArrowUpIcon v-if="sortState('name') === 'asc'" class="w-4 h-4" />
                    <ArrowDownIcon v-else-if="sortState('name') === 'desc'" class="w-4 h-4" />
                    <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                  </span>
                </button>
              </th>

              <th class="px-4 md:px-6 py-3 font-semibold">
                <button class="inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition" @click="setSort('email')">
                  Email
                  <span class="inline-flex">
                    <ArrowUpIcon v-if="sortState('email') === 'asc'" class="w-4 h-4" />
                    <ArrowDownIcon v-else-if="sortState('email') === 'desc'" class="w-4 h-4" />
                    <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                  </span>
                </button>
              </th>

              <th class="px-4 md:px-6 py-3 font-semibold">
                <button class="inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition" @click="setSort('role')">
                  {{ dataMode === 'users' ? 'Role' : 'Sumber' }}
                  <span class="inline-flex">
                    <ArrowUpIcon v-if="sortState('role') === 'asc'" class="w-4 h-4" />
                    <ArrowDownIcon v-else-if="sortState('role') === 'desc'" class="w-4 h-4" />
                    <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                  </span>
                </button>
              </th>

              <th class="px-4 md:px-6 py-3 font-semibold">
                <button class="inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition" @click="setSort('is_admin')">
                  Admin
                  <span class="inline-flex">
                    <ArrowUpIcon v-if="sortState('is_admin') === 'asc'" class="w-4 h-4" />
                    <ArrowDownIcon v-else-if="sortState('is_admin') === 'desc'" class="w-4 h-4" />
                    <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                  </span>
                </button>
              </th>

              <th class="px-4 md:px-6 py-3 font-semibold">
                <button class="inline-flex items-center gap-1.5 hover:text-sky-600 dark:hover:text-sky-300 transition" @click="setSort('created_at')">
                  Created
                  <span class="inline-flex">
                    <ArrowUpIcon v-if="sortState('created_at') === 'asc'" class="w-4 h-4" />
                    <ArrowDownIcon v-else-if="sortState('created_at') === 'desc'" class="w-4 h-4" />
                    <ChevronUpDownIcon v-else class="w-4 h-4 opacity-60" />
                  </span>
                </button>
              </th>

              <th class="px-4 md:px-6 py-3 font-semibold">Aksi</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200/70 dark:divide-slate-700/80">
            <tr v-if="loading">
              <td colspan="7" class="px-4 md:px-6 py-6">
                <div class="space-y-3">
                  <div class="h-3 w-1/2 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                  <div class="h-3 w-3/4 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                  <div class="h-3 w-2/3 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                </div>
                <p class="mt-4 text-xs text-slate-500 dark:text-slate-300/70">Memuat data...</p>
              </td>
            </tr>

            <tr v-else-if="displayRows.length === 0">
              <td colspan="7" class="px-4 md:px-6 py-10 text-center">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">Tidak ada data</p>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">Coba ubah filter atau kata kunci pencarian.</p>
              </td>
            </tr>

            <tr
              v-else
              v-for="r in displayRows"
              :key="r.uid"
              class="text-slate-800 dark:text-slate-100/90 hover:bg-sky-50/60 dark:hover:bg-white/[0.04] transition"
            >
              <td class="px-4 md:px-6 py-3 font-semibold text-slate-900 dark:text-slate-50">#{{ r.id }}</td>

              <td class="px-4 md:px-6 py-3">
                <div class="font-semibold text-slate-900 dark:text-slate-50">{{ r.name }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-300/70">{{ r.email }}</div>
              </td>

              <td class="px-4 md:px-6 py-3 text-slate-600 dark:text-slate-200/85">{{ r.email }}</td>

              <td class="px-4 md:px-6 py-3">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold border"
                  :class="
                    String(r.role).toLowerCase() === 'admin'
                      ? 'bg-sky-50/70 text-sky-700 border-sky-200/70 dark:bg-sky-900/20 dark:text-sky-200 dark:border-sky-400/30'
                      : String(r.role).toLowerCase() === 'user'
                        ? 'bg-slate-50/70 text-slate-700 border-slate-200/70 dark:bg-slate-900/30 dark:text-slate-200 dark:border-slate-700/80'
                        : 'bg-amber-50/70 text-amber-700 border-amber-200/70 dark:bg-amber-900/20 dark:text-amber-200 dark:border-amber-400/30'
                  "
                >
                  {{ r.role || "-" }}
                </span>
              </td>

              <td class="px-4 md:px-6 py-3">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold border"
                  :class="
                    r.is_admin
                      ? 'bg-emerald-50/70 text-emerald-700 border-emerald-200/70 dark:bg-emerald-900/20 dark:text-emerald-200 dark:border-emerald-400/30'
                      : 'bg-slate-50/70 text-slate-600 border-slate-200/70 dark:bg-slate-900/30 dark:text-slate-300 dark:border-slate-700/80'
                  "
                >
                  {{ r.is_admin ? "Yes" : "-" }}
                </span>
              </td>

              <td class="px-4 md:px-6 py-3 text-xs text-slate-500 dark:text-slate-300/70">{{ formatDate(r.created_at) }}</td>

              <!-- Aksi -->
              <td class="px-4 md:px-6 py-3">
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    @click="openDetail(r)"
                    title="Detail"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                  >
                    <EyeIcon class="w-5 h-5" />
                  </button>

                  <button
                    type="button"
                    @click="downloadPdfRow(r)"
                    title="Download PDF"
                    :disabled="r.kind !== 'user' || downloadingId === r.id"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    <ArrowDownTrayIcon class="w-5 h-5" :class="downloadingId === r.id ? 'animate-pulse' : ''" />
                  </button>

                  <button
                    type="button"
                    @click="r.kind === 'user' ? startEdit(r) : startEditPmb(r)"
                    title="Edit"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                  >
                    <PencilSquareIcon class="w-5 h-5" />
                  </button>

                  <button
                    type="button"
                    @click="delRow(r)"
                    title="Hapus"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-full bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-400 hover:to-red-600 text-white shadow-[0_10px_25px_rgba(239,68,68,0.20)] transition"
                  >
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-4 md:px-6 py-4 border-t border-slate-200/70 dark:border-slate-700/80">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <p class="text-xs text-slate-600 dark:text-slate-300">
            Showing <span class="font-semibold">{{ showingFrom }}</span> to
            <span class="font-semibold">{{ showingTo }}</span> of
            <span class="font-semibold">{{ effectiveTotal }}</span>
            {{ dataMode === 'users' ? 'users' : 'registrations' }}
          </p>

          <div class="inline-flex items-center gap-1">
            <button
              type="button"
              @click="goFirst"
              :disabled="page === 1"
              class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
              <ChevronDoubleLeftIcon class="w-4 h-4" />
            </button>

            <button
              type="button"
              @click="goPrev"
              :disabled="page === 1"
              class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition"
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
                :class="
                  p === page
                    ? 'border-sky-400 bg-sky-500 text-white shadow-sm'
                    : 'border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800'
                "
              >
                {{ p }}
              </button>
            </template>

            <button
              type="button"
              @click="goNext"
              :disabled="page === pageCount"
              class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
              <ChevronRightIcon class="w-4 h-4" />
            </button>

            <button
              type="button"
              @click="goLast"
              :disabled="page === pageCount"
              class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
              <ChevronDoubleRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALS -->
    <AdminUserDetailModal
      :open="detailOpen"
      :user="detailUser"
      :loading="detailLoading"
      :pairs="detailPairs"
      @close="closeDetail"
      @open-pmb-edit="openPmbEdit"
      @delete-pmb="handlePmbDelete"
    />

    <AdminUserEditModal
      :open="editOpen"
      :editing="editing"
      :saving="saving"
      :detail-user="detailUser"
      :auto-open-pmb="autoOpenPmb"
      @cancel="cancelEdit"
      @save="saveEdit"
      @pmb-changed="onPmbChanged"
    />
  </div>
</template>

<style scoped>
/* optional */
</style>
