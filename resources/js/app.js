import './bootstrap';

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-loading-form]')) return;

    const button = form.querySelector('button[type="submit"]');
    if (!button) return;

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    const label = button.querySelector('[data-submit-label]');
    if (label) label.textContent = button.dataset.loadingLabel || 'Please wait...';
});

function setSidebarOpen(open) {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    if (!sidebar || !backdrop || !toggle) return;

    sidebar.classList.toggle('-translate-x-full', !open);
    backdrop.classList.toggle('hidden', !open);
    backdrop.setAttribute('aria-hidden', String(!open));
    toggle.setAttribute('aria-expanded', String(open));

    if (open) sidebar.querySelector('a')?.focus();
    else toggle.focus();
}

function setPublicNavOpen(open) {
    const menu = document.getElementById('public-mobile-menu');
    const toggle = document.querySelector('[data-public-nav-toggle]');
    if (!menu || !toggle) return;

    menu.classList.toggle('hidden', !open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
}

/**
 * data-copy="<text>" — copies the text to the clipboard and briefly shows "Copied!".
 * Used on payment link copy buttons throughout the dashboard.
 */
document.addEventListener('click', async (e) => {
    const el = e.target.closest('[data-copy]');
    if (!el) return;
    e.preventDefault();
    try {
        await navigator.clipboard.writeText(el.dataset.copy);
    } catch (_) {
        return;
    }
    const label = el.querySelector('[data-copy-label]') || el;
    const original = label.textContent;
    label.textContent = 'Copied!';
    setTimeout(() => label.textContent = original, 1500);
});

document.addEventListener('click', (event) => {
    const confirmButton = event.target.closest('[data-confirm]');
    if (confirmButton && !window.confirm(confirmButton.dataset.confirm)) {
        event.preventDefault();
        return;
    }

    const navigationTarget = event.target.closest('[data-href]');
    if (navigationTarget && !event.target.closest('a, button, input, select, textarea')) {
        window.location.assign(navigationTarget.dataset.href);
    }

    if (event.target.closest('[data-sidebar-toggle]')) {
        const sidebar = document.getElementById('sidebar');
        setSidebarOpen(sidebar?.classList.contains('-translate-x-full') ?? false);
    }

    if (event.target.closest('[data-sidebar-close]')) {
        setSidebarOpen(false);
    }

    if (event.target.closest('[data-public-nav-toggle]')) {
        const menu = document.getElementById('public-mobile-menu');
        setPublicNavOpen(menu?.classList.contains('hidden') ?? false);
    }

    if (event.target.closest('#public-mobile-menu a')) {
        setPublicNavOpen(false);
        document.querySelector('[data-public-nav-toggle]')?.focus();
    }

    if (event.target.closest('[data-toggle-secret]')) {
        const secret = document.getElementById('secret');
        if (secret) secret.type = secret.type === 'password' ? 'text' : 'password';
    }

    if (event.target.closest('[data-reload-page]')) {
        window.location.reload();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    const backdrop = document.getElementById('backdrop');
    if (backdrop && !backdrop.classList.contains('hidden')) {
        setSidebarOpen(false);
    }

    const menu = document.getElementById('public-mobile-menu');
    if (menu && !menu.classList.contains('hidden')) {
        setPublicNavOpen(false);
        document.querySelector('[data-public-nav-toggle]')?.focus();
    }
});

document.addEventListener('change', (event) => {
    if (event.target.matches('[data-submit-on-change]')) {
        event.target.form?.requestSubmit();
    }
});

document.addEventListener('input', (event) => {
    if (!event.target.matches('[data-amount-preview]')) return;
    const preview = document.getElementById('preview-amount');
    const amount = Number.parseFloat(event.target.value || 0);
    if (preview) {
        preview.textContent = `₹${amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
});
