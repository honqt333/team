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
                'brand-primary': 'rgb(var(--color-brand-primary-rgb) / <alpha-value>)',
                'brand-primary-hover': 'rgb(var(--color-brand-primary-hover-rgb) / <alpha-value>)',
                'brand-primary-active':
                    'rgb(var(--color-brand-primary-active-rgb) / <alpha-value>)',
                'brand-primary-soft': 'var(--color-brand-primary-soft)',
                'brand-on': 'rgb(var(--color-brand-on-rgb) / <alpha-value>)',

                // Accent Gold (heritage highlight)
                'accent-gold': 'rgb(var(--color-accent-gold-rgb) / <alpha-value>)',
                'accent-gold-light': 'rgb(var(--color-accent-gold-light-rgb) / <alpha-value>)',
                'accent-gold-soft': 'var(--color-accent-gold-soft)',
                'accent-gold-soft-dark': 'var(--color-accent-gold-soft-dark)',

                // Action (Primary Actions)
                'action-primary': 'rgb(var(--color-action-primary-rgb) / <alpha-value>)',
                'action-primary-hover':
                    'rgb(var(--color-action-primary-hover-rgb) / <alpha-value>)',
                'action-primary-active':
                    'rgb(var(--color-action-primary-active-rgb) / <alpha-value>)',
                'action-primary-soft': 'var(--color-action-primary-soft)',
                'action-on': 'rgb(var(--color-action-on-rgb) / <alpha-value>)',

                // Surfaces (canonical + semantic aliases)
                surface: 'rgb(var(--color-surface-rgb) / <alpha-value>)',
                'surface-primary': 'rgb(var(--color-surface-rgb) / <alpha-value>)',
                'surface-muted': 'rgb(var(--color-surface-muted-rgb) / <alpha-value>)',
                'surface-secondary': 'rgb(var(--color-surface-muted-rgb) / <alpha-value>)',
                'surface-raised': 'rgb(var(--color-surface-raised-rgb) / <alpha-value>)',
                'surface-tertiary': 'rgb(var(--color-surface-tertiary-rgb) / <alpha-value>)',
                border: 'rgb(var(--color-border-rgb) / <alpha-value>)',
                'border-subtle': 'rgb(var(--color-border-rgb) / <alpha-value>)',
                'border-strong': 'rgb(var(--color-border-strong-rgb) / <alpha-value>)',

                // Text (canonical + content-* aliases)
                'text-primary': 'rgb(var(--color-text-primary-rgb) / <alpha-value>)',
                'content-primary': 'rgb(var(--color-text-primary-rgb) / <alpha-value>)',
                'text-secondary': 'rgb(var(--color-text-secondary-rgb) / <alpha-value>)',
                'content-secondary': 'rgb(var(--color-text-secondary-rgb) / <alpha-value>)',
                'text-muted': 'rgb(var(--color-text-muted-rgb) / <alpha-value>)',
                'content-muted': 'rgb(var(--color-text-muted-rgb) / <alpha-value>)',
                'text-inverse': 'rgb(var(--color-text-inverse-rgb) / <alpha-value>)',

                // Focus
                'focus-ring': 'rgb(var(--color-focus-ring-rgb) / <alpha-value>)',
                'focus-ring-soft': 'var(--color-focus-ring-soft)',

                // Selection / Toggle states
                'selection-bg': 'var(--color-selection-bg)',
                'selection-text': 'var(--color-selection-text)',
                'selection-ring': 'var(--color-selection-ring)',
                'toggle-off-bg': 'var(--color-toggle-off-bg)',
                'toggle-thumb': 'var(--color-toggle-thumb)',

                // Semantic Feedback
                success: 'rgb(var(--color-success-rgb) / <alpha-value>)',
                'success-soft': 'rgb(var(--color-success-soft-rgb) / <alpha-value>)',
                warning: 'rgb(var(--color-warning-rgb) / <alpha-value>)',
                'warning-soft': 'rgb(var(--color-warning-soft-rgb) / <alpha-value>)',
                danger: 'rgb(var(--color-danger-rgb) / <alpha-value>)',
                'danger-soft': 'rgb(var(--color-danger-soft-rgb) / <alpha-value>)',
                info: 'rgb(var(--color-info-rgb) / <alpha-value>)',
                'info-soft': 'rgb(var(--color-info-soft-rgb) / <alpha-value>)',
            },
            boxShadow: {
                '2xs': '0 1px 2px 0 rgb(0 0 0 / 0.03)',
                xs: 'var(--shadow-xs)',
            },
        },
    },

    plugins: [forms],
};
