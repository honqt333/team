import { describe, it, expect } from 'vitest';
import { useConfirm } from './useConfirm';

describe('useConfirm', () => {
    const { isOpen, options, confirm, resolve } = useConfirm();

    it('opens confirm dialog', () => {
        expect(isOpen.value).toBe(false);
        const promise = confirm({ title: 'Delete Item?', message: 'Are you sure?' });
        expect(isOpen.value).toBe(true);
        expect(options.value.title).toBe('Delete Item?');

        resolve(true);
        return expect(promise).resolves.toBe(true);
    });

    it('resolves false on cancel', () => {
        const promise = confirm({ title: 'Cancel Test' });
        resolve(false);
        return expect(promise).resolves.toBe(false);
    });
});
