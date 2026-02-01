<script setup>
import { computed, reactive, ref } from "vue";
import {
  LockClosedIcon,
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  MoonIcon,
  SunIcon,
} from "@heroicons/vue/24/outline";

// ✅ Layout global
import AppHeader from "@/components/layout/AppHeader.vue";
import AppFooter from "@/components/layout/AppFooter.vue";

const form = reactive({
  email: "",
  password: "",
  remember: false,
});

const showPassword = ref(false);
const loading = ref(false);
const serverError = ref("");
const fieldErrors = ref({});

const isDark = computed(() =>
  document.documentElement.classList.contains("dark")
);

const toggleTheme = () => {
  const root = document.documentElement;
  const nextDark = !root.classList.contains("dark");
  root.classList.toggle("dark", nextDark);
  try {
    localStorage.setItem("theme", nextDark ? "dark" : "light");
  } catch (e) {}
};

const csrf = () =>
  document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content") || "";

const handleLogin = async () => {
  serverError.value = "";
  fieldErrors.value = {};
  loading.value = true;

  try {
    const res = await fetch("/login", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": csrf(),
      },
      body: JSON.stringify({
        email: form.email,
        password: form.password,
        remember: form.remember,
      }),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      if (data?.errors) fieldErrors.value = data.errors;
      serverError.value =
        data?.message || "Login gagal. Periksa email/kata sandi.";
      return;
    }

    window.location.href = data.redirect || data.redirect_to || "/dashboard";
  } catch (e) {
    serverError.value = "Gagal terhubung ke server.";
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <!-- ✅ Layout halaman: header - content - footer -->
  <div class="min-h-screen flex flex-col bg-slate-100 dark:bg-slate-950">
    <AppHeader />

    <main class="flex-1 flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-md">
        <div class="flex items-start justify-between mb-5">
          <div>
            <p class="text-[11px] tracking-[0.22em] text-slate-500 dark:text-slate-400">
              PMB PORTAL
            </p>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
              Login Akun
            </h1>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
              Masuk untuk mengakses portal PMB.
            </p>
          </div>

          <button
            type="button"
            @click="toggleTheme"
            class="inline-flex items-center gap-2 rounded-full px-3 py-2 border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70 shadow-sm hover:shadow transition"
          >
            <SunIcon v-if="isDark" class="w-4 h-4" />
            <MoonIcon v-else class="w-4 h-4" />
            <span class="text-[11px] font-medium text-slate-700 dark:text-slate-200">
              {{ isDark ? "Terang" : "Gelap" }}
            </span>
          </button>
        </div>

        <div class="rounded-2xl border border-sky-500/15 dark:border-slate-700/80 bg-white/90 dark:bg-slate-950/60 backdrop-blur-md shadow-[0_18px_45px_rgba(15,23,42,0.12)] dark:shadow-[0_22px_60px_rgba(2,6,23,0.65)] overflow-hidden">
          <div class="h-1.5 bg-gradient-to-r from-sky-400 via-blue-500 to-emerald-400"></div>

          <form @submit.prevent="handleLogin" class="p-5 sm:p-6 space-y-4">
            <div
              v-if="serverError"
              class="rounded-xl border border-rose-200/70 dark:border-rose-400/30 bg-rose-50/70 dark:bg-rose-900/20 px-3 py-2 text-xs text-rose-700 dark:text-rose-200"
            >
              {{ serverError }}
            </div>

            <!-- Email -->
            <div class="space-y-1">
              <label class="text-xs font-medium text-slate-700 dark:text-slate-200">
                Email / Username <span class="text-rose-500">*</span>
              </label>

              <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 dark:border-slate-700/80 bg-white dark:bg-slate-900/70 px-3 py-2.5 focus-within:ring-2 focus-within:ring-sky-400 focus-within:border-sky-400 dark:focus-within:ring-sky-500">
                <EnvelopeIcon class="w-4 h-4 text-slate-400 dark:text-slate-300" />
                <input
                  v-model="form.email"
                  type="text"
                  autocomplete="username"
                  class="w-full bg-transparent outline-none text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400"
                  placeholder="email atau username"
                />
              </div>

              <p v-if="fieldErrors.email?.[0]" class="text-[11px] text-rose-500">
                {{ fieldErrors.email[0] }}
              </p>
            </div>

            <!-- Password -->
            <div class="space-y-1">
              <label class="text-xs font-medium text-slate-700 dark:text-slate-200">
                Kata Sandi <span class="text-rose-500">*</span>
              </label>

              <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 dark:border-slate-700/80 bg-white dark:bg-slate-900/70 px-3 py-2.5 focus-within:ring-2 focus-within:ring-sky-400 focus-within:border-sky-400 dark:focus-within:ring-sky-500">
                <LockClosedIcon class="w-4 h-4 text-slate-400 dark:text-slate-300" />

                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  class="w-full bg-transparent outline-none text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400"
                  placeholder="Masukkan kata sandi"
                />

                <button
                  type="button"
                  class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                  @click="showPassword = !showPassword"
                  aria-label="Toggle password"
                >
                  <EyeIcon v-if="!showPassword" class="w-4 h-4 text-slate-400" />
                  <EyeSlashIcon v-else class="w-4 h-4 text-slate-400" />
                </button>
              </div>

              <p v-if="fieldErrors.password?.[0]" class="text-[11px] text-rose-500">
                {{ fieldErrors.password[0] }}
              </p>
            </div>

            <div class="flex items-center justify-between">
              <label class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <input
                  v-model="form.remember"
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 accent-sky-500"
                />
                Ingat saya
              </label>

              <button
                type="button"
                class="text-xs font-medium text-sky-600 dark:text-sky-300 hover:underline"
                @click="serverError = 'Fitur lupa kata sandi dibuat setelah backend siap.'"
              >
                Lupa kata sandi?
              </button>
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="w-full inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold tracking-wide text-white bg-gradient-to-r from-sky-500 to-blue-500 shadow-[0_12px_30px_rgba(37,99,235,0.35)] hover:from-sky-400 hover:to-blue-500 disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-950 transition"
            >
              {{ loading ? "Memproses..." : "Masuk" }}
            </button>
          </form>
        </div>
      </div>
    </main>

    <AppFooter />
  </div>
</template>
