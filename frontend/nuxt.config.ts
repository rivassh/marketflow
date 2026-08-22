// Nuxt configuration for production build
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  
  // Bypass impound restrictions for critical modules
  nitro: {
    server: {
      allowedImports: ['std-env', 'node-mock-http', 'uncrypto', 'iron-webcrypto']
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