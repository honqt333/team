import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { describe, expect, it } from 'vitest';
import AppFileUpload from './AppFileUpload.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: {} },
    missingWarn: false,
    fallbackWarn: false,
});

const globalMocks = {
    plugins: [i18n],
    mocks: {
        $t: (key) => key,
    },
};

describe('AppFileUpload', () => {
    it('accepts valid files via dropzone and allows removing them', async () => {
        const wrapper = mount(AppFileUpload, {
            props: {
                accept: '.csv,.xlsx',
                maxSizeMb: 5,
                'onUpdate:modelValue': (val) => wrapper.setProps({ modelValue: val }),
            },
            global: globalMocks,
        });

        const dropzone = wrapper.find('.border-dashed');
        const validFile = new File(['a,b,c'], 'clients.csv', { type: 'text/csv' });

        await dropzone.trigger('drop', { dataTransfer: { files: [validFile] } });

        expect(wrapper.emitted('select')).toHaveLength(1);
        expect(wrapper.text()).toContain('clients.csv');

        await wrapper.find('button').trigger('click');
        expect(wrapper.text()).not.toContain('clients.csv');
    });

    it('rejects unsupported extensions and files exceeding maxSizeMb', async () => {
        const wrapper = mount(AppFileUpload, {
            props: {
                accept: 'image/png,image/jpeg',
                maxSizeMb: 1,
                'onUpdate:modelValue': (val) => wrapper.setProps({ modelValue: val }),
            },
            global: globalMocks,
        });

        const dropzone = wrapper.find('.border-dashed');

        const svgFile = new File(['<svg></svg>'], 'bad.svg', { type: 'image/svg+xml' });
        await dropzone.trigger('drop', { dataTransfer: { files: [svgFile] } });
        expect(wrapper.emitted('invalid')?.[0]?.[0]?.reason).toBe('type');

        const hugeFile = new File([new Uint8Array(2 * 1024 * 1024)], 'big.png', {
            type: 'image/png',
        });
        await dropzone.trigger('drop', { dataTransfer: { files: [hugeFile] } });
        expect(wrapper.emitted('invalid')?.[1]?.[0]?.reason).toBe('size');
    });

    it('supports multiple attachments and image variant reset', async () => {
        const multiWrapper = mount(AppFileUpload, {
            props: {
                multiple: true,
                accept: '.pdf,image/*',
                modelValue: [],
                'onUpdate:modelValue': (val) => multiWrapper.setProps({ modelValue: val }),
            },
            global: globalMocks,
        });

        const dropzone = multiWrapper.find('.border-dashed');
        const f1 = new File(['1'], 'doc1.pdf', { type: 'application/pdf' });
        const f2 = new File(['2'], 'photo.png', { type: 'image/png' });

        await dropzone.trigger('drop', { dataTransfer: { files: [f1, f2] } });
        expect(multiWrapper.text()).toContain('doc1.pdf');
        expect(multiWrapper.text()).toContain('photo.png');

        const imageWrapper = mount(AppFileUpload, {
            props: {
                variant: 'image',
                existingUrl: '/storage/brand/logo.png',
                uploadButtonText: 'Update',
                resetButtonText: 'Reset',
            },
            global: globalMocks,
        });

        expect(imageWrapper.find('img').attributes('src')).toBe('/storage/brand/logo.png');
        const buttons = imageWrapper.findAll('button');
        await buttons[1].trigger('click');
        expect(imageWrapper.emitted('remove-existing')).toHaveLength(1);
    });
});
