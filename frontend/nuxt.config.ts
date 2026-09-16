// Nuxt configuration for production build
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  
  modules: ['@pinia/nuxt', '@nuxtjs/tailwindcss'],
  
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'https://torob.adlr.ir/api'
    }
  },
  
  // Preserve client/vite configuration
  vite: {
    build: {
      rollupOptions: {
        external: ['std-env', 'node-mock-http', 'uncrypto', 'iron-webcrypto']
      }
    }
  }
})
