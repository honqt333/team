/**
 * Vitest global setup — runs once before any test file.
 *
 * Responsibilities:
 *  1. Polyfill <dialog> element methods that jsdom doesn't implement
 *     (Modal.vue calls dialog.showModal() / dialog.close()).
 *  2. Register a global $t mock so i18n calls in templates don't fail.
 */

import { config } from '@vue/test-utils';

// ────────────────────────────────────────────────────────────
// 1. <dialog> element polyfill
// ────────────────────────────────────────────────────────────
if (typeof window !== 'undefined' && window.HTMLDialogElement) {
    // @ts-ignore - dynamic property assignment
    const proto = window.HTMLDialogElement.prototype;
    // @ts-ignore - dynamic property check
    if (!proto.showModal) {
        // @ts-ignore - jsdom polyfill
        proto.showModal = function () {
            this.open = true;
        };
    }
    // @ts-ignore - dynamic property check
    if (!proto.close) {
        // @ts-ignore - jsdom polyfill
        proto.close = function () {
            this.open = false;
        };
    }
}

// ────────────────────────────────────────────────────────────
// 2. Global $t mock for templates that call $t() directly
// ────────────────────────────────────────────────────────────
config.global.mocks = {
    ...config.global.mocks,
    $t: (key, fallback) => {
        if (fallback !== undefined && fallback !== null) {
            return typeof fallback === 'string' ? fallback : fallback;
        }
        return key.split('.').pop() ?? key;
    },
};
