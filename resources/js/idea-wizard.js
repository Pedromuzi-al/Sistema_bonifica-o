export default (initialStep = 1) => ({
    step: initialStep,
    sectorError: false,
    move(step) {
        this.step = step;
        this.$nextTick(() => this.$root.querySelector(`[data-step="${step}"] legend`)?.focus());
    },
    validate(step) {
        const section = this.$root.querySelector(`[data-step="${step}"]`);
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
});
