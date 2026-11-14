import { describe, expect, it, vi } from 'vitest';
import { createI18n } from 'vue-i18n';
import { mount } from '@vue/test-utils';
import StandardPrintControls from './StandardPrintControls.vue';

const global = {
    plugins: [
        createI18n({
            legacy: false,
            locale: 'en',
            messages: {
                en: {
                    common: {
                        print: 'print',
                        close: 'close',
                        back: 'back',
                        print_scale: 'Print Scale',
                    },
                },
            },
        }),
    ],
};

describe('shared print controls', () => {
    it.each(['inline', 'floating'])('calls callbacks exactly once in %s layout', async (layout) => {
        const onPrint = vi.fn();
        const onClose = vi.fn();
        const wrapper = mount(StandardPrintControls, {
            global,
            props: { layout, onPrint, onClose },
        });
        const buttons = wrapper.findAll('button');
        const print = buttons.find((button) => button.text() === 'print')!;
        const close = buttons.find((button) => ['close', 'back'].includes(button.text()))!;
        await print.trigger('click');
        await close.trigger('click');
        expect(onPrint).toHaveBeenCalledTimes(1);
        expect(onClose).toHaveBeenCalledTimes(1);
        wrapper.unmount();
    });
    it('uses the native print fallback once without a callback', async () => {
        const print = vi.spyOn(window, 'print').mockImplementation(() => {});
        const wrapper = mount(StandardPrintControls, { global });
        await wrapper.get('button').trigger('click');
        expect(print).toHaveBeenCalledTimes(1);
        print.mockRestore();
        wrapper.unmount();
    });
    it.each(['inline', 'floating'])(
        'emits clamped update:scale (70-100) and resets to 100 in %s layout',
        async (layout) => {
            const wrapper = mount(StandardPrintControls, {
                global,
                props: { layout, scale: 85 },
            });
            const range = wrapper.get('input[type="range"]');
            await range.setValue('75');
            expect(wrapper.emitted('update:scale')?.[0]).toEqual([75]);

            const resetBtn = wrapper
                .findAll('button')
                .find((button) => button.text().includes('85%'))!;
            await resetBtn.trigger('click');
            expect(wrapper.emitted('update:scale')?.[1]).toEqual([100]);
            wrapper.unmount();
        }
    );
});
