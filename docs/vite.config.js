import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    root: '.',
    build: {
        outDir: 'dist',
        emptyOutDir: true,
    },
    server: {
        port: 3000,
        open: true,
    },
}); 