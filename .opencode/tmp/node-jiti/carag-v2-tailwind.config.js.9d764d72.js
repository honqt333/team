"use strict";Object.defineProperty(exports, "__esModule", {value: true}); function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }var _defaultTheme = require('tailwindcss/defaultTheme'); var _defaultTheme2 = _interopRequireDefault(_defaultTheme);
var _forms = require('@tailwindcss/forms'); var _forms2 = _interopRequireDefault(_forms);

/** @type {import('tailwindcss').Config} */
exports. default = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ..._defaultTheme2.default.fontFamily.sans],
            },
        },
    },

    plugins: [_forms2.default],
};
 /* v7-12bf673d0e2b5b6e */