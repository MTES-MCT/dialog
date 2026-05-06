import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        include: ['assets/**/*.test.js'],
        environment: 'node',
    },
});
