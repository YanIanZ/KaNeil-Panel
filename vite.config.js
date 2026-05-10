import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { globSync } from 'glob';

export default defineConfig({
    plugins: [
        react({ include: /resources\/js\/galleon\/.*\.jsx$/ }),
        laravel({
            input: [
                ...globSync('resources/css/**/*.css'),
                ...globSync('resources/js/**/*.js'),
                'resources/js/galleon/app.jsx',
                ...globSync('resources/js/galleon/**/*.jsx'),
                ...globSync('plugins/*/resources/css/**/*.css'),
                ...globSync('plugins/*/resources/js/**/*.js'),
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
