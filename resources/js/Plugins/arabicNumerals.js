/**
 * Global plugin to automatically convert Arabic numerals to English numerals
 * in all input fields across the application.
 */
export const ArabicNumeralsPlugin = {
    install(app) {
        if (typeof window === 'undefined') return;

        const arabicMap = {
            '٠': '0',
            '١': '1',
            '٢': '2',
            '٣': '3',
            '٤': '4',
            '٥': '5',
            '٦': '6',
            '٧': '7',
            '٨': '8',
            '٩': '9',
            '۰': '0',
            '۱': '1',
            '۲': '2',
            '۳': '3',
            '۴': '4',
            '۵': '5',
            '۶': '6',
            '۷': '7',
            '۸': '8',
            '۹': '9',
            '٫': '.',
        };

        const toEnglish = (value) => {
            if (typeof value !== 'string') return value;
            if (!/[\u0660-\u0669\u06F0-\u06F9٫]/.test(value)) return null;
            return value.replace(
                /[\u0660-\u0669\u06F0-\u06F9٫]/g,
                (char) => arabicMap[char] || char
            );
        };

        const sanitizeElement = (el) => {
            if (!el || el.readOnly || el.disabled) return;
            if (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') return;
            const val = el.value;
            if (val && /[\u0660-\u0669\u06F0-\u06F9٫]/.test(val)) {
                const converted = toEnglish(val);
                if (converted !== null && converted !== val) {
                    el.value = converted;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        };

        // 1. Intercept beforeinput to prevent native type="number" from clearing/dropping Arabic numerals
        window.addEventListener(
            'beforeinput',
            (e) => {
                const target = e.target;
                if (!target || (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA'))
                    return;
                if (target.readOnly || target.disabled) return;

                const data = e.data;
                if (data && /[\u0660-\u0669\u06F0-\u06F9٫]/.test(data)) {
                    const converted = data.replace(
                        /[\u0660-\u0669\u06F0-\u06F9٫]/g,
                        (c) => arabicMap[c] || c
                    );
                    if (
                        typeof document !== 'undefined' &&
                        typeof document.execCommand === 'function'
                    ) {
                        e.preventDefault();
                        document.execCommand('insertText', false, converted);
                    }
                }
            },
            true
        );

        // 2. Intercept paste to convert Arabic numerals in clipboard
        window.addEventListener(
            'paste',
            (e) => {
                const target = e.target;
                if (!target || (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA'))
                    return;
                if (target.readOnly || target.disabled) return;

                const text = e.clipboardData?.getData('text');
                if (text && /[\u0660-\u0669\u06F0-\u06F9٫]/.test(text)) {
                    const converted = text.replace(
                        /[\u0660-\u0669\u06F0-\u06F9٫]/g,
                        (c) => arabicMap[c] || c
                    );
                    if (
                        typeof document !== 'undefined' &&
                        typeof document.execCommand === 'function'
                    ) {
                        e.preventDefault();
                        document.execCommand('insertText', false, converted);
                    }
                }
            },
            true
        );

        // 3. Intercept focusin so any field displaying Arabic digits converts instantly upon user focus
        window.addEventListener(
            'focusin',
            (e) => {
                const target = e.target;
                if (target) {
                    sanitizeElement(target);
                }
            },
            true
        );

        // 4. Listed for 'input' events on the document (capturing phase) as fallback
        window.addEventListener(
            'input',
            (e) => {
                const target = e.target;
                if (target) {
                    sanitizeElement(target);
                }
            },
            true
        );

        // 5. DOM MutationObserver: whenever a modal or new component mounts, sanitize all its inputs immediately
        if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
            const observer = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    for (const node of mutation.addedNodes) {
                        if (node.nodeType === 1) {
                            // Node.ELEMENT_NODE
                            if (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA') {
                                sanitizeElement(node);
                            } else if (typeof node.querySelectorAll === 'function') {
                                const inputs = node.querySelectorAll('input, textarea');
                                for (let i = 0; i < inputs.length; i++) {
                                    sanitizeElement(inputs[i]);
                                }
                            }
                        }
                    }
                }
            });

            const targetRoot = document.body || document.documentElement;
            if (targetRoot) {
                observer.observe(targetRoot, {
                    childList: true,
                    subtree: true,
                });
            }
        }
    },
};
