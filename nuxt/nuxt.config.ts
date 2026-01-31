
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@nuxt/ui', '@pinia/nuxt', 'nuxt-laravel-echo', '@nuxt/image'],
  css: ['~/assets/css/main.css', '~/assets/css/vnwa.scss'],
 
  vite: {
    optimizeDeps: {
      include: ['nuxt-laravel-echo > pusher-js'],
    },
  },
  image: {
    providers: {
      laravel: {
        name: 'laravel',
        provider: '~/providers/laravel-storage.ts',

      }
    }
  },
  runtimeConfig: {
    public: {
      echo: {
        key: 'local',
        broadcaster: 'reverb',
        host: 'localhost',
        port: 8080,
        scheme: 'http',
        transports: ['ws'],
        authentication: {
          mode: 'token',
          baseUrl: '',
          authEndpoint: '',
        },
        logLevel: 3,
        properties: undefined,
      },
      appUrl: '',
      apiUrl: '',
      storageUrl: '',
    },
  }
})
