import ideaWizard from './idea-wizard';


import Alpine from 'alpinejs';

window.Alpine = Alpine;

const themeStorageKey = 'conted-theme';

function syncThemeControls() {
    const isDark = document.documentElement.classList.contains('dark');

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const label = isDark ? 'Ativar modo claro' : 'Ativar modo escuro';

        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', isDark.toString());
        button.querySelector('[data-theme-label]')?.replaceChildren(
            document.createTextNode(isDark ? 'Modo claro' : 'Modo escuro'),
        );
        button.querySelector('[data-theme-icon="moon"]')?.classList.toggle('hidden', isDark);
        button.querySelector('[data-theme-icon="sun"]')?.classList.toggle('hidden', !isDark);
    });
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-toggle]');

    if (!button) {
        return;
    }

    const isDark = document.documentElement.classList.toggle('dark');

    window.localStorage.setItem(themeStorageKey, isDark ? 'dark' : 'light');
    syncThemeControls();
});

syncThemeControls();

Alpine.data('ideaWizard', ideaWizard);

Alpine.data('layoutSidebar', () => ({
    sidebarOpen: false,
    mobile: window.matchMedia('(max-width: 1023px)').matches,
    media: null,
    mediaListener: null,
    lastFocused: null,
    init() {
        this.media = window.matchMedia('(max-width: 1023px)');
        this.mediaListener = event => {
            const active = document.activeElement === document.body ? this.lastFocused : document.activeElement;
            const focusWasInSidebar = this.$refs.sidebar.contains(active);
            const focusWasOnToggle = active === this.$refs.menuButton;
            this.mobile = event.matches;
            this.sidebarOpen = false;
            document.documentElement.style.overflow = '';
            this.$nextTick(() => {
                if (this.mobile && focusWasInSidebar) {
                    this.$refs.menuButton.focus();
                } else if (!this.mobile && (focusWasOnToggle || active?.classList.contains('sidebar-close'))) {
                    (this.$refs.sidebar.querySelector('[aria-current="page"]') ?? this.$refs.sidebar.querySelector('nav a'))?.focus();
                }
            });
        };
        this.media.addEventListener('change', this.mediaListener);
    },
    destroy() {
        this.media?.removeEventListener('change', this.mediaListener);
        document.documentElement.style.overflow = '';
    },
    openMenu() {
        if (!this.mobile) return;
        this.sidebarOpen = true;
        document.documentElement.style.overflow = 'hidden';
        this.$nextTick(() => this.$refs.sidebar.querySelector('button').focus());
    },
    closeMenu() {
        if (!this.sidebarOpen) return;
        this.sidebarOpen = false;
        document.documentElement.style.overflow = '';
        this.$nextTick(() => this.$refs.menuButton.focus());
    },
    trapFocus(event) {
        if (!this.mobile || !this.sidebarOpen || event.key !== 'Tab') return;
        const items = Array.from(this.$refs.sidebar.querySelectorAll('a[href], button:not([disabled])')).filter(item => item.getClientRects().length && !item.closest('[inert]'));
        const first = items[0], last = items.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    },
}));

Alpine.start();
