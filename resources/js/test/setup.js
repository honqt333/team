/**
 * Vitest global setup — runs once before any test file.
 *
 * Responsibilities:
 *  1. Polyfill <dialog> element methods that jsdom doesn't implement
 *     (Modal.vue calls dialog.showModal() / dialog.close()).
 *  2. Register a global $t mock so i18n calls in templates don't fail.
 *  3. Restore the Storage API that jsdom does not implement.
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
// 2. localStorage polyfill
// ────────────────────────────────────────────────────────────
//
// jsdom implements the Storage interface but not `clear()` or `key()`.
// Tests that call `localStorage.clear()` in a beforeEach hook fail with
// "localStorage.clear is not a function".
//
// The Storage instance is a Proxy in jsdom, so its methods cannot be
// reassigned directly — the prototype has to be patched instead. The keys
// are tracked manually because the Storage object does not expose them as
// own enumerable properties.
if (typeof window !== 'undefined' && window.localStorage) {
    const store = window.localStorage;

    if (typeof store.clear !== 'function' || typeof store.key !== 'function') {
        /*
         * jsdom's Storage is a Proxy whose `get` trap returns undefined for
         * `clear` and `key` — even though the underlying target DOES define
         * them (the own property descriptor holds a real function).
         *
         * Assigning to the instance does not help either: the proxy
         * intercepts the write. The working approach is to read the real
         * implementations off the property descriptors and expose them as
         * own properties of a plain object that delegates the rest to the
         * storage.
         */
        const descriptor = (name) => Object.getOwnPropertyDescriptor(store, name);

        const realSetItem = descriptor('setItem')?.value ?? store.setItem;
        const realRemoveItem = descriptor('removeItem')?.value ?? store.removeItem;
        const realGetItem = descriptor('getItem')?.value ?? store.getItem;

        // The Storage object does not expose its keys as own enumerable
        // properties, so the key list is tracked by wrapping set/remove.
        const keys = [];

        const patched = {
            get length() {
                return keys.length;
            },
            getItem(key) {
                return realGetItem.call(store, key);
            },
            setItem(key, value) {
                if (!keys.includes(key)) keys.push(key);
                return realSetItem.call(store, key, value);
            },
            removeItem(key) {
                const index = keys.indexOf(key);
                if (index !== -1) keys.splice(index, 1);
                return realRemoveItem.call(store, key);
            },
            key(i) {
                return keys[i] ?? null;
            },
            clear() {
                const snapshot = [...keys];
                keys.length = 0;
                snapshot.forEach((key) => realRemoveItem.call(store, key));
            },
        };

        // Replace the global binding so every test sees the patched object.
        Object.defineProperty(window, 'localStorage', {
            configurable: true,
            writable: true,
            value: patched,
        });

        if (typeof globalThis !== 'undefined' && globalThis !== window) {
            Object.defineProperty(globalThis, 'localStorage', {
                configurable: true,
                writable: true,
                value: patched,
            });
        }
    }
}

// ────────────────────────────────────────────────────────────
// 3. Global $t mock for templates that call $t() directly
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
