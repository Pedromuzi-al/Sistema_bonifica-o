import { test } from 'node:test';
import assert from 'node:assert/strict';
import ideaWizard from '../resources/js/idea-wizard.js';

function wizardWithFields(valid = true) {
    const focused = [];
    const inputs = [{ checkValidity: () => valid, reportValidity: () => focused.push('invalid'), checked: true, setCustomValidity: () => {} }];
    const section = { querySelectorAll: () => inputs };
    const wizard = ideaWizard(1);
    wizard.$root = { querySelector: selector => selector.endsWith('legend') ? { focus: () => focused.push('legend') } : section };
    wizard.$el = { querySelector: () => null };
    wizard.$nextTick = callback => callback();
    return { wizard, focused };
}

test('Continuar valida o formulário mesmo quando o evento parte de um botão filho', () => {
    const { wizard, focused } = wizardWithFields();
    wizard.next();
    assert.equal(wizard.step, 2);
    assert.deepEqual(focused, ['legend']);
});

test('Campos inválidos mantêm a etapa e recebem a indicação de erro', () => {
    const { wizard, focused } = wizardWithFields(false);
    wizard.next();
    assert.equal(wizard.step, 1);
    assert.deepEqual(focused, ['invalid']);
});

test('Enviar revisa todas as etapas e impede envio se uma etapa estiver inválida', () => {
    const { wizard } = wizardWithFields(false);
    let prevented = false;
    wizard.submit({ preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(wizard.step, 1);
});
