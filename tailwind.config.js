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
        },
    },

    plugins: [forms],
};
