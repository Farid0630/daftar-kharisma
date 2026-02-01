<script setup>
import { computed, ref, onMounted } from "vue";
import AppSidebar from "@/components/layout/AppSidebar.vue";

import AdminDashboard from "./pages/AdminDashboard.vue";
import AdminUsers from "./pages/AdminUsers.vue";

const pageKey = computed(() => {
  const el = document.getElementById("admin-app");
  const fromData = (el?.dataset?.page || "").toLowerCase();

  if (fromData === "users") return "users";
  if (fromData === "dashboard") return "dashboard";

  const path = window.location.pathname || "";
  if (path.startsWith("/admin/users")) return "users";
  return "dashboard";
});

const PageComponent = computed(() => (pageKey.value === "users" ? AdminUsers : AdminDashboard));

const authUser = ref({ name: "User", photo_url: "", role: "admin" });

onMounted(() => {
  if (window.__AUTH_USER__) {
    const u = window.__AUTH_USER__;
    authUser.value = {
      name: u?.name || u?.nama || "User",
      photo_url: u?.photo_url || u?.avatar_url || u?.foto_url || "",
      role: u?.role || "admin",
    };
    return;
  }

  if (window.__AUTH__?.user) {
    const u = window.__AUTH__.user;
    authUser.value = {
      name: u?.name || u?.nama || "User",
      photo_url: u?.profile_photo_url || u?.avatar_url || u?.photo_url || "",
      role: u?.role || "admin",
    };
    return;
  }

  const el = document.getElementById("admin-app");
  const name = el?.dataset?.userName || "User";
  const photo = el?.dataset?.userPhoto || "";
  authUser.value = { name, photo_url: photo, role: "admin" };
});

/** ✅ desktop open state */
const desktopSidebarOpen = ref(true);
</script>

<template>
  <div class="h-[100dvh] w-full overflow-hidden bg-slate-100 dark:bg-slate-950">
    <div class="h-full w-full md:flex">
      <!-- ✅ DESKTOP sidebar: padding hilang total saat closed -->
      <AppSidebar
        class="md:shrink-0"
        variant="admin"
        :user="authUser"
        :desktopOpen="desktopSidebarOpen"
        :hideable="true"
        @desktop-open-change="desktopSidebarOpen = $event"
      />

      <main
        class="w-full min-w-0 h-full overflow-y-auto transition-[padding] duration-200"
        :class="desktopSidebarOpen ? '' : ''"
      >
        <!-- ✅ Tombol untuk membuka lagi saat sidebar tertutup total (desktop) -->
        <button
          v-if="!desktopSidebarOpen"
          type="button"
          @click="desktopSidebarOpen = true"
          class="hidden md:inline-flex fixed left-4 top-4 z-[60] items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold
                 border border-slate-200/70 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/70
                 text-slate-800 dark:text-slate-100 hover:shadow transition"
          title="Buka Sidebar"
        >
          ☰ Menu
        </button>

        <div class="px-4 md:px-6 py-6">
          <component :is="PageComponent" />
        </div>
      </main>
    </div>
  </div>
</template>
