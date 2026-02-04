// resources/js/app.js
import "./bootstrap";
import "../css/app.css";
import { createApp, h } from "vue";
import PrimeVue from "primevue/config";

// Public / PMB
import LandingPage from "./pages/LandingPage.vue";
import PmbRegisterPage from "./pages/pmb/mandiri/PmbRegisterPage.vue";
import PmbRegisterKipPage from "./pages/pmb/kip/PmbRegisterKipPage.vue";
import PmbRegisterYayasanPage from "./pages/pmb/yayasan/PmbRegisterYayasanPage.vue";

// Auth
import Login from "./pages/auth/Login.vue";

// Admin (ENTRY BARU)
import AdminEntry from "./admin/AdminEntry.vue";

// User dashboards
import UserDashboard from "./user/pages/UserDashboard.vue";

// Helper: mount Vue component to selector if element exists
function mountIf(selector, component, pluginSetup) {
    const el = document.querySelector(selector);
    if (!el) return false;

    const app = createApp(component);
    if (pluginSetup && typeof pluginSetup === "function") pluginSetup(app);
    app.mount(selector);
    return true;
}

// 1) Login page
if (mountIf("#auth-login", Login)) {
    // ok
}

// 2) ADMIN entry (ini yang akan render sidebar + page)
else if (
    mountIf("#admin-app", AdminEntry, (app) =>
        app.use(PrimeVue, { unstyled: true }),
    )
) {
    // ok
}

// 3) Dashboard untuk user (default UserDashboard)
else if (
    mountIf("#dashboard-app", UserDashboard, (app) =>
        app.use(PrimeVue, { unstyled: true }),
    )
) {
    // ok
}

// 4) Fallback: landing / pmb register pages
else {
    const path = window.location.pathname;
    let RootComponent = LandingPage;

    if (path.startsWith("/pmb/register/kip")) {
        RootComponent = PmbRegisterKipPage;
    } else if (path.startsWith("/pmb/register/yayasan")) {
        RootComponent = PmbRegisterYayasanPage;
    } else if (path.startsWith("/pmb/register")) {
        RootComponent = PmbRegisterPage;
    }

    const app = createApp(RootComponent);
    app.use(PrimeVue, { unstyled: true });
    app.mount("#app");
}
