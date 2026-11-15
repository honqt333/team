import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { afterEach, describe, expect, it, vi } from 'vitest';
import SubscriptionCard from './SubscriptionCard.vue';
import PlanCard from './PlanCard.vue';
import SubscribeModal from './SubscribeModal.vue';
import SubscriptionHistoryTable from './SubscriptionHistoryTable.vue';
import PaymentMethodSelector from './PaymentMethodSelector.vue';
import CompanySubscriptionTab from '../Settings/Company/CompanySubscriptionTab.vue';
import BillingIndex from '@/Pages/Settings/Billing/Index.vue';
import DunningPreferencesCard from './DunningPreferencesCard.vue';
import ar from '@/i18n/lang/ar.json';
import en from '@/i18n/lang/en.json';

let mockAuthPermissions;

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal()),
    usePage: () => ({
        props: {
            csrf_token: 'test-csrf',
            auth: mockAuthPermissions ? { permissions: mockAuthPermissions } : undefined,
            value: { csrf_token: 'test-csrf' },
        },
    }),
    router: { post: vi.fn(), reload: vi.fn() },
    Head: { template: '<div />' },
}));

const makeI18n = (locale = 'ar') =>
    createI18n({ legacy: false, locale, messages: { ar, en } });

const samplePlan = {
    id: 2,
    name_ar: 'الباقة الأساسية',
    name_en: 'Basic Plan',
    price_yearly: 1000,
    currency: 'SAR',
};

describe('Billing state UI & translations', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('translates pending_payment accurately in both ar and en', () => {
        expect(ar.system.company_subscription.status.pending_payment).toBe('بانتظار الدفع');
        expect(en.system.company_subscription.status.pending_payment).toBe('Pending payment');
    });

    it('SubscriptionCard renders server billingState badges and partial amounts', () => {
        const sub = { id: 10, status: 'pending_payment', plan: samplePlan, price: 1000 };

        const rejected = mount(SubscriptionCard, {
            props: {
                subscription: sub,
                billingState: { display_status: 'receipt_rejected' },
            },
            global: { plugins: [makeI18n('ar')] },
        });
        expect(rejected.text()).toContain('الإيصال مرفوض');
        expect(rejected.text()).not.toContain('قيد مراجعة الإيصال');

        const underReview = mount(SubscriptionCard, {
            props: {
                subscription: sub,
                billingState: { display_status: 'under_review' },
            },
            global: { plugins: [makeI18n('ar')] },
        });
        expect(underReview.text()).toContain('قيد مراجعة الإيصال');

        const partial = mount(SubscriptionCard, {
            props: {
                subscription: sub,
                billingState: {
                    display_status: 'partially_paid',
                    open_invoice: { paid_amount: 600, remaining_amount: 550 },
                },
            },
            global: { plugins: [makeI18n('en')] },
        });
        expect(partial.text()).toContain('Partially paid');
        expect(partial.text()).toContain('600');
        expect(partial.text()).toContain('550');
    });

    it('PlanCard unlocks open partial plan for completing payment while locking others', () => {
        const partialPlan = mount(PlanCard, {
            props: {
                plan: samplePlan,
                hasPendingSubscription: true,
                isPartialOpenPlan: true,
            },
            global: { plugins: [makeI18n('en')] },
        });
        const btn = partialPlan.find('button');
        expect(btn.attributes('disabled')).toBeUndefined();
        expect(btn.text()).toContain('Complete payment');

        const otherPlan = mount(PlanCard, {
            props: {
                plan: { ...samplePlan, id: 3 },
                hasPendingSubscription: true,
                isPartialOpenPlan: false,
                lockReason: en.system.billing.page.state.partial_paid_locked_hint,
            },
            global: { plugins: [makeI18n('en')] },
        });
        expect(otherPlan.find('button').attributes('disabled')).toBeDefined();
    });

    it('CompanySubscriptionTab ignores stale receipts array and follows billingState', () => {
        mockAuthPermissions = ['settings.company.manage'];
        try {
            const wrapper = mount(CompanySubscriptionTab, {
                props: {
                    activeTab: 'subscription',
                    currentSubscription: {
                        id: 1,
                        status: 'pending_payment',
                        plan_id: 2,
                        plan: samplePlan,
                        receipts: [{ id: 99, status: 'rejected' }],
                    },
                    billingState: {
                        display_status: 'receipt_rejected',
                        has_pending_receipt: false,
                        latest_rejected_receipt: { review_notes: 'Blurry receipt' },
                        open_invoice: { id: 5, plan_id: 2, paid_amount: 0, remaining_amount: 1150 },
                        allowed_actions: { can_upload_receipt: true },
                    },
                    availablePlans: [samplePlan],
                    subscriptions: [],
                    locale: 'en',
                },
                global: {
                    plugins: [makeI18n('en')],
                    mocks: {
                        $t: (key) => key.split('.').reduce((o, k) => o?.[k], en) ?? key,
                    },
                    stubs: { SubscribeModal: true },
                },
            });

            expect(wrapper.text()).toContain('Receipt rejected');
            expect(wrapper.text()).toContain('Blurry receipt');
            expect(wrapper.text()).toContain('Re-upload receipt');
            expect(wrapper.text()).not.toContain('Under Review');
        } finally {
            mockAuthPermissions = undefined;
        }
    });

    it('SubscribeModal locks gateway to bank_transfer when invoice is partially_paid', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => ({
                ok: true,
                json: async () => ({
                    plan: samplePlan,
                    base_price: 1000,
                    discount: 0,
                    subtotal: 1000,
                    vat_enabled: true,
                    vat_rate: 15,
                    vat_amount: 150,
                    total: 1150,
                    currency: 'SAR',
                }),
            }))
        );

        const wrapper = mount(SubscribeModal, {
            props: {
                open: false,
                plan: samplePlan,
                enabledGateways: ['moyasar', 'bank_transfer'],
                paymentSettings: {
                    default_gateway: 'moyasar',
                    moyasar: { enabled: true },
                    bank_transfer_enabled: true,
                    bank_transfer_min_amount: 100,
                },
                billingState: {
                    display_status: 'partially_paid',
                },
            },
            global: {
                plugins: [makeI18n('en')],
                stubs: {
                    BaseModal: { template: '<div><slot /><slot name="footer" /></div>' },
                    Teleport: true,
                },
            },
        });

        await wrapper.setProps({ open: true });
        await flushPromises();

        expect(wrapper.text()).toContain('approved partial payment');
        const selector = wrapper.findComponent(PaymentMethodSelector);
        expect(selector.exists()).toBe(true);
        expect(selector.props('modelValue')).toBe('bank_transfer');
        expect(selector.props('lockedGateway')).toBe('bank_transfer');
    });

    it('SubscriptionHistoryTable displays billingState status for the matching pending subscription', () => {
        const wrapper = mount(SubscriptionHistoryTable, {
            props: {
                subscriptions: [
                    {
                        id: 42,
                        status: 'pending_payment',
                        price: 1150,
                        starts_at: '2026-01-01',
                        ends_at: '2027-01-01',
                        plan: samplePlan,
                    },
                    {
                        id: 41,
                        status: 'expired',
                        price: 1000,
                        starts_at: '2025-01-01',
                        ends_at: '2026-01-01',
                        plan: samplePlan,
                    },
                ],
                billingState: {
                    display_status: 'partially_paid',
                    pending_subscription_id: 42,
                },
            },
            global: {
                plugins: [makeI18n('en')],
            },
        });

        expect(wrapper.text()).toContain(en.system.billing.page.state.partially_paid_title);
        expect(wrapper.text()).toContain(en.system.company_subscription.status.expired);
    });

    it('Billing/Index gates billing action buttons when settings.company.manage permission is missing', () => {
        mockAuthPermissions = ['work_orders.view'];
        try {
            const wrapper = mount(BillingIndex, {
                props: {
                    current_subscription: {
                        id: 42,
                        status: 'pending_payment',
                        plan_id: 2,
                        plan: samplePlan,
                    },
                    billing_state: {
                        display_status: 'awaiting_payment',
                        pending_subscription_id: 42,
                        open_invoice: {
                            id: 7,
                            invoice_number: 'SUB-2026-0007',
                            plan_id: 2,
                            total_amount: 1150,
                            paid_amount: 0,
                            remaining_amount: 1150,
                            currency: 'SAR',
                        },
                        allowed_actions: {
                            can_upload_receipt: true,
                            can_cancel_pending: true,
                            can_change_plan_or_gateway: true,
                        },
                    },
                    available_plans: [samplePlan],
                    subscription_history: [],
                    enabled_gateways: ['bank_transfer'],
                    payment_settings: {
                        default_gateway: 'bank_transfer',
                        bank_transfer_enabled: true,
                    },
                },
                global: {
                    plugins: [makeI18n('en')],
                    mocks: {
                        $t: (key) => key.split('.').reduce((o, k) => o?.[k], en) ?? key,
                    },
                    stubs: {
                        AppLayout: { template: '<div><slot /></div>' },
                        SubscribeModal: true,
                        BankTransferPaymentModal: true,
                    },
                },
            });

            expect(wrapper.text()).not.toContain(en.system.billing.page.state.upload_receipt);
            expect(wrapper.text()).not.toContain(en.system.billing.page.state.cancel_pending);
        } finally {
            mockAuthPermissions = undefined;
        }
    });

    it('Billing/Index fails closed when auth.permissions is missing', () => {
        mockAuthPermissions = undefined;
        const wrapper = mount(BillingIndex, {
            props: {
                current_subscription: {
                    id: 42,
                    status: 'pending_payment',
                    plan_id: 2,
                    plan: samplePlan,
                },
                billing_state: {
                    display_status: 'awaiting_payment',
                    pending_subscription_id: 42,
                    open_invoice: {
                        id: 7,
                        invoice_number: 'SUB-2026-0007',
                        plan_id: 2,
                        total_amount: 1150,
                        paid_amount: 0,
                        remaining_amount: 1150,
                        currency: 'SAR',
                    },
                    allowed_actions: {
                        can_upload_receipt: true,
                        can_cancel_pending: true,
                        can_change_plan_or_gateway: true,
                    },
                },
                available_plans: [samplePlan],
                subscription_history: [],
                enabled_gateways: ['bank_transfer'],
                payment_settings: {
                    default_gateway: 'bank_transfer',
                    bank_transfer_enabled: true,
                },
            },
            global: {
                plugins: [makeI18n('en')],
                mocks: {
                    $t: (key) => key.split('.').reduce((o, k) => o?.[k], en) ?? key,
                },
                stubs: {
                    AppLayout: { template: '<div><slot /></div>' },
                    SubscribeModal: true,
                    BankTransferPaymentModal: true,
                },
            },
        });

        expect(wrapper.text()).not.toContain(en.system.billing.page.state.reupload_receipt);
        expect(wrapper.text()).not.toContain(en.system.billing.page.state.cancel_request);
    });

    it.each([
        ['missing billingState', null, false],
        ['missing allowed_actions', { display_status: 'awaiting_payment' }, false],
        ['can_cancel_pending=false', { allowed_actions: { can_cancel_pending: false } }, false],
        ['can_cancel_pending=true', { allowed_actions: { can_cancel_pending: true } }, true],
    ])(
        'SubscribeModal cancelPendingAttempt fail-closed check (%s)',
        async (_label, billingState, shouldCallCancel) => {
            const fetchMock = vi.fn(async (url) => {
                if (String(url).includes('/calculate')) {
                    return {
                        ok: true,
                        json: async () => ({
                            plan: samplePlan,
                            base_price: 1000,
                            discount: 0,
                            subtotal: 1000,
                            vat_enabled: false,
                            vat_rate: 0,
                            vat_amount: 0,
                            total: 1000,
                            currency: 'SAR',
                        }),
                    };
                }
                return { ok: true, json: async () => ({ success: true }) };
            });
            vi.stubGlobal('fetch', fetchMock);

            const wrapper = mount(SubscribeModal, {
                props: {
                    open: false,
                    plan: samplePlan,
                    enabledGateways: ['moyasar'],
                    paymentSettings: {
                        default_gateway: 'moyasar',
                        moyasar: { enabled: true },
                    },
                    billingState,
                },
                global: {
                    plugins: [makeI18n('en')],
                    stubs: {
                        BaseModal: { template: '<div><slot /><slot name="footer" /></div>' },
                        Teleport: true,
                    },
                },
            });

            await wrapper.setProps({ open: true });
            await flushPromises();

            // Simulate closing while an online payment was started
            await wrapper.vm.cancelPendingAttempt?.();
            await flushPromises();

            const cancelCalls = fetchMock.mock.calls.filter(([url]) =>
                String(url).includes('/cancel-pending')
            );
            expect(cancelCalls.length).toBe(shouldCallCancel ? 1 : 0);
        }
    );

    it('CompanySubscriptionTab hides subscription action buttons when unauthorized', () => {
        mockAuthPermissions = ['work_orders.view'];
        try {
            const wrapper = mount(CompanySubscriptionTab, {
                props: {
                    activeTab: 'subscription',
                    currentSubscription: {
                        id: 1,
                        status: 'pending',
                        plan_id: 2,
                        plan: samplePlan,
                    },
                    billingState: {
                        display_status: 'awaiting_payment',
                        open_invoice: { id: 5, plan_id: 2, paid_amount: 0, remaining_amount: 1150 },
                        allowed_actions: {
                            can_upload_receipt: true,
                            can_change_plan_or_gateway: true,
                        },
                    },
                    availablePlans: [samplePlan],
                    subscriptions: [],
                    locale: 'en',
                },
                global: {
                    plugins: [makeI18n('en')],
                    mocks: {
                        $t: (key) => key.split('.').reduce((o, k) => o?.[k], en) ?? key,
                    },
                    stubs: { SubscribeModal: true },
                },
            });

            expect(wrapper.text()).not.toContain('Subscribe now');
            expect(wrapper.text()).not.toContain(en.subscription.complete_payment_now);
        } finally {
            mockAuthPermissions = undefined;
        }
    });

    it('PlanCard and SubscriptionCard reactively switch ar <-> en without relying on document.documentElement.lang', async () => {
        document.documentElement.lang = 'ar';
        const i18n = makeI18n('ar');
        const richPlan = {
            ...samplePlan,
            features_ar: ['ميزة عربية ١'],
            features_en: ['English feature 1'],
        };

        const planWrapper = mount(PlanCard, {
            props: { plan: richPlan },
            global: { plugins: [i18n] },
        });
        const subWrapper = mount(SubscriptionCard, {
            props: {
                subscription: {
                    id: 11,
                    status: 'pending_payment',
                    ends_at: '2027-11-15T00:00:00Z',
                    plan: richPlan,
                    price: 1000,
                },
                billingState: { display_status: 'under_review' },
            },
            global: { plugins: [i18n] },
        });

        expect(planWrapper.text()).toContain('الباقة الأساسية');
        expect(planWrapper.text()).toContain('ميزة عربية ١');
        expect(subWrapper.text()).toContain('قيد مراجعة الإيصال');

        // Switch locale reactively via vue-i18n while document.documentElement.lang stays 'ar'
        i18n.global.locale.value = 'en';
        await flushPromises();

        expect(planWrapper.text()).toContain('Basic Plan');
        expect(planWrapper.text()).toContain('English feature 1');
        expect(planWrapper.text()).not.toContain('الباقة الأساسية');
        expect(subWrapper.text()).toContain('Basic Plan');
        expect(subWrapper.text()).toContain('Receipt under review');
        expect(subWrapper.text()).toContain('Nov');
        expect(subWrapper.text()).not.toContain('قيد مراجعة الإيصال');

        // Switch back to Arabic
        i18n.global.locale.value = 'ar';
        await flushPromises();
        expect(planWrapper.text()).toContain('الباقة الأساسية');
        expect(subWrapper.text()).toContain('قيد مراجعة الإيصال');
    });

    it('Billing/Index wraps tables in overflow-x-auto, translates dates/banner/dunning placeholder on locale switch, and preserves allowed_actions', async () => {
        document.documentElement.lang = 'ar';
        mockAuthPermissions = ['settings.billing.manage'];
        const i18n = makeI18n('ar');
        try {
            const wrapper = mount(BillingIndex, {
                props: {
                    current_subscription: {
                        id: 42,
                        status: 'pending_payment',
                        plan_id: 2,
                        ends_at: '2027-11-15T00:00:00Z',
                        plan: samplePlan,
                    },
                    billing_state: {
                        display_status: 'under_review',
                        pending_subscription_id: 42,
                        open_invoice: {
                            id: 7,
                            invoice_number: 'SUB-2026-0007',
                            plan_id: 2,
                            total_amount: 1150,
                            paid_amount: 0,
                            remaining_amount: 1150,
                            currency: 'SAR',
                        },
                        allowed_actions: {
                            can_upload_receipt: false,
                            can_cancel_pending: false,
                            can_change_plan_or_gateway: false,
                        },
                    },
                    available_plans: [samplePlan],
                    subscription_history: [],
                    all_receipts: [
                        {
                            id: 99,
                            created_at: '2026-11-15T00:00:00Z',
                            transfer_amount: 1150,
                            transfer_reference: 'REF-123',
                            status: 'pending',
                            subscription: { plan: samplePlan },
                        },
                    ],
                    recent_payments: [
                        {
                            id: 88,
                            created_at: '2026-11-15T00:00:00Z',
                            gateway: 'bank_transfer',
                            amount: 1150,
                            status: 'pending',
                        },
                    ],
                    enabled_gateways: ['bank_transfer'],
                    payment_settings: {
                        default_gateway: 'bank_transfer',
                        bank_transfer_enabled: true,
                    },
                    tenant: { dunning_opted_out: false, dunning_language: '' },
                },
                global: {
                    plugins: [i18n],
                    stubs: {
                        AppLayout: { template: '<div><slot /></div>' },
                        SubscribeModal: true,
                        BankTransferPaymentModal: true,
                    },
                },
            });

            // Mobile overflow protection: both tables must sit inside overflow-x-auto containers
            const overflowWrappers = wrapper.findAll('.overflow-x-auto');
            expect(overflowWrappers.length).toBeGreaterThanOrEqual(2);

            // Switch to English
            i18n.global.locale.value = 'en';
            await flushPromises();

            expect(wrapper.text()).toContain('Receipt under review');
            expect(wrapper.text()).toContain('Basic Plan');
            expect(wrapper.text()).toContain('Nov');
            expect(wrapper.text()).not.toContain('قيد مراجعة الإيصال');
            expect(wrapper.text()).not.toContain('الباقة الأساسية');
            expect(wrapper.text()).not.toContain('نوفمبر');
            // Permissions / allowed_actions remain enforced after locale switch
            expect(wrapper.text()).not.toContain(en.system.billing.page.state.upload_receipt);
            expect(wrapper.text()).not.toContain(en.system.billing.page.state.cancel_request);
            // Zero raw translation keys
            expect(wrapper.text()).not.toContain('system.billing.page.');

            // DunningPreferencesCard placeholder uses i18n key instead of hardcoded Arabic
            const dunningEn = mount(DunningPreferencesCard, {
                props: { tenant: { dunning_opted_out: false, dunning_language: '' } },
                global: {
                    plugins: [makeI18n('en')],
                    mocks: {
                        $t: (key) => key.split('.').reduce((o, k) => o?.[k], en) ?? key,
                    },
                },
            });
            expect(dunningEn.html()).not.toContain('اختر اللغة...');
            expect(dunningEn.html()).toContain('Select language...');
        } finally {
            mockAuthPermissions = undefined;
        }
    });
});
