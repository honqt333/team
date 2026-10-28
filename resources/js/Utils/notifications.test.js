import { describe, it, expect, vi } from 'vitest';
import { openNotification, markNotificationAsRead } from './notifications';

/**
 * Clicking an unread notification marked it as read with a POST and then
 * navigated straight away, without waiting for the answer. The browser
 * can cancel a request that is still in flight when the page is left,
 * so the mark was lost: the customer reached the target and the
 * notification stayed unread in the badge.
 */
describe('openNotification', () => {
    it('waits for the mark to finish before it navigates', async () => {
        const order = [];

        let resolveFetch;
        const fetchImpl = vi.fn(
            () =>
                new Promise((resolve) => {
                    resolveFetch = () => {
                        order.push('marked');
                        resolve({ ok: true, status: 200 });
                    };
                })
        );

        const navigate = vi.fn(() => order.push('navigated'));

        const notification = { id: 7, read_at: null, action_url: '/app/work-orders/3' };

        const pending = openNotification(notification, { csrfToken: 'token', fetchImpl, navigate });

        // The navigation must NOT have happened yet.
        expect(navigate).not.toHaveBeenCalled();

        resolveFetch();
        await pending;

        expect(order).toEqual(['marked', 'navigated']);
        expect(navigate).toHaveBeenCalledWith('/app/work-orders/3');
    });

    it('marks the notification as read', async () => {
        const fetchImpl = vi.fn().mockResolvedValue({ ok: true, status: 200 });
        const notification = { id: 9, read_at: null, action_url: '/app/work-orders/4' };

        await openNotification(notification, { csrfToken: 'token', fetchImpl, navigate: vi.fn() });

        expect(fetchImpl).toHaveBeenCalledWith(
            '/app/notifications/9/read',
            expect.objectContaining({ method: 'POST' })
        );
        expect(notification.read_at).not.toBeNull();
    });

    it('does not mark a notification that was already read', async () => {
        const fetchImpl = vi.fn();
        const navigate = vi.fn();

        await openNotification(
            { id: 9, read_at: '2026-01-01T00:00:00Z', action_url: '/app/work-orders/4' },
            { fetchImpl, navigate }
        );

        expect(fetchImpl).not.toHaveBeenCalled();
        expect(navigate).toHaveBeenCalledWith('/app/work-orders/4');
    });

    it('still navigates when the mark failed, and reports the failure', async () => {
        const fetchImpl = vi.fn().mockResolvedValue({ ok: false, status: 500 });
        const navigate = vi.fn();
        const error = vi.spyOn(console, 'error').mockImplementation(() => {});

        const notification = { id: 9, read_at: null, action_url: '/app/work-orders/4' };

        await openNotification(notification, { csrfImpl: undefined, fetchImpl, navigate });

        expect(navigate).toHaveBeenCalledWith('/app/work-orders/4');
        expect(error).toHaveBeenCalled();

        error.mockRestore();
    });

    it('does not navigate when the notification has no target', async () => {
        const fetchImpl = vi.fn().mockResolvedValue({ ok: true, status: 200 });
        const navigate = vi.fn();

        await openNotification({ id: 9, read_at: null, action_url: null }, { fetchImpl, navigate });

        expect(navigate).not.toHaveBeenCalled();
    });
});

describe('markNotificationAsRead', () => {
    it('throws when the server refuses', async () => {
        const fetchImpl = vi.fn().mockResolvedValue({ ok: false, status: 422 });

        await expect(markNotificationAsRead({ id: 1 }, { fetchImpl })).rejects.toThrow(/422/);
    });
});
