import { defineConfig } from 'vite'
import path from 'path'
import pkpThemePlugin from 'vite-pkp-theme'

const viteServer = './.vite.server.json'

export default defineConfig({
  plugins: [
    pkpThemePlugin({configFile: viteServer}),
  ],
  build: {
    manifest: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'src', 'main.js'),
        system: path.resolve(__dirname, 'src', 'system.js'),
      },
    },
  },
})
