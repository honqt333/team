/**
 * Opening a notification.
 *
 * Clicking a notification that was not read yet marked it as read with
 * a POST and then navigated straight away, without waiting for the
 * answer. The browser can cancel a request that is still in flight when
 * the page is left, so the mark could be lost: the customer reaches the
 * target, the notification stays unread, and it keeps counting in the
 * badge.
 *
 * The mark is now awaited before the navigation happens, and a failure
 * is reported instead of being swallowed.
 */

/**
 * Mark a notification as read.
 *
 * @param {object} notification
 * @param {{csrfToken?: string, fetchImpl?: Function}} options
 */
export const markNotificationAsRead = async (notification, options = {}) => {
    const { csrfToken, fetchImpl = fetch } = options;

    const response = await fetchImpl(`/app/notifications/${notification.id}/read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`The notification could not be marked as read (HTTP ${response.status}).`);
    }

    notification.read_at = new Date().toISOString();

    return notification;
};

/**
 * Open a notification: mark it as read, then go to its target.
 *
 * @param {object} notification
 * @param {{csrfToken?: string, fetchImpl?: Function, navigate?: Function}} options
 */
export const openNotification = async (notification, options = {}) => {
    const {
        navigate = (url) => {
            window.location.href = url;
        },
    } = options;

    if (!notification.read_at) {
        try {
            await markNotificationAsRead(notification, options);
        } catch (error) {
            // The mark failed. The navigation still happens — the
            // customer asked to go there — but the failure is not
            // hidden behind a silent page change.
            console.error('Could not mark the notification as read:', error);
        }
    }

    if (notification.action_url) {
        navigate(notification.action_url);
    }
};
