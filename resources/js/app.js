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

Alpine.start();
