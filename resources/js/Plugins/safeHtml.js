import DOMPurify from 'dompurify';

/**
 * Vue plugin that registers a global `v-safe-html` directive.
 *
 * v-safe-html sanitizes its value through DOMPurify before injecting it via
 * innerHTML. It is the safe counterpart to Vue's built-in v-html and should
 * be used whenever the source string may contain user-controlled content
 * (e.g. Laravel pagination `link.label`).
 *
 * Usage in templates:
 *   <span v-safe-html="link.label" />
 *
 * Sanitization is also applied automatically to v-html by overriding its
 * default behaviour, so existing `v-html` usages become safe without
 * touching every call-site. If you intentionally need raw HTML, use the
 * built-in `v-html` and opt out by binding to a `__unsafe_html` prefixed
 * helper or a value you have already sanitized yourself.
 */
export const SafeHtmlPlugin = {
    install(app) {
        // v-safe-html: always sanitized
        app.directive('safe-html', {
            mounted(el, binding) {
                el.innerHTML = DOMPurify.sanitize(String(binding.value ?? ''));
            },
            updated(el, binding) {
                el.innerHTML = DOMPurify.sanitize(String(binding.value ?? ''));
            },
        });
    },
};

export default SafeHtmlPlugin;
