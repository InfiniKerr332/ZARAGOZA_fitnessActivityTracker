/**
 * FitTrack Pro - Main JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initUserMenu();
    initModals();
    initFlashMessages();
});

/* Sidebar */
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const mobileBtn = document.getElementById('mobileMenuBtn');
    if (!sidebar) return;
    toggle?.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
    mobileBtn?.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && sidebar.classList.contains('open') &&
            !sidebar.contains(e.target) && e.target !== mobileBtn) {
            sidebar.classList.remove('open');
        }
    });
}

/* User Dropdown */
function initUserMenu() {
    const btn = document.getElementById('userMenuBtn');
    const dropdown = document.getElementById('userDropdown');
    if (!btn || !dropdown) return;
    btn.addEventListener('click', (e) => { e.stopPropagation(); dropdown.classList.toggle('show'); });
    document.addEventListener('click', () => dropdown.classList.remove('show'));
}

/* Modal System */
function initModals() {
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.modal));
    });
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(overlay.id); });
    });
}
function openModal(id) {
    document.getElementById(id)?.classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id)?.classList.remove('show');
    document.body.style.overflow = '';
}

/* Flash Messages - auto dismiss */
function initFlashMessages() {
    document.querySelectorAll('.flash-message').forEach(el => {
        setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 5000);
    });
}

/* Verification Code Input Handler */
function initCodeInputs() {
    const inputs = document.querySelectorAll('.code-input');
    inputs.forEach((input, idx) => {
        input.addEventListener('input', (e) => {
            const val = e.target.value.replace(/\D/g, '');
            e.target.value = val.slice(0, 1);
            if (val && idx < inputs.length - 1) inputs[idx + 1].focus();
            updateHiddenCode();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && idx > 0) {
                inputs[idx - 1].focus();
                inputs[idx - 1].value = '';
            }
        });
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const data = (e.clipboardData.getData('text')).replace(/\D/g, '').slice(0, 6);
            data.split('').forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
            if (data.length > 0) inputs[Math.min(data.length, inputs.length) - 1].focus();
            updateHiddenCode();
        });
    });
}
function updateHiddenCode() {
    const inputs = document.querySelectorAll('.code-input');
    const hidden = document.getElementById('verification_code');
    if (hidden) hidden.value = Array.from(inputs).map(i => i.value).join('');
}

/* Resend Timer */
function startResendTimer(seconds) {
    const btn = document.getElementById('resendBtn');
    const timer = document.getElementById('resendTimer');
    if (!btn || !timer) return;
    btn.disabled = true;
    let remaining = seconds;
    timer.textContent = `Resend available in ${remaining}s`;
    const interval = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
            clearInterval(interval);
            btn.disabled = false;
            timer.textContent = '';
        } else {
            timer.textContent = `Resend available in ${remaining}s`;
        }
    }, 1000);
}

/* Expiry Timer */
function startExpiryTimer(seconds) {
    const el = document.getElementById('expiryTimer');
    if (!el) return;
    let remaining = seconds;
    function formatTime(s) {
        const min = Math.floor(s / 60);
        const sec = s % 60;
        return `${min}:${sec.toString().padStart(2, '0')}`;
    }
    el.textContent = `Code expires in ${formatTime(remaining)}`;
    const interval = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
            clearInterval(interval);
            el.textContent = 'Code expired';
            el.style.color = '#ef4444';
        } else {
            el.textContent = `Code expires in ${formatTime(remaining)}`;
        }
    }, 1000);
}

/* Delete Confirmation */
function confirmDelete(form, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        form.submit();
    }
    return false;
}

/* AJAX helper */
async function fetchJSON(url, options = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const defaults = {
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf || '' },
    };
    const res = await fetch(url, { ...defaults, ...options });
    return res.json();
}
