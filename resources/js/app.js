// Sidebar (mobile)
import { initCascades } from './cascade';
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebar-backdrop');

function toggleSidebar() {
    if (!sidebar || !backdrop) return;
    sidebar.classList.toggle('-translate-x-full');
    backdrop.classList.toggle('hidden');
}

document.querySelectorAll('[data-sidebar-toggle]').forEach((el) => el.addEventListener('click', toggleSidebar));
backdrop?.addEventListener('click', toggleSidebar);

// Konfirmasi sebelum submit (hapus data, dll.)
document.addEventListener('submit', (e) => {
    const message = e.target?.dataset?.confirm;
    if (message && !window.confirm(message)) {
        e.preventDefault();
    }
});

// Auto-submit filter
document.querySelectorAll('[data-autosubmit]').forEach((el) => {
    el.addEventListener('change', () => el.form?.submit());
});

// Tampil/sembunyi password
document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const input = document.querySelector(btn.dataset.togglePassword);
        if (input) input.type = input.type === 'password' ? 'text' : 'password';
    });
});

// Tutup flash message
document.querySelectorAll('[data-dismiss]').forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('[role="alert"]')?.remove());
});
initCascades();

window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});