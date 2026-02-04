<!-- resources/js/components/layout/AppSidebar.vue -->
<script>
export default { inheritAttrs: false };
</script>

<script setup>
import { computed, ref, onMounted, watch, useAttrs } from "vue";
import { logout } from "@/helpers/auth.js";
import {
  Squares2X2Icon,
  UsersIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  XMarkIcon,
  HomeIcon,
  IdentificationIcon,
  SunIcon,
  MoonIcon,
} from "@heroicons/vue/24/outline";

const attrs = useAttrs();

const props = defineProps({
  user: { type: Object, default: null },
  variant: { type: String, default: "admin" },

  /** desktopOpen: true => tampil, false => hilang total */
  desktopOpen: { type: Boolean, default: true },

  /** izinkan tombol close total di desktop */
  hideable: { type: Boolean, default: true },
});

const emit = defineEmits(["desktop-open-change"]);

/** Mobile drawer */
const mobileOpen = ref(false);

/** THEME */
const theme = ref("dark");

const applyThemeClass = (mode) => {
  const root = document.documentElement;
  if (mode === "dark") root.classList.add("dark");
  else root.classList.remove("dark");
};

const initTheme = () => {
  const saved = localStorage.getItem("theme");
  if (saved === "dark" || saved === "light") {
    theme.value = saved;
    applyThemeClass(saved);
    return;
  }
  const prefersDark =
    window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
  theme.value = prefersDark ? "dark" : "light";
  applyThemeClass(theme.value);
};

const toggleTheme = () => {
  theme.value = theme.value === "dark" ? "light" : "dark";
};

watch(theme, (v) => {
  localStorage.setItem("theme", v);
  applyThemeClass(v);
});

/** Resolve auth user */
const authUser = computed(() => {
  if (props.user) return props.user;
  const w = typeof window !== "undefined" ? window : null;
  if (w?.__AUTH__?.user) return w.__AUTH__.user;
  return null;
});

const displayName = computed(() => {
  const u = authUser.value || {};
  return u.name || u.nama || u.nama_lengkap || u.full_name || u.username || "Pengguna";
});

const displayEmail = computed(() => {
  const u = authUser.value || {};
  return u.email || u.alamat_email || "";
});

const displayPhone = computed(() => {
  const u = authUser.value || {};
  return u.phone || u.nomor_hp || "";
});

const initials = computed(() => {
  const n = String(displayName.value || "").trim();
  if (!n) return "U";
  const parts = n.split(/\s+/).slice(0, 2);
  return parts.map((p) => p[0]?.toUpperCase() || "").join("") || "U";
});

function resolveAvatarUrl(u) {
  if (!u) return "";
  const raw =
    u.profile_photo_url ||
    u.avatar_url ||
    u.photo_url ||
    u.foto_url ||
    u.avatar ||
    u.photo ||
    u.foto ||
    u.image ||
    "";

  if (!raw) return "";
  const s = String(raw).trim();
  if (!s) return "";

  if (s.startsWith("http://") || s.startsWith("https://") || s.startsWith("/")) return s;
  return `/storage/${s}`;
}

const avatarUrl = computed(() => resolveAvatarUrl(authUser.value));

/** Active route */
const currentPath = computed(() =>
  typeof window !== "undefined" ? window.location.pathname : ""
);

const isActive = (href) => {
  const p = currentPath.value || "";
  if (!href) return false;
  return p === href || p.startsWith(href + "/");
};

/** Menu items */
const adminNav = computed(() => [
  { key: "admin-dashboard", label: "Dashboard Pendaftar", href: "/admin/dashboard", icon: Squares2X2Icon },
  { key: "admin-users", label: "Pengelolaan Akun", href: "/admin/users", icon: UsersIcon },
]);

const userNav = computed(() => [
  { key: "user-dashboard", label: "Dashboard", href: "/dashboard", icon: HomeIcon },
  { key: "user-profile", label: "Data Akun", href: "/dashboard", icon: IdentificationIcon },
]);

const navItems = computed(() => (props.variant === "user" ? userNav.value : adminNav.value));

const toggleMobile = () => (mobileOpen.value = !mobileOpen.value);

/** close total desktop sidebar */
const closeDesktop = () => emit("desktop-open-change", false);

onMounted(() => {
  if (typeof window !== "undefined") initTheme();
});
</script>

<template>
  <!-- MOBILE TOP BAR -->
  <div
    class="md:hidden sticky top-0 z-30 bg-slate-100/80 dark:bg-slate-950/70 backdrop-blur
           border-b border-slate-200/70 dark:border-slate-800"
  >
    <div class="px-4 py-3 flex items-center justify-between">
      <button
        type="button"
        @click="toggleMobile"
        class="inline-flex items-center gap-2 rounded-full px-3 py-2
               border border-slate-200/70 dark:border-slate-700/70
               bg-white/80 dark:bg-slate-900/60
               text-slate-700 dark:text-slate-100 shadow-sm"
      >
        <Bars3Icon class="w-5 h-5" />
        <span class="text-sm font-semibold">Menu</span>
      </button>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="toggleTheme"
          class="h-10 w-10 inline-flex items-center justify-center rounded-full
                 border border-slate-200/70 dark:border-slate-700/70
                 bg-white/80 dark:bg-slate-900/60
                 text-slate-700 dark:text-slate-100 shadow-sm
                 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
          :title="theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"
        >
          <SunIcon v-if="theme === 'dark'" class="w-5 h-5" />
          <MoonIcon v-else class="w-5 h-5" />
        </button>

        <div class="text-right leading-tight">
          <p class="text-[11px] text-slate-500 dark:text-slate-400">Login sebagai</p>
          <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
            {{ displayName }}
          </p>
        </div>

        <div
          class="h-10 w-10 rounded-full overflow-hidden border border-slate-200/70 dark:border-slate-700/70
                 bg-white/80 dark:bg-slate-900/60 flex items-center justify-center"
        >
          <img v-if="avatarUrl" :src="avatarUrl" alt="avatar" class="h-full w-full object-cover" />
          <span v-else class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ initials }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- MOBILE DRAWER -->
  <transition name="fade">
    <div v-if="mobileOpen" class="md:hidden fixed inset-0 z-40" aria-modal="true" role="dialog">
      <div class="absolute inset-0 bg-black/40" @click="mobileOpen = false"></div>

      <transition name="slide">
        <aside
          class="absolute left-0 top-0 h-full w-[290px] flex flex-col
                 bg-white dark:bg-slate-950
                 border-r border-slate-200/70 dark:border-slate-800
                 shadow-2xl"
        >
          <!-- Header drawer -->
          <div class="px-4 py-4 border-b border-slate-200/70 dark:border-slate-800 flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
              {{ variant === "user" ? "User Menu" : "Admin Menu" }}
            </p>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="toggleTheme"
                class="h-9 w-9 inline-flex items-center justify-center rounded-full
                       border border-slate-200/70 dark:border-slate-700/70
                       bg-white/80 dark:bg-slate-900/60
                       text-slate-700 dark:text-slate-100
                       hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                :title="theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"
              >
                <SunIcon v-if="theme === 'dark'" class="w-4 h-4" />
                <MoonIcon v-else class="w-4 h-4" />
              </button>

              <button
                type="button"
                class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900 transition"
                @click="mobileOpen = false"
                aria-label="Close"
              >
                <XMarkIcon class="w-5 h-5 text-slate-700 dark:text-slate-200" />
              </button>
            </div>
          </div>

          <!-- Body -->
          <div class="flex-1 min-h-0 overflow-y-auto px-4 py-4">
            <div class="rounded-2xl border border-sky-500/15 dark:border-slate-800 bg-white/90 dark:bg-slate-950/60 shadow-sm overflow-hidden">
              <div class="h-1.5 bg-gradient-to-r from-sky-400 via-blue-500 to-emerald-400"></div>
              <div class="p-4 flex items-center gap-3">
                <div class="h-11 w-11 rounded-full overflow-hidden border border-slate-200/70 dark:border-slate-700/70 bg-slate-50 dark:bg-slate-900 flex items-center justify-center">
                  <img v-if="avatarUrl" :src="avatarUrl" alt="avatar" class="h-full w-full object-cover" />
                  <span v-else class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ initials }}</span>
                </div>

                <div class="min-w-0">
                  <p class="text-sm font-semibold text-slate-900 dark:text-slate-50 truncate">{{ displayName }}</p>
                  <p v-if="displayEmail" class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ displayEmail }}</p>
                  <p v-if="variant === 'user' && displayPhone" class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ displayPhone }}</p>
                </div>
              </div>
            </div>

            <nav class="mt-4 space-y-1">
              <a
                v-for="item in navItems"
                :key="item.key"
                :href="item.href"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 border border-transparent transition"
                :class="isActive(item.href)
                  ? 'bg-sky-50/80 dark:bg-sky-500/10 border-sky-200/60 dark:border-sky-500/20'
                  : 'hover:bg-slate-100/80 dark:hover:bg-slate-900/60'"
              >
                <component
                  :is="item.icon"
                  class="w-5 h-5"
                  :class="isActive(item.href) ? 'text-sky-600 dark:text-sky-300' : 'text-slate-500 dark:text-slate-300'"
                />
                <span
                  class="text-sm font-semibold"
                  :class="isActive(item.href) ? 'text-sky-700 dark:text-sky-200' : 'text-slate-700 dark:text-slate-100'"
                >
                  {{ item.label }}
                </span>
              </a>
            </nav>
          </div>

          <!-- Footer -->
          <div class="mt-auto px-4 py-4 border-t border-slate-200/70 dark:border-slate-800">
            <button
              type="button"
              @click="logout()"
              class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold
                     bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-400 hover:to-red-600
                     text-white shadow-[0_12px_30px_rgba(239,68,68,0.25)] transition"
            >
              <ArrowRightOnRectangleIcon class="w-5 h-5" />
              Logout
            </button>
          </div>
        </aside>
      </transition>
    </div>
  </transition>

  <!-- DESKTOP SIDEBAR (no collapse icon) -->
  <aside
    v-if="desktopOpen"
    v-bind="attrs"
    class="hidden md:flex md:flex-col md:sticky md:top-0 md:h-[100dvh] md:w-[280px]
           border-r border-slate-200/70 dark:border-slate-800
           bg-white/90 dark:bg-slate-950/60 backdrop-blur shadow-sm"
  >
    <!-- Header -->
    <div class="px-4 py-4 border-b border-slate-200/70 dark:border-slate-800">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <div class="h-11 w-11 rounded-full overflow-hidden border border-slate-200/70 dark:border-slate-700/70 bg-slate-50 dark:bg-slate-900 flex items-center justify-center">
            <img v-if="avatarUrl" :src="avatarUrl" alt="avatar" class="h-full w-full object-cover" />
            <span v-else class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ initials }}</span>
          </div>

          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-50 truncate">{{ displayName }}</p>
            <p v-if="displayEmail" class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ displayEmail }}</p>
            <p v-if="variant === 'user' && displayPhone" class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ displayPhone }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Theme toggle (desktop) -->
          <button
            type="button"
            @click="toggleTheme"
            class="h-9 w-9 inline-flex items-center justify-center rounded-full
                   border border-slate-200/70 dark:border-slate-700/70
                   bg-white/80 dark:bg-slate-900/60
                   text-slate-700 dark:text-slate-100
                   hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            :title="theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"
          >
            <SunIcon v-if="theme === 'dark'" class="w-4 h-4" />
            <MoonIcon v-else class="w-4 h-4" />
          </button>

          <!-- Close total desktop -->
          <button
            v-if="hideable"
            type="button"
            class="rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-900 transition"
            @click="closeDesktop"
            title="Tutup Sidebar"
          >
            <XMarkIcon class="w-5 h-5 text-slate-600 dark:text-slate-200" />
          </button>
        </div>
      </div>
    </div>

    <!-- Nav -->
    <nav class="px-3 py-4 space-y-1 flex-1 min-h-0 overflow-y-auto">
      <a
        v-for="item in navItems"
        :key="item.key"
        :href="item.href"
        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 transition"
        :class="isActive(item.href)
          ? 'bg-sky-50/80 dark:bg-sky-500/10 border border-sky-200/60 dark:border-sky-500/20'
          : 'hover:bg-slate-100/80 dark:hover:bg-slate-900/60'"
      >
        <component
          :is="item.icon"
          class="w-5 h-5 shrink-0"
          :class="isActive(item.href) ? 'text-sky-600 dark:text-sky-300' : 'text-slate-500 dark:text-slate-300'"
        />
        <span
          class="text-sm font-semibold"
          :class="isActive(item.href) ? 'text-sky-700 dark:text-sky-200' : 'text-slate-700 dark:text-slate-100'"
        >
          {{ item.label }}
        </span>
      </a>

      <a
        v-if="variant === 'user'"
        href="/dashboard"
        class="mt-3 block rounded-xl px-3 py-2.5 text-sm font-semibold
               bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-400 hover:to-blue-500
               text-white shadow-[0_12px_30px_rgba(37,99,235,0.25)] transition"
      >
        Buka Dashboard
      </a>
    </nav>

    <!-- Footer logout -->
    <div class="mt-auto px-3 py-4 border-t border-slate-200/70 dark:border-slate-800">
      <button
        type="button"
        @click="logout()"
        class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold
               bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-400 hover:to-red-600
               text-white shadow-[0_12px_30px_rgba(239,68,68,0.25)] transition"
      >
        <ArrowRightOnRectangleIcon class="w-5 h-5" />
        Logout
      </button>
    </div>
  </aside>
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
.slide-enter-active,
.slide-leave-active {
  transition: transform 200ms ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-12px);
}
</style>
