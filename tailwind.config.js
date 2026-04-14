import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        { pattern: /^dark:/ },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['DM Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // MoonRow-inspired color palette
                primary: {
                    50: '#fff7ed',
                    100: '#ffedd5',
                    200: '#fed7aa',
                    300: '#fdba74',
                    400: '#fb923c',
                    500: '#f97316', // Primary accent color
                    600: '#ea580c',
                    700: '#c2410c',
                    800: '#9a3412',
                    900: '#7c2d12',
                },
                navy: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#1a1a2e', // Dark navy color
                },
                // Semantic colors for dashboard
                success: {
                    50: '#e8f5e9',
                    100: '#c8e6c9',
                    500: '#2e7d32',
                    600: '#1b5e20',
                },
                danger: {
                    50: '#fce4ec',
                    100: '#f8bbd9',
                    500: '#c62828',
                    600: '#b71c1c',
                },
                // Background colors
                background: '#F4F5F7',
                surface: '#ffffff',
                border: '#e8e8e8',
                // Text colors
                'text-primary': '#1a1a2e',
                'text-secondary': '#888888',
                'text-muted': '#aaaaaa',
            },
            borderRadius: {
                'card': '12px',
                'tab': '20px',
            },
            boxShadow: {
                'subtle': '0 0 0 0.5px rgba(0, 0, 0, 0.1)',
            },
        },
    },

    plugins: [forms],
};