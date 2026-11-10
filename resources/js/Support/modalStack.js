import { shallowRef } from 'vue';

export const activeModalElement = shallowRef(null);

// Native dialogs share one scroll lock, including nested confirmation dialogs.
const stack = [];
let previousOverflow = '';

export function registerModal(element, opener) {
    if (stack.some((entry) => entry.element === element)) return;
    if (stack.length === 0) {
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    }
    stack.push({ element, opener });
    activeModalElement.value = element;
}

export function isTopModal(element) {
    return stack.at(-1)?.element === element;
}

export function unregisterModal(element) {
    const index = stack.findIndex((entry) => entry.element === element);
    if (index === -1) return;
    const wasTop = index === stack.length - 1;
    const [{ opener }] = stack.splice(index, 1);
    activeModalElement.value = stack.at(-1)?.element || null;
    if (stack.length === 0) document.body.style.overflow = previousOverflow;
    if (!wasTop) {
        // If an ancestor is removed first, descendants must restore focus to
        // its opener, rather than to a control that is about to disappear.
        for (const entry of stack.slice(index)) {
            if (element.contains(entry.opener)) entry.opener = opener;
        }
        return;
    }
    const top = stack.at(-1)?.element;
    if (opener?.isConnected && (!top || top.contains(opener))) opener.focus();
    else top?.focus();
}
