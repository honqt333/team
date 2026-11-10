import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AppAttachmentManager from './AppAttachmentManager.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string, params?: Record<string, any>) => {
            if (params) {
                return Object.entries(params).reduce(
                    (str, [k, v]) => str.replace(`{${k}}`, String(v)),
                    key
                );
            }
            return key;
        },
    }),
}));

vi.mock('@/Composables/useConfirm', () => ({
    useConfirm: () => ({
        confirm: vi.fn().mockResolvedValue(true),
    }),
}));

vi.mock('@/Composables/useToast', () => ({
    useToast: () => ({
        success: vi.fn(),
        error: vi.fn(),
    }),
}));

describe('AppAttachmentManager', () => {
    const sampleAttachments = [
        {
            id: 1,
            file_name: 'invoice_receipt.pdf',
            file_size: 1048576,
            file_type: 'pdf',
            path: 'attachments/receipt.pdf',
            user: { name: 'أحمد' },
        },
        {
            id: 2,
            file_name: 'car_photo.jpg',
            file_size: 524288,
            file_type: 'image/jpeg',
            path: 'attachments/photo.jpg',
            user: { name: 'سارة' },
        },
    ];

    it('renders empty state when no attachments provided', () => {
        const wrapper = mount(AppAttachmentManager, {
            props: {
                attachments: [],
            },
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
                stubs: {
                    BaseModal: true,
                },
            },
        });

        expect(wrapper.text()).toContain('common.no_attachments');
    });

    it('renders attachments list with file names', () => {
        const wrapper = mount(AppAttachmentManager, {
            props: {
                attachments: sampleAttachments,
            },
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
                stubs: {
                    BaseModal: true,
                },
            },
        });

        expect(wrapper.text()).toContain('invoice_receipt.pdf');
        expect(wrapper.text()).toContain('car_photo.jpg');
        expect(wrapper.text()).toContain('أحمد');
        expect(wrapper.text()).toContain('سارة');
    });

    it('hides upload button in read only mode', () => {
        const wrapper = mount(AppAttachmentManager, {
            props: {
                attachments: sampleAttachments,
                isReadOnly: true,
            },
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
                stubs: {
                    BaseModal: true,
                },
            },
        });

        expect(wrapper.find('button[class*="from-blue-600"]').exists()).toBe(false);
    });
});
