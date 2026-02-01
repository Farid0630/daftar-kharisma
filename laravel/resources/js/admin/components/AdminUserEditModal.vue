<script setup>
import { ref, watch, computed, nextTick, onBeforeUnmount, toRefs } from "vue";
import Swal from "sweetalert2";
import {
  XMarkIcon,
  PencilSquareIcon,
  TrashIcon,
  CodeBracketIcon,
  ChevronDownIcon,
} from "@heroicons/vue/24/outline";

/**
 * PROPS & EMITS
 */
const props = defineProps({
  open: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  editing: { type: Object, default: null },       // user record
  detailUser: { type: Object, default: null },    // user detail + pmb_accounts
  autoOpenPmb: { type: Object, default: null },   // optional: auto open PMB
});
const emit = defineEmits(["cancel", "save", "pmb-changed"]);
const { open, saving, editing, detailUser, autoOpenPmb } = toRefs(props);

/**
 * HELPERS
 */
const csrf = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

const isObjectLike = (v) => typeof v === "object" && v !== null;

const titleCase = (s) =>
  String(s || "")
    .replaceAll("_", " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());

const enc = (v) => encodeURIComponent(String(v ?? ""));

const API_ADMIN_PMB_SHOW = (source, id) => `/api/admin/pmb/${enc(source)}/${enc(id)}`;
const API_ADMIN_PMB_UPDATE = (source, id) => `/api/admin/pmb/${enc(source)}/${enc(id)}`;
const API_ADMIN_PMB_DELETE = (source, id) => `/api/admin/pmb/${enc(source)}/${enc(id)}`;

/**
 * Normalisasi ID agar aman jika bentuknya "#6", " 6 ", dll
 */
const normalizeNumericId = (v) => {
  const s = String(v ?? "").trim();
  const m = s.match(/\d+/);
  return m ? m[0] : "";
};

/**
 * ✅ strip internal UI keys (yang diawali "__") secara rekursif
 */
const stripInternalKeys = (obj) => {
  if (!obj || typeof obj !== "object") return obj;
  if (Array.isArray(obj)) return obj.map(stripInternalKeys);

  const out = {};
  for (const [k, v] of Object.entries(obj)) {
    if (String(k).startsWith("__")) continue;
    out[k] = stripInternalKeys(v);
  }
  return out;
};

/**
 * ✅ PENTING: resolver identity PMB (hindari id undefined)
 */
const pickPmbIdentity = (obj) => {
  const rawSource =
    obj?.source ??
    obj?.jalur ??
    obj?.jalur_pendaftaran ??
    obj?.jenis ??
    obj?.jenis_pendaftaran ??
    obj?.table_source ??
    null;

  const source = rawSource ? String(rawSource).trim().toLowerCase() : "";

  const rawId =
    obj?.id ??
    obj?.pmb_registration_id ??
    obj?.pmb_registrations_id ??
    obj?.registration_id ??
    obj?.pmb_id ??
    obj?.pmb_reg_id ??
    obj?.reg_id ??
    null;

  const id = normalizeNumericId(rawId);
  return { source, id, rawId };
};

const resolveUrl = (p) => {
  if (!p || typeof p !== "string") return "";
  const s = p.trim();
  if (!s) return "";
  if (s.startsWith("http://") || s.startsWith("https://")) return s;
  if (s.startsWith("/")) return s;
  if (s.startsWith("public/")) return "/storage/" + s.replace(/^public\//, "");
  if (s.startsWith("storage/")) return "/" + s;
  return "/storage/" + s;
};

/**
 * HIDE FIELD RULE (UI ONLY)
 */
const isHiddenMetaField = (key) => {
  const k = String(key ?? "").toLowerCase();

  if (k === "created_at" || k === "updated_at") return true;
  if (k.includes("folder_path")) return true;

  if (k === "url" || k.endsWith("_url") || k.includes("_url_")) return true;

  if (k === "path") return true;
  if (k.endsWith("_path") || k.endsWith("_paths")) return true;
  if (k.includes("_path")) return true;

  return false;
};

/**
 * ✅ HIDE khusus PMB (UI ONLY)
 */
const isHiddenPmbField = (key) => {
  const k = String(key ?? "").toLowerCase();

  if (k === "created_at" || k === "updated_at") return true;
  if (k.includes("folder_path")) return true;

  if (k === "url" || k.endsWith("_url") || k.includes("_url_")) return true;

  if (k === "path") return true;
  if (k.endsWith("_path") || k.endsWith("_paths")) return true;
  if (k.includes("_path")) return true;

  return false;
};

/**
 * KHUSUS MANDIRI: field `berkas` JSON array
 */
const parseMandiriBerkas = (v) => {
  if (!v) return [];
  if (Array.isArray(v)) return v;
  if (typeof v === "string") {
    const s = v.trim();
    if (!s) return [];
    try {
      const parsed = JSON.parse(s);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }
  return [];
};

/**
 * file_rapor_paths bisa array / json string array / string tunggal
 */
const parseRaporPaths = (v) => {
  if (!v) return [];
  if (Array.isArray(v)) return v.map((x) => String(x ?? "")).filter(Boolean);

  if (typeof v === "string") {
    const s = v.trim();
    if (!s) return [];
    try {
      const parsed = JSON.parse(s);
      if (Array.isArray(parsed)) return parsed.map((x) => String(x ?? "")).filter(Boolean);
    } catch {
      // ignore
    }
    return [s];
  }
  return [];
};

/**
 * FormData helper
 */
const appendToFormData = (fd, key, value) => {
  if (value === undefined) return;

  if (value === null) {
    fd.append(key, "");
    return;
  }

  if (typeof value === "boolean") {
    fd.append(key, value ? "1" : "0");
    return;
  }

  if (value instanceof File) {
    fd.append(key, value);
    return;
  }

  if (Array.isArray(value)) {
    value.forEach((item) => {
      if (item === undefined) return;
      if (item === null) return fd.append(`${key}[]`, "");
      if (typeof item === "boolean") return fd.append(`${key}[]`, item ? "1" : "0");
      if (typeof item === "object") return fd.append(`${key}[]`, JSON.stringify(item));
      return fd.append(`${key}[]`, String(item));
    });
    return;
  }

  if (typeof value === "object") {
    fd.append(key, JSON.stringify(value));
    return;
  }

  fd.append(key, String(value));
};

const inputCls =
  "w-full rounded-xl border border-slate-200/70 dark:border-slate-700/80 " +
  "bg-white/90 dark:bg-slate-900/60 px-3 py-2 text-sm " +
  "text-slate-900 dark:text-slate-100 outline-none " +
  "focus:ring-2 focus:ring-sky-400/50 disabled:opacity-70";

/* =========================
 * USER EDIT
 * ========================= */
const localEditing = ref(null);
const jsonEdits = ref({});
const localError = ref("");

const showAllFields = ref(true);
const showRawJson = ref(false);

const EXCLUDED_KEYS = new Set([
  "password",
  "remember_token",
  "two_factor_secret",
  "two_factor_recovery_codes",
  "pmb_accounts",
  "photo_preview",
  "photo_file",
  "photo_name",
]);

/**
 * UI-only hide (TAPI id tetap dipertahankan di payload)
 */
const HIDE_USER_EDIT_KEYS = new Set([
  "role",
  "is_admin",
  "created_at",
  "updated_at",
  "email_verified_at",
  "folder_path",
]);

/**
 * readonly UI
 */
const READONLY_KEYS = new Set(["id", "created_at", "updated_at", "email_verified_at"]);
const BASIC_KEYS = new Set(["name", "email"]);

watch(
  editing,
  (v) => {
    localError.value = "";
    localEditing.value = v ? JSON.parse(JSON.stringify(v)) : null;

    if (localEditing.value) {
      // ✅ pastikan id ada & numerik
      const idNorm =
        normalizeNumericId(localEditing.value.id) ||
        normalizeNumericId(v?.id) ||
        normalizeNumericId(detailUser.value?.id);

      localEditing.value.id = idNorm || "";

      const nm = String(localEditing.value.name || "").trim();
      if (!nm) {
        localEditing.value.name =
          localEditing.value.nama ??
          localEditing.value.nama_lengkap ??
          localEditing.value.username ??
          "";
      }

      const em = String(localEditing.value.email || "").trim();
      if (!em) {
        localEditing.value.email =
          localEditing.value.alamat_email ?? localEditing.value.email ?? "";
      }

      // tetap simpan field internal (walau UI foto dihapus)
      localEditing.value.photo_preview = localEditing.value.photo_preview || null;
      localEditing.value.photo_file = null;
      localEditing.value.photo_name = localEditing.value.photo_name || null;
    }

    const next = {};
    if (localEditing.value) {
      for (const [k, val] of Object.entries(localEditing.value)) {
        if (EXCLUDED_KEYS.has(k)) continue;
        if (HIDE_USER_EDIT_KEYS.has(k)) continue;
        if (k === "id") continue; // UI hide
        if (isHiddenMetaField(k)) continue;
        if (isObjectLike(val)) next[k] = JSON.stringify(val, null, 2);
      }
    }
    jsonEdits.value = next;
  },
  { immediate: true }
);

const canSave = computed(() => {
  if (saving.value) return false;
  if (!localEditing.value) return false;

  // ✅ id wajib ada & numerik
  if (!normalizeNumericId(localEditing.value.id)) return false;

  return String(localEditing.value.name || "").trim().length > 0;
});

const inputType = (key, val) => {
  if (typeof val === "number") return "number";
  if (String(key).toLowerCase().includes("email")) return "email";
  return "text";
};

const allFields = computed(() => {
  const u = localEditing.value;
  if (!u) return [];

  const entries = Object.entries(u).filter(([k]) => {
    if (EXCLUDED_KEYS.has(k)) return false;
    if (HIDE_USER_EDIT_KEYS.has(k)) return false;
    if (k === "id") return false; // UI hide
    if (isHiddenMetaField(k)) return false;
    return true;
  });

  const basic = [];
  const rest = [];

  for (const [k, v] of entries) {
    const item = {
      key: k,
      label: titleCase(k),
      value: v,
      readonly: READONLY_KEYS.has(k),
      isJson: isObjectLike(v),
      isBool: typeof v === "boolean",
    };

    if (BASIC_KEYS.has(k)) basic.push(item);
    else rest.push(item);
  }

  rest.sort((a, b) => a.key.localeCompare(b.key, "id-ID"));
  return [...basic, ...rest];
});

const visibleFields = computed(() => {
  if (!showAllFields.value) return allFields.value.filter((f) => BASIC_KEYS.has(f.key));
  return allFields.value;
});

const normalizePayload = () => {
  const payload = localEditing.value ? JSON.parse(JSON.stringify(localEditing.value)) : null;
  if (!payload) return null;

  // ✅ WAJIB: id ikut terkirim
  payload.id = normalizeNumericId(localEditing.value?.id ?? payload.id);

  for (const [k, str] of Object.entries(jsonEdits.value || {})) {
    if (k === "id") continue;
    if (READONLY_KEYS.has(k) && k !== "id") continue;
    if (EXCLUDED_KEYS.has(k)) continue;
    if (HIDE_USER_EDIT_KEYS.has(k)) continue;
    if (isHiddenMetaField(k)) continue;

    const raw = String(str ?? "").trim();
    if (!raw) {
      payload[k] = null;
      continue;
    }

    try {
      payload[k] = JSON.parse(raw);
    } catch {
      throw new Error(`Field "${k}" bukan JSON valid.`);
    }
  }

  // bersihkan field foto user (UI upload dihapus)
  delete payload.photo_preview;
  delete payload.photo_file;
  delete payload.photo_name;

  // hapus field yang tidak ingin diupdate (kecuali id)
  for (const k of HIDE_USER_EDIT_KEYS) delete payload[k];

  // readonly metadata (kecuali id)
  delete payload.created_at;
  delete payload.updated_at;
  delete payload.email_verified_at;

  for (const [k] of Object.entries(payload)) {
    if (k === "id") continue;
    if (isHiddenMetaField(k)) delete payload[k];
  }

  return payload;
};

const onCancel = () => {
  localError.value = "";
  emit("cancel");
};

const onSave = () => {
  localError.value = "";
  if (!canSave.value) return;

  try {
    const payload = normalizePayload();

    if (!payload?.id) {
      throw new Error("User ID tidak valid saat menyimpan (payload.id kosong).");
    }

    emit("save", payload);
  } catch (e) {
    localError.value = e?.message || "Gagal memvalidasi data.";
  }
};

const sanitizeForPreview = (obj) => {
  if (!obj || typeof obj !== "object") return obj;
  if (Array.isArray(obj)) return obj.map(sanitizeForPreview);

  const out = {};
  for (const [k, v] of Object.entries(obj)) {
    if (EXCLUDED_KEYS.has(k)) continue;
    if (HIDE_USER_EDIT_KEYS.has(k)) continue;
    if (k === "id") continue;
    if (isHiddenMetaField(k)) continue;
    out[k] = sanitizeForPreview(v);
  }
  return out;
};

/* =========================
 * PMB
 * ========================= */
const pmbAccounts = computed(() => {
  const list = detailUser.value?.pmb_accounts;
  return Array.isArray(list) ? list : [];
});

/**
 * ✅ NORMALISASI list PMB agar id/source selalu benar
 */
const pmbAccountsNormalized = computed(() => {
  return (pmbAccounts.value || []).map((acct, idx) => {
    const { source, id, rawId } = pickPmbIdentity(acct);
    const invalid = !(source && id);
    return {
      ...acct,
      source: source || String(acct?.source || "").trim().toLowerCase(),
      id: id || "",
      __invalid: invalid,
      __rawId: rawId,
      __idx: idx,
      __key: `${source || acct?.source || "unknown"}-${id || "x"}-${idx}`,
    };
  });
});

const pmbEditOpen = ref(false);
const pmbEditing = ref(null);
const pmbSaving = ref(false);

const pmbFotoInput = ref(null);
const pmbBerkasInput = ref(null);

const existingBerkasInputRefs = ref({});
const setExistingBerkasInputRef = (base, el) => {
  if (!base) return;
  if (el) existingBerkasInputRefs.value[base] = el;
};
const clickReplaceExisting = (base) => {
  const el = existingBerkasInputRefs.value?.[base];
  if (el) el.click();
};

const ensurePmbBerkasState = () => {
  if (!pmbEditing.value) return;

  if (!Array.isArray(pmbEditing.value.berkas_new_files)) pmbEditing.value.berkas_new_files = [];
  if (!pmbEditing.value.berkas_field_files || typeof pmbEditing.value.berkas_field_files !== "object")
    pmbEditing.value.berkas_field_files = {};
  if (!Array.isArray(pmbEditing.value.berkas_remove_bases)) pmbEditing.value.berkas_remove_bases = [];
};

const onExistingBerkasChange = (base, e) => {
  if (!pmbEditing.value) return;
  ensurePmbBerkasState();
  const f = e?.target?.files?.[0] || null;
  if (!f) return;

  pmbEditing.value.berkas_field_files[base] = f;

  const idx = pmbEditing.value.berkas_remove_bases.indexOf(base);
  if (idx >= 0) pmbEditing.value.berkas_remove_bases.splice(idx, 1);

  const el = existingBerkasInputRefs.value?.[base];
  if (el) el.value = null;
};

const clearExistingReplacement = (base) => {
  if (!pmbEditing.value) return;
  ensurePmbBerkasState();
  delete pmbEditing.value.berkas_field_files[base];
};

const markRemoveExistingBerkas = (base) => {
  if (!pmbEditing.value) return;
  ensurePmbBerkasState();

  if (!pmbEditing.value.berkas_remove_bases.includes(base)) {
    pmbEditing.value.berkas_remove_bases.push(base);
  }
  delete pmbEditing.value.berkas_field_files[base];
};

const undoRemoveExistingBerkas = (base) => {
  if (!pmbEditing.value) return;
  ensurePmbBerkasState();

  const idx = pmbEditing.value.berkas_remove_bases.indexOf(base);
  if (idx >= 0) pmbEditing.value.berkas_remove_bases.splice(idx, 1);
};

/**
 * Helper: apakah sebuah key *_nama adalah meta file (punya pasangan _path/_url/_paths)
 */
const isFileMetaNameKey = (obj, key) => {
  const k = String(key);
  if (!k.endsWith("_nama")) return false;
  const base = k.replace(/_nama$/, "");
  return (
    Object.prototype.hasOwnProperty.call(obj, `${base}_path`) ||
    Object.prototype.hasOwnProperty.call(obj, `${base}_url`) ||
    Object.prototype.hasOwnProperty.call(obj, `${base}_paths`)
  );
};

const pmbBerkasList = computed(() => {
  const e = pmbEditing.value;
  if (!e) return [];
  ensurePmbBerkasState();

  const removedSet = new Set(pmbEditing.value?.berkas_remove_bases || []);
  const map = new Map();

  // 1) scan *_url
  for (const [k, v] of Object.entries(e)) {
    if (String(k).startsWith("__")) continue;
    if (!k.endsWith("_url")) continue;
    if (k === "foto_url") continue;
    if (typeof v !== "string" || !v.trim()) continue;

    const base = k.replace(/_url$/, "");
    const nameKey = `${base}_nama`;
    const displayName = String(e?.[nameKey] || titleCase(base)).trim() || titleCase(base);

    map.set(base, { base, name: displayName, url: resolveUrl(v), removed: removedSet.has(base), kind: "field" });
  }

  // 2) scan *_path jika belum ada
  for (const [k, v] of Object.entries(e)) {
    if (String(k).startsWith("__")) continue;
    if (!k.endsWith("_path")) continue;
    if (k === "foto_path") continue;
    if (typeof v !== "string" || !v.trim()) continue;

    const base = k.replace(/_path$/, "");
    if (map.has(base)) continue;

    const nameKey = `${base}_nama`;
    const displayName = String(e?.[nameKey] || titleCase(base)).trim() || titleCase(base);

    map.set(base, { base, name: displayName, url: resolveUrl(v), removed: removedSet.has(base), kind: "field" });
  }

  // 3) mandiri JSON `berkas` (view-only)
  const mandiriArr = parseMandiriBerkas(e.berkas);
  mandiriArr.forEach((it, idx) => {
    const href = resolveUrl(it?.url || it?.path || "");
    if (!href) return;
    const base = `mandiri_json_${idx + 1}`;
    const displayName = String(it?.name || `Berkas ${idx + 1}`).trim() || `Berkas ${idx + 1}`;
    if (!map.has(base)) map.set(base, { base, name: displayName, url: href, removed: false, kind: "json" });
  });

  // 4) file_rapor_paths (view-only)
  if (String(e?.source || "").toLowerCase() !== "yayasan") {
    const raporArr = parseRaporPaths(e.file_rapor_paths);
    raporArr.forEach((pth, idx) => {
      const href = resolveUrl(pth);
      if (!href) return;
      const base = `rapor_${idx + 1}`;
      const displayName = raporArr.length > 1 ? `File Rapor ${idx + 1}` : "File Rapor";
      if (!map.has(base)) map.set(base, { base, name: displayName, url: href, removed: false, kind: "rapor" });
    });
  }

  return Array.from(map.values()).sort((a, b) => a.base.localeCompare(b.base, "id-ID"));
});

const showAllPmbFields = ref(true);

const PMB_RUNTIME_KEYS = new Set([
  "berkas_new_files",
  "berkas_field_files",
  "berkas_remove_bases",
  "foto_file",
  "foto_preview",
  "foto_name",
]);

const PMB_HIDDEN_KEYS = new Set(["password"]);
const PMB_UI_HIDE_KEYS = new Set(["berkas", "file_rapor_paths"]);

const isReadonlyPmbKey = (k) => {
  const key = String(k);
  if (key === "id" || key === "source") return true;
  if (key === "created_at" || key === "updated_at") return true;
  if (key.endsWith("_at")) return true; // ✅ tampil readonly, tapi tidak dikirim
  if (key.endsWith("_url") || key.endsWith("_path") || key.endsWith("_paths")) return true;
  if (key.startsWith("foto_")) return true;
  return false;
};

const isBoolish = (key, val) => {
  const k = String(key).toLowerCase();

  // ✅ *_at jangan dianggap boolean
  if (k.endsWith("_at")) return false;
  if (k === "otp_verified_at") return false;

  if (typeof val === "boolean") return true;
  if (val === 0 || val === 1) return true;
  if (val === "0" || val === "1") return true;

  if (k.includes("otp_verifikasi")) return true;
  if (k.includes("otp_terverifikasi")) return true;

  if (k.includes("setuju")) return true;
  if (k.includes("berkas_terunggah")) return true;

  return false;
};

const asBool = (v) => {
  if (typeof v === "boolean") return v;
  if (v === 1 || v === "1") return true;
  if (v === 0 || v === "0") return false;
  return !!v;
};

const formatDateInput = (v) => {
  if (!v) return "";
  if (typeof v === "string" && /^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}`;
};

const isTextareaPmb = (key, val) => {
  const k = String(key).toLowerCase();
  if (k.includes("deskripsi")) return true;
  if (k.includes("kategori_prestasi")) return true;
  if (typeof val === "string" && val.length > 80) return true;
  return false;
};

const pmbPinnedKeys = [
  "nama_lengkap",
  "alamat_email",
  "nomor_hp",
  "username",
  "jalur_pendaftaran",
  "jalur",
  "program_studi",
  "program_studi_1",
  "program_studi_2",
  "status_pembayaran",
  "metode_pembayaran",
  "jenis_beasiswa",
  "jenis_kelamin",
  "tempat_lahir",
  "tanggal_lahir",
  "nik",
  "nomor_kk",
  "kewarganegaraan",
  "nama_sekolah",
  "npsn_sekolah",
  "nisn",
  "jenis_sekolah",
  "jurusan_sekolah",
  "jurusan",
  "provinsi_sekolah",
  "kabkota_sekolah",
  "kota_sekolah",
  "tahun_lulus",
  "otp_verifikasi",
  "otp_terverifikasi",
  "otp_verified_at", // tampil readonly, tidak dikirim
  "setuju_syarat",
  "setuju_kebenaran_data",
  "setuju_biaya_formulir",
  "berkas_terunggah",
  "deskripsi_prestasi",
  "kategori_prestasi",
];

const pmbAllFields = computed(() => {
  const u = pmbEditing.value;
  if (!u) return [];

  const entries = Object.entries(u)
    .filter(([k]) => !String(k).startsWith("__"))
    .filter(([k]) => !PMB_RUNTIME_KEYS.has(k))
    .filter(([k]) => !PMB_HIDDEN_KEYS.has(k))
    .filter(([k]) => !PMB_UI_HIDE_KEYS.has(k))
    .filter(([k]) => !isHiddenPmbField(k));

  const map = new Map(entries);
  const out = [];

  for (const k of pmbPinnedKeys) {
    if (!map.has(k)) continue;
    out.push([k, map.get(k)]);
    map.delete(k);
  }

  const rest = Array.from(map.entries()).sort(([a], [b]) => a.localeCompare(b, "id-ID"));
  out.push(...rest);

  return out.map(([k, v]) => {
    const key = String(k);
    return {
      key,
      label: titleCase(key),
      readonly: isReadonlyPmbKey(key),
      isBool: isBoolish(key, v),
      isTextarea: isTextareaPmb(key, v),
      isDate: String(key).toLowerCase().includes("tanggal_lahir"),
      isNumber: key === "tahun_lulus",
      value: v,
    };
  });
});

const pmbVisibleFields = computed(() => {
  if (showAllPmbFields.value) return pmbAllFields.value;
  const pinnedSet = new Set(pmbPinnedKeys);
  return pmbAllFields.value.filter((f) => pinnedSet.has(f.key));
});

const normalizePmbRecord = (obj) => {
  if (!obj || typeof obj !== "object") return obj;

  for (const [k, v] of Object.entries(obj)) {
    if (String(k).startsWith("__")) continue;
    if (PMB_RUNTIME_KEYS.has(k)) continue;
    if (PMB_HIDDEN_KEYS.has(k)) continue;

    const lk = String(k).toLowerCase();

    // ✅ otp_verified_at kalau "0" -> null (tampilnya kosong)
    if (lk === "otp_verified_at" && (v === 0 || v === "0")) obj[k] = null;

    if (!lk.endsWith("_at") && isBoolish(k, v)) obj[k] = asBool(v);
    if (lk.includes("tanggal_lahir")) obj[k] = formatDateInput(v);
  }

  if ("file_rapor_paths" in obj) obj.file_rapor_paths = parseRaporPaths(obj.file_rapor_paths);
  return obj;
};

const openPmbEdit = async (acct) => {
  const cleaned = stripInternalKeys(acct);

  const ident = pickPmbIdentity(cleaned);
  if (!ident.source || !ident.id) {
    await Swal.fire({
      icon: "error",
      title: "Gagal",
      text: `Invalid id/source. source="${ident.source || "-"}", id="${ident.id || "-"}" (raw id: ${String(
        ident.rawId ?? "-"
      )}). Pastikan backend mengirimkan field id/registration_id.`,
    });
    return;
  }

  const pinnedId = ident.id;
  const pinnedSource = ident.source;

  pmbEditing.value = cleaned ? JSON.parse(JSON.stringify(cleaned)) : null;

  if (pmbEditing.value) {
    // kunci identity
    pmbEditing.value.id = pinnedId;
    pmbEditing.value.source = pinnedSource;

    pmbEditing.value.foto_preview = pmbEditing.value.foto_preview || null;
    pmbEditing.value.foto_file = null;
    pmbEditing.value.foto_name = pmbEditing.value.foto_name || null;

    pmbEditing.value.berkas_new_files = [];
    pmbEditing.value.berkas_field_files = {};
    pmbEditing.value.berkas_remove_bases = [];

    if (!pmbEditing.value.nama_lengkap) {
      pmbEditing.value.nama_lengkap =
        pmbEditing.value.nama ?? pmbEditing.value.username ?? pmbEditing.value.nama_lengkap ?? "";
    }
    if (!pmbEditing.value.alamat_email) {
      pmbEditing.value.alamat_email = pmbEditing.value.email ?? pmbEditing.value.alamat_email ?? "";
    }

    delete pmbEditing.value.nama;
    delete pmbEditing.value.email;

    normalizePmbRecord(pmbEditing.value);
  }

  pmbEditOpen.value = true;

  // fetch detail lengkap
  try {
    const res = await fetch(API_ADMIN_PMB_SHOW(pinnedSource, pinnedId), {
      method: "GET",
      headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
      credentials: "same-origin",
    });

    const data = await res.json().catch(() => ({}));
    if (res.ok && data) {
      const detail = stripInternalKeys(data.data ?? data);

      const prevNewFiles = pmbEditing.value.berkas_new_files || [];
      const prevFieldFiles = pmbEditing.value.berkas_field_files || {};
      const prevRemoveBases = pmbEditing.value.berkas_remove_bases || [];
      const prevFotoFile = pmbEditing.value.foto_file || null;
      const prevFotoPreview = pmbEditing.value.foto_preview || null;

      pmbEditing.value = {
        ...pmbEditing.value,
        ...detail,
        // restore runtime
        berkas_new_files: prevNewFiles,
        berkas_field_files: prevFieldFiles,
        berkas_remove_bases: prevRemoveBases,
        foto_file: prevFotoFile,
        foto_preview: prevFotoPreview,
        // restore identity
        id: pinnedId,
        source: pinnedSource,
      };

      if (!pmbEditing.value.nama_lengkap) {
        pmbEditing.value.nama_lengkap =
          pmbEditing.value.nama ?? pmbEditing.value.username ?? pmbEditing.value.nama_lengkap ?? "";
      }
      if (!pmbEditing.value.alamat_email) {
        pmbEditing.value.alamat_email = pmbEditing.value.email ?? pmbEditing.value.alamat_email ?? "";
      }

      delete pmbEditing.value.nama;
      delete pmbEditing.value.email;

      normalizePmbRecord(pmbEditing.value);
    }
  } catch {
    // ignore
  }
};

const closePmbEdit = () => {
  pmbEditOpen.value = false;
  pmbEditing.value = null;
};

watch(autoOpenPmb, (v) => {
  if (v) openPmbEdit(v);
});

const calcHasBerkas = () => {
  ensurePmbBerkasState();
  const hasNewBerkas = (pmbEditing.value?.berkas_new_files || []).length > 0;
  const hasExistingActive = pmbBerkasList.value.some((b) => !b.removed);
  const hasReplacement = Object.values(pmbEditing.value?.berkas_field_files || {}).some((f) => f instanceof File);
  return hasExistingActive || hasNewBerkas || hasReplacement;
};

/**
 * ✅ Build body PMB yang BENAR-BENAR bersih:
 * - tidak kirim __*
 * - tidak kirim *_at (otp_verified_at, dll)
 * - tidak kirim *_url/_path/_paths
 * - tidak kirim meta file *_nama (yang punya pasangan _path/_url/_paths)
 * - tidak kirim berkas & file_rapor_paths (upload via input file)
 */
const buildPmbBodyAllFields = () => {
  const src0 = pmbEditing.value;
  if (!src0) return {};

  const src = stripInternalKeys(src0);
  const body = {};

  const SKIP_KEYS = new Set([
    "created_at",
    "updated_at",
    "berkas",
    "file_rapor_paths",
    ...Array.from(PMB_RUNTIME_KEYS),
    ...Array.from(PMB_HIDDEN_KEYS),
  ]);

  for (const [k, v] of Object.entries(src)) {
    const key = String(k);

    if (key.startsWith("__")) continue;
    if (SKIP_KEYS.has(key)) continue;
    if (key === "id" || key === "source") continue;
    if (key === "foto_file" || key === "foto_preview" || key === "foto_name") continue;

    const lk = key.toLowerCase();
    if (lk.endsWith("_at")) continue;
    if (lk.endsWith("_url") || lk.endsWith("_path") || lk.endsWith("_paths")) continue;

    // meta foto dan meta file-name (yang punya pasangan path/url)
    if (lk.startsWith("foto_")) continue;
    if (isFileMetaNameKey(src, key)) continue;

    body[key] = v === "" ? null : v;
  }

  body.berkas_terunggah = !!calcHasBerkas();
  return body;
};

const onPmbSave = async () => {
  if (!pmbEditing.value) return;

  const ident = pickPmbIdentity(pmbEditing.value);
  if (!ident.source || !ident.id) {
    await Swal.fire({ icon: "error", title: "Gagal", text: "Invalid id/source pada data PMB yang sedang diedit." });
    return;
  }

  pmbSaving.value = true;
  try {
    const payload = pmbEditing.value;

    const body = buildPmbBodyAllFields();
    ensurePmbBerkasState();

    const url = API_ADMIN_PMB_UPDATE(ident.source, ident.id);

    const fd = new FormData();
    fd.append("_method", "PUT");
    fd.append("source", String(ident.source));
    fd.append("id", String(ident.id));

    for (const [k, v] of Object.entries(body || {})) {
      appendToFormData(fd, k, v);
    }

    if (payload.foto_file instanceof File) {
      fd.append("foto", payload.foto_file);
    }

    if (payload.berkas_new_files?.length) {
      for (const f of payload.berkas_new_files) fd.append("berkas[]", f);
    }

    for (const [base, file] of Object.entries(payload.berkas_field_files || {})) {
      if (file instanceof File) fd.append(base, file);
    }

    if (payload.berkas_remove_bases?.length) {
      for (const base of payload.berkas_remove_bases) fd.append("remove_files[]", String(base));
    }

    const res = await fetch(url, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
      credentials: "same-origin",
      body: fd,
    });

    const ct = res.headers.get("content-type") || "";
    const data = ct.includes("application/json")
      ? await res.json().catch(() => ({}))
      : { message: await res.text().catch(() => "") };

    if (!res.ok) {
      const msg =
        data?.message ||
        (data?.errors ? JSON.stringify(data.errors) : `Gagal menyimpan PMB (HTTP ${res.status})`);
      throw new Error(msg);
    }

    await Swal.fire({
      icon: "success",
      title: "Berhasil",
      text: "Perubahan PMB disimpan.",
      timer: 1400,
      showConfirmButton: false,
    });

    closePmbEdit();
    emit("pmb-changed");
  } catch (e) {
    await Swal.fire({
      icon: "error",
      title: "Gagal",
      text: e?.message || "Gagal menyimpan PMB",
    });
  } finally {
    pmbSaving.value = false;
  }
};

const deletePmb = async (acct) => {
  if (!acct) return;

  const cleaned = stripInternalKeys(acct);
  const ident = pickPmbIdentity(cleaned);

  if (!ident.source || !ident.id) {
    await Swal.fire({
      icon: "error",
      title: "Gagal",
      text: `Tidak bisa hapus: Invalid id/source (raw id: ${String(ident.rawId ?? "-")}).`,
    });
    return;
  }

  const result = await Swal.fire({
    icon: "warning",
    title: "Hapus record PMB?",
    text: `Record PMB (${ident.source}) akan dihapus permanen.`,
    showCancelButton: true,
    confirmButtonText: "Ya, hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#ef4444",
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch(API_ADMIN_PMB_DELETE(ident.source, ident.id), {
      method: "DELETE",
      headers: { "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
      credentials: "same-origin",
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data?.message || "Gagal menghapus PMB");

    await Swal.fire({
      icon: "success",
      title: "Dihapus",
      text: "Record PMB berhasil dihapus.",
      timer: 1400,
      showConfirmButton: false,
    });

    if (pmbEditing.value?.source === ident.source && String(pmbEditing.value?.id) === String(ident.id)) closePmbEdit();
    emit("pmb-changed");
  } catch (e) {
    await Swal.fire({ icon: "error", title: "Gagal", text: e?.message || "Gagal menghapus PMB" });
  }
};

// FOTO PMB
const onPmbFotoChange = (e) => {
  const f = e?.target?.files?.[0] || null;
  if (!f || !pmbEditing.value) return;

  pmbEditing.value.foto_file = f;
  pmbEditing.value.foto_name = f.name;

  const reader = new FileReader();
  reader.onload = (ev) => (pmbEditing.value.foto_preview = ev.target?.result || null);
  reader.readAsDataURL(f);
};

const clearPmbFoto = () => {
  if (!pmbEditing.value) return;
  pmbEditing.value.foto_file = null;
  pmbEditing.value.foto_preview = null;
  pmbEditing.value.foto_name = null;
};

// BERKAS PMB (new)
const onPmbBerkasChange = (e) => {
  if (!pmbEditing.value) return;
  ensurePmbBerkasState();

  const files = Array.from(e?.target?.files || []);
  if (!files.length) return;

  for (const f of files) pmbEditing.value.berkas_new_files.push(f);
  if (pmbBerkasInput.value) pmbBerkasInput.value.value = null;
};

const removeNewBerkas = (idx) => {
  if (!pmbEditing.value?.berkas_new_files) return;
  pmbEditing.value.berkas_new_files.splice(idx, 1);
};

/* =========================
 * ACCESSIBILITY
 * ========================= */
const modalRef = ref(null);

const onKeydown = (e) => {
  if (!open.value) return;
  if (e.key === "Escape") {
    if (pmbEditOpen.value) return closePmbEdit();
    return onCancel();
  }
};

watch(
  open,
  async (v) => {
    if (v) {
      window.addEventListener("keydown", onKeydown);
      await nextTick();
      modalRef.value?.focus?.();
    } else {
      window.removeEventListener("keydown", onKeydown);
      closePmbEdit();
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  window.removeEventListener("keydown", onKeydown);
});

const pmbHeaderTitle = computed(() => {
  const p = pmbEditing.value;
  if (!p) return "-";
  return p.nama_lengkap || p.nama || p.username || p.alamat_email || p.email || p.source || "-";
});
</script>

<template>
  <transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
      <div class="absolute inset-0 bg-black/40" @click="onCancel"></div>

      <div
        ref="modalRef"
        tabindex="-1"
        class="relative w-full max-w-3xl rounded-2xl border border-slate-200/70 dark:border-slate-700/80 bg-white dark:bg-slate-950 shadow-2xl overflow-hidden flex flex-col max-h-[90vh] outline-none"
        @click.stop
      >
        <!-- Header -->
        <div class="px-5 py-4 border-b border-slate-200/70 dark:border-slate-700/80 flex items-center justify-between gap-3">
          <div>
            <p class="text-[11px] tracking-[0.22em] text-slate-500 dark:text-slate-400">EDIT USER</p>
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
              {{ localEditing?.name || localEditing?.nama || "User" }}
            </p>

            <!-- ✅ FOTO USER DIHAPUS (BAGIAN YANG ANDA LINGKARI MERAH) -->

            <p v-if="localEditing?.id" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
              User ID:
              <span class="font-semibold text-slate-700 dark:text-slate-200">#{{ localEditing.id }}</span>
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="showAllFields = !showAllFields"
              class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
              :title="showAllFields ? 'Ubah ke Field Utama saja' : 'Tampilkan Semua Field'"
            >
              <ChevronDownIcon class="w-4 h-4" :class="showAllFields ? 'rotate-180' : ''" />
              {{ showAllFields ? "Field Utama" : "Semua Field" }}
            </button>

            <button
              type="button"
              @click="showRawJson = !showRawJson"
              class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
              title="Lihat JSON (tanpa url/path/id/dll)"
            >
              <CodeBracketIcon class="w-4 h-4" />
              JSON
            </button>

            <button
              type="button"
              @click="onCancel"
              class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
              aria-label="Close"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Error -->
        <div
          v-if="localError"
          class="mx-5 mt-4 rounded-xl border border-rose-200/70 dark:border-rose-400/30 bg-rose-50/70 dark:bg-rose-900/20 px-4 py-3 text-sm text-rose-700 dark:text-rose-200"
        >
          {{ localError }}
        </div>

        <!-- Body -->
        <div class="p-5 overflow-auto flex-1 space-y-5">
          <!-- Raw JSON -->
          <div v-if="showRawJson" class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80 bg-white/80 dark:bg-slate-900/40 p-4">
            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mb-2">JSON (Disanitasi)</p>
            <pre class="text-xs whitespace-pre-wrap break-words text-slate-800 dark:text-slate-100">{{
              JSON.stringify(sanitizeForPreview(localEditing), null, 2)
            }}</pre>
          </div>

          <!-- Fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
              v-for="f in visibleFields"
              :key="f.key"
              class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/40 p-4"
              :class="f.isJson ? 'md:col-span-2' : ''"
            >
              <div class="flex items-center justify-between gap-2">
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ f.label }}</label>
                <span
                  v-if="f.readonly"
                  class="text-[11px] px-2 py-0.5 rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/70 text-slate-600 dark:text-slate-300"
                >
                  Read-only
                </span>
              </div>

              <div v-if="f.isBool" class="mt-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                  <input type="checkbox" v-model="localEditing[f.key]" :disabled="f.readonly" class="h-4 w-4 rounded border-slate-300 dark:border-slate-600" />
                  {{ f.label }}
                </label>
              </div>

              <div v-else-if="f.isJson" class="mt-2">
                <textarea
                  v-model="jsonEdits[f.key]"
                  :disabled="f.readonly"
                  rows="8"
                  class="w-full rounded-xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/60 px-3 py-2 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-sky-400/50 disabled:opacity-70"
                  placeholder="Masukkan JSON valid"
                />
                <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">Field ini object/array. Pastikan JSON valid saat menyimpan.</p>
              </div>

              <div v-else class="mt-2">
                <input :type="inputType(f.key, f.value)" v-model="localEditing[f.key]" :disabled="f.readonly" :class="inputCls" :placeholder="titleCase(f.key)" />
              </div>
            </div>
          </div>

          <!-- PMB Accounts -->
          <div v-if="pmbAccountsNormalized.length" class="pt-2">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-50">PMB Accounts (ringkasan)</h4>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ pmbAccountsNormalized.length }} akun</p>
            </div>

            <div class="mt-3 space-y-3">
              <div
                v-for="acct in pmbAccountsNormalized"
                :key="acct.__key"
                class="rounded-xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/40 p-3"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0 flex items-center gap-3">
                    <div v-if="acct.foto_url || acct.foto_path" class="w-14 h-14 flex-shrink-0">
                      <img
                        :src="resolveUrl(acct.foto_url || acct.foto_path)"
                        alt="foto"
                        class="w-14 h-14 object-cover rounded-md border border-slate-200/70 dark:border-slate-700/80"
                      />
                    </div>

                    <div class="min-w-0">
                      <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ acct.source }}
                        <span v-if="acct.__invalid" class="ml-2 text-rose-500">(invalid id)</span>
                      </p>
                      <p class="font-semibold text-slate-900 dark:text-slate-50 truncate">
                        {{ acct.nama || acct.nama_lengkap || acct.username || "-" }}
                      </p>
                      <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                        {{ acct.alamat_email || acct.email || "-" }}
                      </p>
                    </div>
                  </div>

                  <div class="flex items-center gap-2 shrink-0">
                    <button
                      type="button"
                      @click.stop="openPmbEdit(acct)"
                      :disabled="acct.__invalid"
                      title="Edit PMB"
                      class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                      <PencilSquareIcon class="w-5 h-5" />
                    </button>

                    <button
                      type="button"
                      @click.stop="deletePmb(acct)"
                      :disabled="acct.__invalid"
                      title="Hapus PMB"
                      class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-rose-200/70 dark:border-rose-400/30 bg-white/80 dark:bg-slate-900/70 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- end PMB -->
        </div>

        <!-- Footer -->
        <div class="px-5 py-4 border-t border-slate-200/70 dark:border-slate-700/80 flex items-center justify-end gap-2">
          <button
            type="button"
            @click="onCancel"
            class="inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-800 dark:text-slate-100 hover:shadow transition"
          >
            Batal
          </button>

          <button
            type="button"
            @click="onSave"
            :disabled="!canSave"
            class="inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-500 text-white shadow-[0_12px_30px_rgba(37,99,235,0.25)] transition disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {{ saving ? "Menyimpan…" : "Simpan" }}
          </button>
        </div>
      </div>

      <!-- NESTED MODAL: EDIT PMB -->
      <transition name="fade">
        <div v-if="pmbEditOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4" aria-modal="true" role="dialog">
          <div class="absolute inset-0 bg-black/50" @click="closePmbEdit"></div>

          <div
            class="relative w-full max-w-3xl rounded-2xl border border-slate-200/70 dark:border-slate-700/80 bg-white dark:bg-slate-950 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.stop
          >
            <div class="px-5 py-4 border-b border-slate-200/70 dark:border-slate-700/80 flex items-center justify-between">
              <div>
                <p class="text-[11px] tracking-[0.22em] text-slate-500 dark:text-slate-400">EDIT PMB</p>
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                  {{ pmbEditing?.source || "-" }} • {{ pmbHeaderTitle }}
                </p>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="showAllPmbFields = !showAllPmbFields"
                  class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                  :title="showAllPmbFields ? 'Ringkas' : 'Tampilkan semua field'"
                >
                  <ChevronDownIcon class="w-4 h-4" :class="showAllPmbFields ? 'rotate-180' : ''" />
                  {{ showAllPmbFields ? "Ringkas" : "Semua Field" }}
                </button>

                <button
                  type="button"
                  @click="closePmbEdit"
                  class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                  aria-label="Close"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>
            </div>

            <div class="p-5 overflow-auto flex-1 space-y-4">
              <!-- FIELD PMB -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div
                  v-for="f in pmbVisibleFields"
                  :key="f.key"
                  class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/40 p-4"
                  :class="f.isTextarea ? 'sm:col-span-2' : ''"
                >
                  <div class="flex items-center justify-between gap-2">
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ f.label }}</label>
                    <span
                      v-if="f.readonly"
                      class="text-[11px] px-2 py-0.5 rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/70 text-slate-600 dark:text-slate-300"
                    >
                      Read-only
                    </span>
                  </div>

                  <div v-if="f.isBool" class="mt-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                      <input type="checkbox" v-model="pmbEditing[f.key]" :disabled="f.readonly" class="h-4 w-4 rounded border-slate-300 dark:border-slate-600" />
                      {{ f.label }}
                    </label>
                  </div>

                  <div v-else-if="f.isDate" class="mt-2">
                    <input type="date" v-model="pmbEditing[f.key]" :disabled="f.readonly" :class="inputCls" />
                  </div>

                  <div v-else-if="f.isTextarea" class="mt-2">
                    <textarea
                      v-model="pmbEditing[f.key]"
                      :disabled="f.readonly"
                      rows="4"
                      class="w-full rounded-xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/60 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-sky-400/50 disabled:opacity-70"
                    />
                  </div>

                  <div v-else-if="f.isNumber" class="mt-2">
                    <input type="number" v-model="pmbEditing[f.key]" :disabled="f.readonly" :class="inputCls" />
                  </div>

                  <div v-else class="mt-2">
                    <input type="text" v-model="pmbEditing[f.key]" :disabled="f.readonly" :class="inputCls" />
                  </div>
                </div>
              </div>

              <!-- Foto PMB -->
              <div class="sm:col-span-2">
                <label class="text-xs text-slate-600 dark:text-slate-300">Foto</label>

                <div class="mt-2 flex items-center gap-4">
                  <div class="w-28 h-28 flex-shrink-0 rounded-md overflow-hidden border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/40">
                    <img v-if="pmbEditing?.foto_preview" :src="pmbEditing.foto_preview" alt="preview" class="w-full h-full object-cover" />
                    <img
                      v-else-if="pmbEditing?.foto_url || pmbEditing?.foto_path"
                      :src="resolveUrl(pmbEditing.foto_url || pmbEditing.foto_path)"
                      alt="foto"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400">Belum ada foto</div>
                  </div>

                  <div class="flex flex-col gap-2">
                    <input ref="pmbFotoInput" type="file" accept="image/*" @change="onPmbFotoChange" class="hidden" />

                    <div class="flex items-center gap-2">
                      <button
                        type="button"
                        @click="pmbFotoInput && pmbFotoInput.click()"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                      >
                        Ubah Foto
                      </button>

                      <button
                        v-if="pmbEditing?.foto_file || pmbEditing?.foto_preview || pmbEditing?.foto_url || pmbEditing?.foto_path"
                        type="button"
                        @click="clearPmbFoto"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold border border-rose-200/70 dark:border-rose-400/30 bg-white/80 dark:bg-slate-900/70 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition"
                      >
                        Hapus Foto
                      </button>
                    </div>

                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                      {{ pmbEditing?.foto_name || (pmbEditing?.foto_url ? "Foto saat ini dari server" : "") }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- BERKAS -->
              <div class="sm:col-span-2">
                <label class="text-xs text-slate-600 dark:text-slate-300">Berkas</label>

                <div class="mt-2 space-y-3">
                  <div v-if="pmbBerkasList.length" class="space-y-2">
                    <div
                      v-for="b in pmbBerkasList"
                      :key="b.base"
                      class="rounded-xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/40 px-3 py-2"
                    >
                      <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                          <div class="text-xs text-slate-500 dark:text-slate-400">{{ titleCase(b.base) }}</div>

                          <div class="text-sm">
                            <template v-if="!b.removed">
                              <a :href="b.url" target="_blank" class="text-sky-600 dark:text-sky-400 hover:underline">
                                {{ b.name }}
                              </a>
                            </template>
                            <template v-else>
                              <span class="text-rose-600 line-through">{{ b.name }}</span>
                              <span class="text-xs text-rose-600 ml-2">(akan dihapus)</span>
                            </template>
                          </div>

                          <div v-if="pmbEditing?.berkas_field_files?.[b.base]" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                            Pengganti: {{ pmbEditing.berkas_field_files[b.base].name }}
                            <button type="button" class="ml-2 text-rose-600" @click="clearExistingReplacement(b.base)">batal</button>
                          </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                          <template v-if="b.kind === 'field'">
                            <input
                              type="file"
                              class="hidden"
                              accept="*/*"
                              :ref="(el) => setExistingBerkasInputRef(b.base, el)"
                              @change="(e) => onExistingBerkasChange(b.base, e)"
                            />

                            <button
                              v-if="!b.removed"
                              type="button"
                              @click="clickReplaceExisting(b.base)"
                              class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                            >
                              Ubah
                            </button>

                            <button
                              v-if="!b.removed"
                              type="button"
                              @click="markRemoveExistingBerkas(b.base)"
                              class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold border border-rose-200/70 dark:border-rose-400/30 bg-white/80 dark:bg-slate-900/70 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition"
                            >
                              Hapus
                            </button>

                            <button
                              v-else
                              type="button"
                              @click="undoRemoveExistingBerkas(b.base)"
                              class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                            >
                              Batalkan
                            </button>
                          </template>

                          <template v-else-if="b.kind === 'json'">
                            <span class="text-[11px] px-2 py-0.5 rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/70 text-slate-600 dark:text-slate-300">
                              Mandiri
                            </span>
                          </template>

                          <template v-else>
                            <span class="text-[11px] px-2 py-0.5 rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/70 text-slate-600 dark:text-slate-300">
                              Rapor
                            </span>
                          </template>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="pmbEditing?.berkas_new_files?.length" class="space-y-1">
                    <div class="text-xs text-slate-500 dark:text-slate-400">Berkas baru (akan ditambahkan):</div>
                    <div v-for="(nf, idx) in pmbEditing.berkas_new_files" :key="nf.name + idx" class="flex items-center gap-3">
                      <div class="text-sm truncate">{{ nf.name }}</div>
                      <button type="button" @click="removeNewBerkas(idx)" class="text-rose-600 text-xs">Hapus</button>
                    </div>
                  </div>

                  <div class="flex items-center gap-2">
                    <input ref="pmbBerkasInput" type="file" multiple accept="*/*" @change="onPmbBerkasChange" class="hidden" />
                    <button
                      type="button"
                      @click="pmbBerkasInput && pmbBerkasInput.click()"
                      class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                    >
                      Tambah Berkas (berkas[])
                    </button>
                  </div>

                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Catatan: timestamp (<b>*_at</b>) dan meta file (<b>*_url/*_path</b>) tidak dikirim saat save. Upload file tetap lewat input file.
                  </p>
                </div>
              </div>
            </div>

            <div class="px-5 py-4 border-t border-slate-200/70 dark:border-slate-700/80 flex items-center justify-end gap-2">
              <button
                type="button"
                @click="closePmbEdit"
                class="inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-800 dark:text-slate-100 hover:shadow transition"
              >
                Batal
              </button>

              <button
                type="button"
                @click="onPmbSave"
                :disabled="pmbSaving"
                class="inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-500 text-white shadow-[0_12px_30px_rgba(37,99,235,0.25)] transition disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {{ pmbSaving ? "Menyimpan…" : "Simpan" }}
              </button>
            </div>
          </div>
        </div>
      </transition>
      <!-- end nested -->
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 160ms ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 240ms ease, transform 240ms ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.98);
}
.slide-fade-enter-to,
.slide-fade-leave-from {
  opacity: 1;
  transform: translateY(0) scale(1);
}
</style>
