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
 * NOTE: Vue 3's built-in `v-html` compiles directly to `innerHTML` and is NOT
 * overridden by this plugin. Always use `v-safe-html` for any untrusted or
 * user-editable HTML content.
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
