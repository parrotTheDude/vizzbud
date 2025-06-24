import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            typography: ({ theme }) => ({
                invert: {
                    css: {
                        color: theme('colors.slate.100'),
                        a: { color: theme('colors.cyan.400'), textDecoration: 'underline' },
                        strong: { color: theme('colors.white') },
                        h1: { color: theme('colors.cyan.300'), fontSize: theme('fontSize.3xl') },
                        h2: { color: theme('colors.cyan.200') },
                        h3: { color: theme('colors.cyan.100') },
                        p: { marginTop: theme('spacing.4'), marginBottom: theme('spacing.4') },
                        blockquote: { borderLeftColor: theme('colors.cyan.500') },
                        code: {
                            backgroundColor: theme('colors.slate.800'),
                            padding: '2px 4px',
                            borderRadius: '4px',
                        },
                        'code::before': { content: '""' },
                        'code::after': { content: '""' },
                    },
                },
                DEFAULT: {
                    css: {
                        color: theme('colors.slate.800'),
                        a: { color: theme('colors.cyan.600'), textDecoration: 'underline' },
                        h1: { fontSize: theme('fontSize.3xl') },
                        h2: { fontSize: theme('fontSize.2xl') },
                    },
                },
            }),
        },
    },

    plugins: [forms, typography],
};