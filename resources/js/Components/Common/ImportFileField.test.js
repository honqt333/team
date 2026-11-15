import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { describe, expect, it } from 'vitest';
import ImportFileField from './ImportFileField.vue';
import ImportResult from './ImportResult.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { common: {} } },
    missingWarn: false,
    fallbackWarn: false,
});

describe('spreadsheet import controls', () => {
    it('handles invalid extensions, removal and selecting the same file again', async () => {
        const wrapper = mount(ImportFileField, {
            global: {
                plugins: [i18n],
                mocks: { $t: (key) => key },
            },
            props: { 'onUpdate:modelValue': (file) => wrapper.setProps({ modelValue: file }) },
        });
        const drop = (file) =>
            wrapper.get('.border-dashed').trigger('drop', { dataTransfer: { files: [file] } });
        await drop(new File(['x'], 'bad.pdf'));
        expect(wrapper.emitted('invalid')).toHaveLength(1);
        const file = new File(['rows'], 'data.CSV');
        await drop(file);
        expect(wrapper.text()).toContain(file.name);
        await wrapper.get('button').trigger('click');
        expect(wrapper.text()).not.toContain(file.name);
        await drop(file);
        expect(wrapper.text()).toContain(file.name);
    });

    it('shows both the imported count and all returned row errors', () => {
        const wrapper = mount(ImportResult, {
            props: { result: { imported: 2, errors: ['Duplicate phone', 'Missing name'] } },
            global: { mocks: { $t: (key, args) => (args ? String(args.count) : key) } },
        });
        expect(wrapper.get('p').text()).toBe('2');
        expect(wrapper.findAll('li').map((row) => row.text())).toEqual([
            'Duplicate phone',
            'Missing name',
        ]);
    });
});
