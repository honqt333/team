import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import StandardPrintControls from './StandardPrintControls.vue';

describe('shared print controls', () => {
    it.each(['inline', 'floating'])('calls callbacks exactly once in %s layout', async (layout) => {
        const onPrint = vi.fn();
        const onClose = vi.fn();
        const wrapper = mount(StandardPrintControls, { props: { layout, onPrint, onClose } });
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
        const wrapper = mount(StandardPrintControls);
        await wrapper.get('button').trigger('click');
        expect(print).toHaveBeenCalledTimes(1);
        print.mockRestore();
        wrapper.unmount();
    });
});
