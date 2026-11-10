import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
        // The landing page stylesheet was moved out of the SFC into
        // resources/css/landing.css. Without this entry Tailwind would not
        // scan the classes used there and would purge them from the build.
        './resources/css/landing.css',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: [
                    '"IBM Plex Sans Arabic"',
                    'system-ui',
                    '-apple-system',
                    'sans-serif',
                    ...defaultTheme.fontFamily.sans,
                ],
            },
            colors: {
                // Brand
                'brand-primary': 'var(--color-brand-primary)',
                'brand-primary-hover': 'var(--color-brand-primary-hover)',
                'brand-primary-active': 'var(--color-brand-primary-active)',
                'brand-primary-soft': 'var(--color-brand-primary-soft)',
                'brand-on': 'var(--color-brand-on)',

                // Action (Primary Actions)
                'action-primary': 'var(--color-action-primary)',
                'action-primary-hover': 'var(--color-action-primary-hover)',
                'action-primary-active': 'var(--color-action-primary-active)',
                'action-primary-soft': 'var(--color-action-primary-soft)',
                'action-on': 'var(--color-action-on)',

                // Surfaces
                surface: 'var(--color-surface)',
                'surface-muted': 'var(--color-surface-muted)',
                'surface-raised': 'var(--color-surface-raised)',
                border: 'var(--color-border)',
                'border-strong': 'var(--color-border-strong)',

                // Text
                'text-primary': 'var(--color-text-primary)',
                'text-secondary': 'var(--color-text-secondary)',
                'text-muted': 'var(--color-text-muted)',
                'text-inverse': 'var(--color-text-inverse)',

                // Focus
                'focus-ring': 'var(--color-focus-ring)',
                'focus-ring-soft': 'var(--color-focus-ring-soft)',

                // Selection / Toggle states
                'selection-bg': 'var(--color-selection-bg)',
                'selection-text': 'var(--color-selection-text)',
                'selection-ring': 'var(--color-selection-ring)',
                'toggle-off-bg': 'var(--color-toggle-off-bg)',
                'toggle-thumb': 'var(--color-toggle-thumb)',

                // Semantic Feedback
                success: 'var(--color-success)',
                'success-soft': 'var(--color-success-soft)',
                warning: 'var(--color-warning)',
                'warning-soft': 'var(--color-warning-soft)',
                danger: 'var(--color-danger)',
                'danger-soft': 'var(--color-danger-soft)',
                info: 'var(--color-info)',
                'info-soft': 'var(--color-info-soft)',
            },
        },
    },

    plugins: [forms],
};
