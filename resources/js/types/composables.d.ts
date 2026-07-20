/**
 * Type declarations for Composables that are still in JS.
 *
 * As we migrate composables to TypeScript one by one, this file
 * provides the temporary type information for the JS versions
 * so TypeScript can type-check consumers (like the .ts composables
 * that import them).
 *
 * Once a composable is migrated to .ts, remove its declaration here.
 */

declare module '@/Composables/useLocalized' {
    export interface LocalizedItem {
        name_ar?: string;
        name_en?: string;
        description_ar?: string;
        description_en?: string;
        [key: string]: unknown;
    }

    export function useLocalized(): {
        getLocalized: (item: LocalizedItem | null | undefined, field?: string) => string;
        getName: (item: LocalizedItem | null | undefined) => string;
        getDescription: (item: LocalizedItem | null | undefined) => string;
    };
}

declare module '@/Composables/useToast' {
    export function useToast(): {
        success: (message: string) => void;
        error: (message: string) => void;
        info: (message: string) => void;
        warning: (message: string) => void;
    };
}

declare module '@/Composables/useConfirm' {
    export interface ConfirmOptions {
        title: string;
        message: string;
        confirmText?: string;
        cancelText?: string;
        type?: 'info' | 'success' | 'warning' | 'danger';
    }

    export function useConfirm(): {
        isOpen: import('vue').Ref<boolean>;
        options: import('vue').Ref<ConfirmOptions | null>;
        open: (opts: ConfirmOptions) => Promise<boolean>;
        close: () => void;
        confirm: (opts: ConfirmOptions) => Promise<boolean>;
        resolve: (value: boolean) => void;
    };
}
