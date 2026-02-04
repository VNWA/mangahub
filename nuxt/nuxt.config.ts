
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  css: ['~/assets/css/main.css', '~/assets/css/vnwa.scss'],
  modules: [
    '@nuxt/ui',
    '@pinia/nuxt',
    'nuxt-laravel-echo',
    '@nuxt/image'
  ],


  vite: {
    optimizeDeps: {
      include: ['nuxt-laravel-echo > pusher-js'],
    },
  },

  image: {
    provider: 'storage',
    providers: {
      storage: {
        name: 'storage', // optional value to overrider provider name
        provider: '~/providers/storage.ts', // Path to custom provider

      }
    },
    presets: {
      avatar: {
        modifiers: {
          format: 'webp',
          width: 245,
          height: 350
        }
      }
    },
    domains: [
      '127.0.0.1',
      'localhost'
    ],
    alias: {
      '127.0.0.1': 'https://127.0.0.1',
      'localhost': 'https://localhost'
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
