/**
 * Event tracking for the public pages.
 *
 * WHY A TINY MODULE INSTEAD OF A LIBRARY
 * -------------------------------------
 * The landing page needs to answer one question: "which button do people
 * actually press?". That needs an event name, a placement, and nothing
 * else. A tracking SDK would add a request to a third party on every
 * public page — the opposite of what this project just spent a day fixing.
 *
 * WHAT IS SENT
 * ------------
 * Only the events listed in `TRACKED_EVENTS`, and only the parameters
 * listed in `ALLOWED_PARAMS`. The parameter whitelist is the important
 * part: the contact form has a name, an email and a phone number, and
 * none of them may ever reach an analytics endpoint.
 *
 * WHERE IT GOES
 * -------------
 * `window.dataLayer`, which is what GA4 reads. If no analytics script is
 * installed, the events land in a plain array and go nowhere — the public
 * pages stay free of third-party requests.
 */

/**
 * The vocabulary. An event that is not in this list is dropped, so a typo
 * in a `data-track` attribute fails silently in the UI but never creates a
 * stray event in the analytics.
 */
export const TRACKED_EVENTS = Object.freeze([
    'trial_start',
    'contact_submit',
    'phone_click',
    'whatsapp_click',
    'pricing_toggle',
    'faq_open',
    'booking_start',
    'cta_click',
]);

/**
 * The parameters that may leave the page.
 *
 * Everything else is dropped, which is what keeps the contact form's
 * name, email, phone number and message out of the analytics.
 */
export const ALLOWED_PARAMS = Object.freeze(['placement', 'plan', 'section', 'method']);

const ALLOWED_PARAM_SET = new Set(ALLOWED_PARAMS);
const TRACKED_EVENT_SET = new Set(TRACKED_EVENTS);

/**
 * Keep only the allowed parameters, and only scalar values.
 *
 * @param {Record<string, unknown>} params
 * @returns {Record<string, string|number|boolean>}
 */
function sanitize(params) {
    const clean = {};

    for (const key of Object.keys(params || {})) {
        if (!ALLOWED_PARAM_SET.has(key)) {
            continue;
        }

        const value = params[key];

        if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
            clean[key] = value;
        }
    }

    return clean;
}

/**
 * Record an event.
 *
 * @param {string} event One of TRACKED_EVENTS.
 * @param {Record<string, unknown>} [params]
 */
export function track(event, params = {}) {
    if (!TRACKED_EVENT_SET.has(event)) {
        return false;
    }

    if (typeof window === 'undefined') {
        return false;
    }

    const payload = sanitize(params);

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event, ...payload });

    // gtag only exists when the GA4 snippet is installed on the site.
    if (typeof window.gtag === 'function') {
        window.gtag('event', event, payload);
    }

    return true;
}

/**
 * Listen for clicks on anything carrying a `data-track` attribute.
 *
 * Declarative on purpose: adding tracking to a button is one attribute on
 * the element, and the behaviour works for links and buttons alike.
 *
 * @param {Document|HTMLElement} [root]
 */
export function installEventTracking(root = typeof document !== 'undefined' ? document : null) {
    if (!root || root.__mavisTrackingInstalled) {
        return false;
    }

    root.__mavisTrackingInstalled = true;

    root.addEventListener('click', (event) => {
        const target = event.target;

        if (!target || typeof target.closest !== 'function') {
            return;
        }

        const element = target.closest('[data-track]');

        if (!element) {
            return;
        }

        const name = element.getAttribute('data-track');
        const placement = element.getAttribute('data-track-placement');

        track(name, placement ? { placement } : {});
    });

    return true;
}
