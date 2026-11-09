import { beforeEach, describe, expect, it } from 'vitest';

import { ALLOWED_PARAMS, TRACKED_EVENTS, installEventTracking, track } from './analytics';

/**
 * The tracking module sends what a marketing analyst needs — which button
 * was pressed — and nothing else.
 *
 * The important assertion is the second one: the contact form collects a
 * name, an email and a phone number, and none of them may ever leave the
 * page. The parameter whitelist is what guarantees it, so it is tested
 * rather than trusted.
 */
describe('analytics', () => {
    beforeEach(() => {
        window.dataLayer = [];
        window.gtag = undefined;
    });

    it('records a known event with its allowed parameters', () => {
        const recorded = track('trial_start', { placement: 'hero' });

        expect(recorded).toBe(true);
        expect(window.dataLayer).toEqual([{ event: 'trial_start', placement: 'hero' }]);
    });

    it('drops an event that is not in the vocabulary', () => {
        const recorded = track('definitely_not_an_event', { placement: 'hero' });

        expect(recorded).toBe(false);
        expect(window.dataLayer).toHaveLength(0);
    });

    it('never sends personal data, even when it is passed in', () => {
        track('contact_submit', {
            email: 'customer@example.com',
            name: 'أحمد',
            phone: '+966500000000',
            message: 'أريد حجز موعد',
            placement: 'contact-form',
        });

        const payload = window.dataLayer[0];

        expect(payload).toEqual({ event: 'contact_submit', placement: 'contact-form' });

        const serialised = JSON.stringify(payload);

        expect(serialised).not.toContain('example.com');
        expect(serialised).not.toContain('+966500000000');
        expect(serialised).not.toContain('أحمد');
    });

    it('only allows the parameters on the list', () => {
        for (const key of Object.keys({ placement: 'x', plan: 'y', section: 'z', method: 'w' })) {
            expect(ALLOWED_PARAMS).toContain(key);
        }

        track('cta_click', { placement: 'hero', evil: 'value' });

        expect(window.dataLayer[0]).not.toHaveProperty('evil');
    });

    it('forwards to gtag when the analytics snippet is installed', () => {
        const calls = [];
        window.gtag = (...args) => calls.push(args);

        track('phone_click', { placement: 'hero' });

        expect(calls).toEqual([['event', 'phone_click', { placement: 'hero' }]]);
    });

    it('reports any element carrying data-track when it is clicked', () => {
        const root = document.createElement('div');
        root.innerHTML = `
            <a href="#"><span id="label">Start</span></a>
        `;

        const link = root.querySelector('a');
        link.setAttribute('data-track', 'trial_start');
        link.setAttribute('data-track-placement', 'hero');

        installEventTracking(root);

        root.querySelector('#label').click();

        expect(window.dataLayer).toEqual([{ event: 'trial_start', placement: 'hero' }]);
    });

    it('installs the delegated listener only once', () => {
        const root = document.createElement('div');

        expect(installEventTracking(root)).toBe(true);
        expect(installEventTracking(root)).toBe(false);
    });

    it('exposes a vocabulary that covers the public call to actions', () => {
        for (const event of ['trial_start', 'contact_submit', 'phone_click', 'whatsapp_click']) {
            expect(TRACKED_EVENTS).toContain(event);
        }
    });
});
