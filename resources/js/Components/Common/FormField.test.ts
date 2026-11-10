import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import FormField from './FormField.vue';

describe('FormField', () => {
    it('renders label and asterisk when required', () => {
        const wrapper = mount(FormField, {
            props: {
                label: 'اسم العميل',
                required: true,
            },
        });

        expect(wrapper.text()).toContain('اسم العميل');
        expect(wrapper.text()).toContain('*');
    });

    it('renders error message and applies role alert', () => {
        const wrapper = mount(FormField, {
            props: {
                label: 'البريد',
                error: 'هذا الحقل مطلوب',
            },
        });

        const alert = wrapper.find('[role="alert"]');
        expect(alert.exists()).toBe(true);
        expect(alert.text()).toContain('هذا الحقل مطلوب');
    });

    it('renders hint when no error is present', () => {
        const wrapper = mount(FormField, {
            props: {
                hint: 'أدخل البريد الإلكتروني للشركة',
            },
        });

        expect(wrapper.text()).toContain('أدخل البريد الإلكتروني للشركة');
    });
});
