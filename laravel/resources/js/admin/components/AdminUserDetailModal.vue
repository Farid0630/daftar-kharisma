<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    open: { type: Boolean, default: false },
    user: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);
const onClose = () => emit("close");

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

const API_ADMIN_PMB_SHOW = (source, id) => `/api/admin/pmb/${source}/${id}`;

const resolveUrl = (p) => {
    if (!p) return "";
    if (typeof p !== "string") return "";
    const s = p.trim();
    if (!s) return "";
    if (s.startsWith("http://") || s.startsWith("https://")) return s;
    if (s.startsWith("/")) return s;
    if (s.startsWith("public/")) return "/storage/" + s.replace(/^public\//, "");
    if (s.startsWith("storage/")) return "/" + s;
    return "/storage/" + s;
};

const isObjectLike = (v) => typeof v === "object" && v !== null;

const toLabel = (k) =>
    String(k)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());

/**
 * Jangan tampilkan data di gambar:
 * Created At, Email Verified At, Is Admin, Id, Role, Updated At
 * (snake_case + camelCase)
 */
const HIDDEN_COMMON_KEYS = new Set([
    "created_at",
    "updated_at",
    "email_verified_at",
    "is_admin",
    "id",
    "role",

    "createdAt",
    "updatedAt",
    "emailVerifiedAt",
    "isAdmin",
]);

/** keamanan umum (tetap disembunyikan) */
const HIDDEN_SECURITY_KEYS = new Set([
    "password",
    "remember_token",
    "two_factor_secret",
    "two_factor_recovery_codes",
    "kata_sandi_hash",
    "kata_sandi",
]);

/** relationship / data besar yang tidak perlu masuk grid user pairs */
const HIDDEN_USER_EXTRA = new Set(["pmb_accounts"]);

/** gabungan hidden untuk user */
const HIDDEN_DETAIL_KEYS = new Set([
    ...Array.from(HIDDEN_COMMON_KEYS),
    ...Array.from(HIDDEN_SECURITY_KEYS),
    ...Array.from(HIDDEN_USER_EXTRA),

    // field foto user tidak perlu tampil sebagai field grid (cukup preview foto di atas)
    "photo_url",
    "profile_photo_url",
    "avatar_url",
    "foto_url",
    "foto_path",
]);

/** gabungan hidden untuk pmb */
const HIDDEN_PMB_KEYS = new Set([
    ...Array.from(HIDDEN_COMMON_KEYS),
    ...Array.from(HIDDEN_SECURITY_KEYS),
]);

/**
 * RULE:
 * data url/path/nama jangan ditampilkan sebagai field,
 * cukup ditampilkan di section "Berkas".
 */
const isFileMetaKey = (k) => {
    const key = String(k);
    return key.endsWith("_url") || key.endsWith("_path") || key.endsWith("_nama");
};

const safeJsonArray = (v) => {
    if (!v) return null;
    if (Array.isArray(v)) return v;

    if (typeof v === "string") {
        const s = v.trim();
        if (!s) return null;
        try {
            const parsed = JSON.parse(s);
            return Array.isArray(parsed) ? parsed : null;
        } catch {
            return null;
        }
    }

    return null;
};

const fileRaporList = (acct) => {
    const v = acct?.file_rapor_paths;
    if (!v) return [];

    const arr = safeJsonArray(v);
    if (arr) return arr.map(resolveUrl).filter(Boolean);

    if (typeof v === "string") return [resolveUrl(v)].filter(Boolean);
    return [];
};

const isBoolish = (key, val) => {
    const k = String(key).toLowerCase();
    if (typeof val === "boolean") return true;
    if (val === 0 || val === 1) return true;
    if (val === "0" || val === "1") return true;

    if (
        k.includes("otp_ver") ||
        k.includes("otp_ter") ||
        k.includes("setuju") ||
        k.includes("berkas_terunggah")
    )
        return true;

    return false;
};

const asBool = (v) => {
    if (typeof v === "boolean") return v;
    if (v === 1 || v === "1") return true;
    if (v === 0 || v === "0") return false;
    return !!v;
};

/**
 * makePairs:
 * - FILTER: hiddenSet + fileMeta keys (*_url, *_path, *_nama) + file_rapor_paths + berkas
 * - tampilkan hanya text / json / boolean-ish
 */
const makePairs = (obj, hiddenSet) => {
    if (!obj) return [];

    const entries = Object.entries(obj)
        .filter(([k]) => !hiddenSet.has(k))
        .filter(([k]) => !isFileMetaKey(k))
        .filter(([k]) => k !== "file_rapor_paths") // rapor masuk berkas
        .filter(([k]) => k !== "berkas"); // mandiri "berkas" masuk berkas

    entries.sort(([a], [b]) => String(a).localeCompare(String(b), "id-ID"));

    return entries.map(([k, v]) => {
        const key = String(k);

        // boolean-ish
        if (isBoolish(key, v)) {
            return {
                key,
                label: toLabel(key),
                type: "text",
                display: asBool(v) ? "Ya" : "Tidak",
                isLong: false,
            };
        }

        // object/array
        if (isObjectLike(v)) {
            return {
                key,
                label: toLabel(key),
                type: "json",
                display: JSON.stringify(v, null, 2),
                isLong: true,
            };
        }

        const display = v === null || v === undefined || v === "" ? "-" : String(v);
        const isLong = display.length > 120;

        return {
            key,
            label: toLabel(key),
            type: isLong ? "json" : "text",
            display,
            isLong,
        };
    });
};

/**
 * Extract berkas dari object:
 * - scan semua *_url dan *_path (kecuali foto_*)
 * - rapor: file_rapor_paths (multi)
 * - mandiri: "berkas" JSON array [{name,path,url}]
 * Output: [{ base, name, href }]
 */
const extractFiles = (obj) => {
    if (!obj || typeof obj !== "object") return [];

    // dedupe by href biar tidak dobel
    const seenHref = new Set();
    const out = [];

    const pushFile = (base, name, href) => {
        const H = String(href || "").trim();
        if (!H) return;
        if (seenHref.has(H)) return;
        seenHref.add(H);

        out.push({
            base: String(base || H),
            name: String(name || "Berkas").trim() || "Berkas",
            href: H,
        });
    };

    // 1) scan *_url
    for (const [k, v] of Object.entries(obj)) {
        if (!k.endsWith("_url")) continue;
        if (k === "foto_url") continue;
        if (typeof v !== "string" || !v.trim()) continue;

        const base = k.replace(/_url$/, "");
        const nameKey = `${base}_nama`;
        const name = String(obj?.[nameKey] || toLabel(base)).trim() || toLabel(base);

        pushFile(base, name, resolveUrl(v));
    }

    // 2) scan *_path
    for (const [k, v] of Object.entries(obj)) {
        if (!k.endsWith("_path")) continue;
        if (k === "foto_path") continue;
        if (typeof v !== "string" || !v.trim()) continue;

        const base = k.replace(/_path$/, "");
        const nameKey = `${base}_nama`;
        const name = String(obj?.[nameKey] || toLabel(base)).trim() || toLabel(base);

        pushFile(base, name, resolveUrl(v));
    }

    // 3) rapor multi
    const rapors = fileRaporList(obj);
    rapors.forEach((href, idx) => {
        pushFile(
            `file_rapor_${idx + 1}`,
            rapors.length > 1 ? `File Rapor ${idx + 1}` : "File Rapor",
            href,
        );
    });

    // 4) mandiri: field "berkas" (JSON array)
    const arr = safeJsonArray(obj?.berkas);
    if (Array.isArray(arr)) {
        arr.forEach((it, idx) => {
            const name = it?.name || `Berkas ${idx + 1}`;
            const href = resolveUrl(it?.url || it?.path || "");
            pushFile(`berkas_${idx + 1}`, name, href);
        });
    }

    // urutkan biar rapi
    out.sort((a, b) => a.name.localeCompare(b.name, "id-ID"));
    return out;
};

/**
 * USER PAIRS (filtered)
 */
const pairs = computed(() => makePairs(props.user, HIDDEN_DETAIL_KEYS));

/**
 * PMB: fetch detail per akun: /api/admin/pmb/{source}/{id}
 */
const pmbDetails = ref({}); // cache: { "source-id": detailObj }
const pmbLoading = ref(false);

const pmbAccountsRaw = computed(() => {
    const list = props.user?.pmb_accounts;
    return Array.isArray(list) ? list : [];
});

const pmbAccountsMerged = computed(() => {
    return pmbAccountsRaw.value.map((acct) => {
        const k = `${acct.source}-${acct.id}`;
        const detail = pmbDetails.value[k];
        return detail ? { ...acct, ...detail } : acct;
    });
});

const pmbViewByAccount = computed(() => {
    return pmbAccountsMerged.value.map((acct) => {
        const items = makePairs(acct, HIDDEN_PMB_KEYS);

        // foto preview
        const foto = acct?.foto_url || acct?.foto_path || "";
        const fotoResolved = resolveUrl(foto);

        // berkas list
        const files = extractFiles(acct);

        return {
            key: `${acct.source}-${acct.id}`,
            source: acct.source || "-",
            title:
                acct.nama ||
                acct.nama_lengkap ||
                acct.username ||
                acct.alamat_email ||
                "-",
            foto: fotoResolved,
            files,
            items,
        };
    });
});

const fetchAllPmbDetails = async () => {
    const list = pmbAccountsRaw.value;
    if (!list.length) return;

    pmbLoading.value = true;
    try {
        await Promise.all(
            list.map(async (acct) => {
                try {
                    const res = await fetch(API_ADMIN_PMB_SHOW(acct.source, acct.id), {
                        method: "GET",
                        headers: {
                            Accept: "application/json",
                            "X-CSRF-TOKEN": csrf(),
                        },
                        credentials: "same-origin",
                    });

                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) return;

                    const detail = data?.data ?? data;
                    if (!detail || typeof detail !== "object") return;

                    const k = `${acct.source}-${acct.id}`;
                    pmbDetails.value = { ...pmbDetails.value, [k]: detail };
                } catch {
                    // ignore
                }
            }),
        );
    } finally {
        pmbLoading.value = false;
    }
};

watch(
    () => props.open,
    (v) => {
        if (!v) return;
        fetchAllPmbDetails();
    },
    { immediate: true },
);
</script>

<template>
    <transition name="fade">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            aria-modal="true"
            role="dialog"
            @keydown.esc="onClose"
        >
            <div class="absolute inset-0 bg-black/40" @click="onClose"></div>

            <div
                class="relative w-full max-w-3xl rounded-2xl border border-slate-200/70 dark:border-slate-700/80 bg-white dark:bg-slate-950 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            >
                <!-- Header -->
                <div
                    class="px-5 py-4 border-b border-slate-200/70 dark:border-slate-700/80 flex items-center justify-between"
                >
                    <div>
                        <p class="text-[11px] tracking-[0.22em] text-slate-500 dark:text-slate-400">
                            DETAIL USER
                        </p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                            {{ user?.name || user?.nama || "User" }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="onClose"
                        class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                        aria-label="Close"
                    >
                        ✕
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 overflow-auto flex-1">
                    <!-- User Foto -->
                    <div
                        v-if="
                            user &&
                            (user.photo_url ||
                                user.profile_photo_url ||
                                user.avatar_url ||
                                user.foto_url ||
                                user.foto_path)
                        "
                        class="mb-4"
                    >
                        <p class="text-xs text-slate-500 dark:text-slate-400">Foto</p>
                        <div class="mt-1">
                            <img
                                :src="
                                    resolveUrl(
                                        user.photo_url ||
                                            user.profile_photo_url ||
                                            user.avatar_url ||
                                            user.foto_url ||
                                            user.foto_path
                                    )
                                "
                                alt="foto user"
                                class="max-h-40 rounded-lg border border-slate-200/70 dark:border-slate-700/80"
                            />
                        </div>
                    </div>

                    <div v-if="loading" class="space-y-3">
                        <div class="h-3 w-1/2 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                        <div class="h-3 w-3/4 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                        <div class="h-3 w-2/3 rounded bg-slate-200/70 dark:bg-white/10 animate-pulse"></div>
                        <p class="mt-4 text-xs text-slate-500 dark:text-slate-300/70">
                            Memuat detail...
                        </p>
                    </div>

                    <div v-else>
                        <!-- USER PAIRS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div
                                v-for="item in pairs"
                                :key="item.key"
                                class="rounded-xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/40 p-3"
                                :class="item.isLong ? 'md:col-span-2' : ''"
                            >
                                <p class="text-[11px] tracking-wide text-slate-500 dark:text-slate-400">
                                    {{ item.label }}
                                </p>

                                <pre
                                    v-if="item.type === 'json'"
                                    class="mt-1 text-xs whitespace-pre-wrap break-words text-slate-800 dark:text-slate-100"
                                >{{ item.display }}</pre>

                                <p
                                    v-else
                                    class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-50 break-words"
                                >
                                    {{ item.display }}
                                </p>
                            </div>
                        </div>

                        <!-- PMB ACCOUNTS -->
                        <div v-if="pmbAccountsRaw.length" class="mt-6">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                                    PMB Accounts
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ pmbAccountsRaw.length }} akun
                                </p>
                            </div>

                            <div v-if="pmbLoading" class="mb-3 text-xs text-slate-500 dark:text-slate-400">
                                Memuat detail PMB...
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="acct in pmbViewByAccount"
                                    :key="acct.key"
                                    class="rounded-2xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/40 p-4"
                                >
                                    <div class="min-w-0">
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ acct.source }}
                                        </p>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-50 truncate">
                                            {{ acct.title }}
                                        </p>
                                    </div>

                                    <!-- FOTO PMB -->
                                    <div v-if="acct.foto" class="mt-3">
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Foto</p>
                                        <img
                                            :src="acct.foto"
                                            class="mt-1 max-h-44 rounded-lg border border-slate-200/70 dark:border-slate-700/80"
                                            alt="foto pmb"
                                        />
                                    </div>

                                    <!-- BERKAS -->
                                    <div v-if="acct.files && acct.files.length" class="mt-4">
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Berkas</p>
                                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div
                                                v-for="f in acct.files"
                                                :key="acct.key + '-file-' + f.base"
                                                class="rounded-xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/30 p-3"
                                            >
                                                <p class="text-[11px] tracking-wide text-slate-500 dark:text-slate-400">
                                                    {{ f.name }}
                                                </p>
                                                <a
                                                    :href="f.href"
                                                    target="_blank"
                                                    class="mt-1 inline-block text-sm font-semibold text-sky-600 dark:text-sky-400 hover:underline"
                                                >
                                                    Lihat File
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FIELD PMB (tanpa url/path/nama & tanpa berkas/file_rapor_paths mentah) -->
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div
                                            v-for="item in acct.items"
                                            :key="acct.key + '-' + item.key"
                                            class="rounded-xl border border-slate-200/70 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/30 p-3"
                                            :class="item.isLong ? 'md:col-span-2' : ''"
                                        >
                                            <p class="text-[11px] tracking-wide text-slate-500 dark:text-slate-400">
                                                {{ item.label }}
                                            </p>

                                            <pre
                                                v-if="item.type === 'json'"
                                                class="mt-1 text-xs whitespace-pre-wrap break-words text-slate-800 dark:text-slate-100"
                                            >{{ item.display }}</pre>

                                            <p
                                                v-else
                                                class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-50 break-words"
                                            >
                                                {{ item.display }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END PMB -->
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="px-5 py-4 border-t border-slate-200/70 dark:border-slate-700/80 flex items-center justify-end gap-2"
                >
                    <button
                        type="button"
                        @click="onClose"
                        class="inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 text-slate-800 dark:text-slate-100 hover:shadow transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
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
</style>
