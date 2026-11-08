import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// The dev server proxies the API so the browser sees a single origin
// (required for the Sanctum cookie session to work without CORS tweaks).
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, '.', '')
  const target = env.VITE_PROXY_TARGET || 'http://localhost:8080'

  return {
    plugins: [react(), tailwindcss()],
    server: {
      proxy: {
        '/api': { target, changeOrigin: false },
        '/sanctum': { target, changeOrigin: false },
      },
    },
  }
})
