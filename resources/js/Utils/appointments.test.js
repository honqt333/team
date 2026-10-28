import { describe, it, expect } from 'vitest';
import { canMarkNoShow, NO_SHOW_ALLOWED_STATUSES } from './appointments';

/**
 * The server only allows "no show" from a scheduled or a confirmed
 * appointment. The button used to be shown for every state except
 * cancelled, converted and no-show — so it appeared on an appointment
 * that had been checked in, and pressing it produced a 422 that the
 * page never showed.
 */
describe('canMarkNoShow', () => {
    it('allows the states the server accepts', () => {
        expect(canMarkNoShow({ status: 'scheduled' })).toBe(true);
        expect(canMarkNoShow({ status: 'confirmed' })).toBe(true);
    });

    it('refuses an appointment that was already checked in', () => {
        expect(canMarkNoShow({ status: 'checked_in' })).toBe(false);
    });

    it('refuses the states that are already past the point of no return', () => {
        expect(canMarkNoShow({ status: 'cancelled' })).toBe(false);
        expect(canMarkNoShow({ status: 'converted' })).toBe(false);
        expect(canMarkNoShow({ status: 'no_show' })).toBe(false);
    });

    it('refuses an appointment with no state at all', () => {
        expect(canMarkNoShow({})).toBe(false);
        expect(canMarkNoShow(null)).toBe(false);
    });

    it('lists exactly the states the server accepts', () => {
        expect(NO_SHOW_ALLOWED_STATUSES).toEqual(['scheduled', 'confirmed']);
    });
});
