

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

Alpine.data('ideaWizard', (initialStep = 1) => ({
    step: initialStep,
    sectorError: false,
    move(step) {
        this.step = step;
        this.$nextTick(() => this.$el.querySelector(`[data-step="${step}"] legend`)?.focus());
    },
    validate(step) {
        const section = this.$el.querySelector(`[data-step="${step}"]`);
        if (step === 2) {
            const choices = [...section.querySelectorAll('input[name="beneficiary_sectors[]"]')];
            this.sectorError = !choices.some(input => input.checked);
            choices.forEach(input => input.setCustomValidity(''));
            if (this.sectorError && choices[0]) {
                choices[0].setCustomValidity('Marque pelo menos um setor que pode se beneficiar.');
            }
        }
        const invalid = [...section.querySelectorAll('input, textarea, select')].find(input => !input.checkValidity());
        if (invalid) {
            this.step = step;
            this.$nextTick(() => invalid.reportValidity());
            return false;
        }
        return !this.sectorError || step !== 2;
    },
    next() {
        if (this.validate(this.step)) this.move(this.step + 1);
    },
    submit(event) {
        for (let step = 1; step <= 3; step++) {
            if (!this.validate(step)) {
                event.preventDefault();
                return;
            }
        }
    },
}));

Alpine.start();
