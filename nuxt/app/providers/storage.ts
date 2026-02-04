import { joinURL } from 'ufo'
import { createOperationsGenerator, defineProvider } from '@nuxt/image/runtime'

const operationsGenerator = createOperationsGenerator()

export default defineProvider<{ baseURL?: string }>({
    getImage(src, { modifiers, baseURL }) {
        if (!src) return { url: '/no-image.png' };
        if (src.startsWith('http://') || src.startsWith('https://')) {
            return { url: src };
        }
        const config = useRuntimeConfig();
        if (!baseURL) {
            // also support runtime config 
            baseURL = config.public.storageUrl as string
        }

        const operations = operationsGenerator(modifiers)
        return {
            url: joinURL(baseURL, src + (operations ? '?' + operations : ''))
        }
    }
})
