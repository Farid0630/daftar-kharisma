export const csrf = () =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") || "";

export async function logout(redirect = "/login") {
    try {
        await fetch("/logout", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
            credentials: "same-origin",
        });
    } catch (e) {
        // ignore
    }
    window.location.href = redirect;
}
