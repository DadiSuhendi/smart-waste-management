const menu = document.querySelector("[data-toggle-menu]");
const sidebar = document.querySelector("#sidebar");
const shell = document.querySelector(".app-shell");
const mobileLayout = window.matchMedia("(max-width: 760px)");
const setMenu = (open) => {
    document.body.classList.toggle("sidebar-open", open);
    menu?.setAttribute("aria-expanded", String(open));
    if (sidebar) sidebar.inert = mobileLayout.matches && !open;
    if (shell) shell.inert = open;
    if (open) sidebar?.querySelector("[data-close-menu]")?.focus();
    else menu?.focus();
};
if (sidebar) sidebar.inert = mobileLayout.matches;
mobileLayout.addEventListener("change", () => setMenu(false));
menu?.addEventListener("click", () =>
    setMenu(!document.body.classList.contains("sidebar-open")),
);
document
    .querySelectorAll("[data-close-menu]")
    .forEach((button) =>
        button.addEventListener("click", () => setMenu(false)),
    );
document.addEventListener("keydown", (e) => {
    if (!document.body.classList.contains("sidebar-open")) return;
    if (e.key === "Escape") setMenu(false);
    if (e.key === "Tab") {
        const items = [...sidebar.querySelectorAll("a, button")].filter(
            (item) => !item.disabled && item.getClientRects().length,
        );
        const first = items[0];
        const last = items.at(-1);
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last?.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first?.focus();
        }
    }
});
document
    .querySelectorAll("[data-autosubmit]")
    .forEach((el) =>
        el.addEventListener("change", () => el.form.requestSubmit()),
    );
document.querySelectorAll("form[data-confirm]").forEach((form) =>
    form.addEventListener("submit", (e) => {
        if (!confirm(form.dataset.confirm)) e.preventDefault();
    }),
);
const photo = document.querySelector("#photo");
const preview = document.querySelector("#photo-preview");
const detect = document.querySelector("[data-detect]");
const message = document.querySelector("[data-detect-message]");
let previewUrl;
photo?.addEventListener("change", () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    const file = photo.files[0];
    if (!file) {
        detect.disabled = true;
        return;
    }
    if (
        file.size > 5 * 1024 * 1024 ||
        !["image/jpeg", "image/png", "image/webp"].includes(file.type)
    ) {
        photo.value = "";
        detect.disabled = true;
        message.textContent =
            "Gunakan JPG, PNG, atau WebP berukuran maksimal 5 MB.";
        return;
    }
    previewUrl = URL.createObjectURL(file);
    preview.src = previewUrl;
    preview.hidden = false;
    detect.disabled = false;
    message.textContent = "Foto siap. Gunakan deteksi untuk saran kategori.";
});
detect?.addEventListener("click", async () => {
    const file = photo.files[0];
    if (!file) return;
    detect.disabled = true;
    message.textContent = "Memeriksa foto...";
    const body = new FormData();
    body.append("photo", file);
    try {
        const response = await fetch(detect.dataset.url, {
            method: "POST",
            body,
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            signal: AbortSignal.timeout(25000),
        });
        const result = await response.json();
        if (!response.ok)
            throw new Error(result.message || "Deteksi belum tersedia.");
        if (photo.files[0] !== file) return;
        const data = result.data;
        document.querySelector("#report-category").value = data.category;
        message.textContent =
            "Saran: " +
            data.label +
            " (" +
            (data.confidence * 100).toFixed(1) +
            "%). " +
            (data.needs_review
                ? "Keyakinan rendah, periksa kembali kategori."
                : "Pastikan sesuai kondisi di lokasi.");
    } catch (e) {
        message.textContent =
            e.name === "TimeoutError"
                ? "Deteksi terlalu lama. Pilih kategori secara manual."
                : e.message ||
                  "Tidak dapat terhubung. Pilih kategori secara manual.";
    } finally {
        detect.disabled = !photo.files[0];
    }
});
const gps = document.querySelector("[data-gps]");
gps?.addEventListener("click", () => {
    const note = document.querySelector("[data-gps-message]");
    if (!navigator.geolocation) {
        note.textContent = "GPS tidak tersedia. Isi koordinat secara manual.";
        return;
    }
    gps.disabled = true;
    note.textContent = "Mencari lokasi Anda...";
    navigator.geolocation.getCurrentPosition(
        (position) => {
            document.querySelector('[name="latitude"]').value =
                position.coords.latitude.toFixed(7);
            document.querySelector('[name="longitude"]').value =
                position.coords.longitude.toFixed(7);
            note.textContent =
                "Lokasi diperoleh. Perkiraan akurasi " +
                Math.round(position.coords.accuracy) +
                " meter. Pastikan titik ini adalah lokasi sampah.";
            gps.disabled = false;
        },
        (error) => {
            note.textContent =
                error.code === 1
                    ? "Akses lokasi ditolak. Izinkan melalui browser atau isi koordinat manual."
                    : "Lokasi belum ditemukan. Coba lagi atau isi koordinat manual.";
            gps.disabled = false;
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
    );
});
const status = document.querySelector("[data-status-select]");
const note = document.querySelector("[data-status-note]");
if (status && note) {
    const sync = () => (note.required = status.value === "rejected");
    status.addEventListener("change", sync);
    sync();
}
document
    .querySelector("[data-report-form]")
    ?.addEventListener("submit", (e) => {
        const button = e.currentTarget.querySelector('[type="submit"]');
        if (e.currentTarget.checkValidity()) {
            button.disabled = true;
            button.textContent = "Menyimpan laporan...";
        }
    });
