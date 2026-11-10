import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';
import DangerButton from './DangerButton.vue';

describe('legacy button compatibility', () => {
    it.each([
        [PrimaryButton, 'submit', 'action'],
        [SecondaryButton, 'button', 'secondary'],
        [DangerButton, 'submit', 'danger'],
    ])(
        'preserves defaults and forwards attributes, slots and clicks',
        async (component, type, variant) => {
            const onClick = vi.fn();
            const wrapper = mount(component, {
                attrs: { onClick, name: 'save' },
                slots: { default: 'Save' },
            });
            expect(wrapper.get('button').attributes('type')).toBe(type);
            expect(wrapper.get('button').attributes('name')).toBe('save');
            expect(wrapper.classes()).toContain(`app-btn--${variant}`);
            expect(wrapper.text()).toBe('Save');
            await wrapper.trigger('click');
            expect(onClick).toHaveBeenCalledTimes(1);
            await wrapper.setProps({ type: 'button' });
            expect(wrapper.get('button').attributes('type')).toBe('button');
            wrapper.unmount();
        }
    );
    it('preserves disabled state', () => {
        const wrapper = mount(PrimaryButton, { attrs: { disabled: true } });
        expect((wrapper.get('button').element as HTMLButtonElement).disabled).toBe(true);
        wrapper.unmount();
    });
});
