import { describe, it, expect, vi } from 'vitest';
import { router } from '@inertiajs/vue3';
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

    it('sends one validated purchase file using the singular attachment field', async () => {
        const post = vi.spyOn(router, 'post').mockImplementation(() => {});
        const wrapper = mount(AppAttachmentManager, {
            props: {
                uploadUrl: '/upload',
                uploadField: 'attachment',
                maxSizeMb: 1,
                allowMultiple: false,
                acceptedTypes: 'application/pdf,image/png',
            },
            global: {
                stubs: { BaseModal: { template: '<div><slot/><slot name="footer"/></div>' } },
            },
        });
        const drop = async (file: File) => {
            await wrapper
                .get('input[type=file]')
                .element.parentElement?.dispatchEvent(
                    Object.assign(new Event('drop', { bubbles: true }), {
                        dataTransfer: { files: [file] },
                    })
                );
            await wrapper.vm.$nextTick();
        };
        const submit = wrapper.findAll('button').at(-1)!;
        await drop(new File(['bad'], 'bad.txt', { type: 'text/plain' }));
        expect(submit.element.disabled).toBe(true);
        await drop(new File(['one'], 'first.pdf', { type: 'application/pdf' }));
        await drop(new File(['two'], 'final.png', { type: 'image/png' }));
        await submit.trigger('click');
        const body = post.mock.calls[0]?.[1] as FormData;
        expect(body.getAll('attachment')).toHaveLength(1);
        expect((body.get('attachment') as File).name).toBe('final.png');
        expect(body.has('files[]')).toBe(false);
        post.mockRestore();
        wrapper.unmount();
    });

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
