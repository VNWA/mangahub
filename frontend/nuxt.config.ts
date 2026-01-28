// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@nuxt/ui', '@pinia/nuxt', 'nuxt-laravel-echo'],
  css: ['~/assets/css/main.css', '~/assets/css/vnwa.scss'],
  echo: {
    key: 'local',
    broadcaster: 'reverb', // available: reverb, pusher
    host: 'localhost',
    port: 8080,
    scheme: 'http', // available: http, https - Change to https in production
    transports: ['ws'],
    authentication: {
      mode: 'token',
      baseUrl: 'http://127.0.0.1:8000',
      authEndpoint: 'api/v1/broadcasting/auth',
    },
    logLevel: 3,
    properties: undefined,
  },
  vite: {
    optimizeDeps: {
      include: ['nuxt-laravel-echo > pusher-js'], // or ['pusher-js'] for older Vite versions
    },
  },
  app: {
    head: {
      title: 'WebTruyện - Đọc Truyện Tranh Online Miễn Phí',
      htmlAttrs: {
        lang: 'vi'
      },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1, maximum-scale=1' },
        { name: 'description', content: 'WebTruyện - Nền tảng đọc truyện tranh online lớn nhất Việt Nam. Hàng ngàn bộ truyện tranh, manga, comic được cập nhật hàng ngày' },
        { name: 'keywords', content: 'truyện tranh, manga, comic, đọc truyện online, webcomic, truyện tranh miễn phí' },
        { name: 'author', content: 'WebTruyện Team' },
        { name: 'theme-color', content: '#2563eb' },
        { name: 'mobile-web-app-capable', content: 'yes' },

        // Open Graph
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: 'https://webtruyen.example.com' },
        { property: 'og:title', content: 'WebTruyện - Đọc Truyện Tranh Online' },
        { property: 'og:description', content: 'Nền tảng đọc truyện tranh online hàng đầu Việt Nam' },
        { property: 'og:image', content: 'https://via.placeholder.com/1200x630?text=WebTruyen' },
        { property: 'og:site_name', content: 'WebTruyện' },

        // Twitter Card
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'WebTruyện - Đọc Truyện Tranh Online' },
        { name: 'twitter:description', content: 'Hàng ngàn bộ truyện tranh được cập nhật hàng ngày' },
        { name: 'twitter:image', content: 'https://via.placeholder.com/1200x630?text=WebTruyen' },
      ],
      link: [
        { rel: 'canonical', href: 'https://webtruyen.example.com' },
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }
      ]
    }
  },
  runtimeConfig: {
    public: {
      appUrl: 'http://localhost:3000',
      apiUrl: 'http://127.0.0.1:8000/api/v1',
      storageUrl: 'http://127.0.0.1:8000/storage',
    },
  }
})
