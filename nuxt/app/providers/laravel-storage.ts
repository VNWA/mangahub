import { joinURL } from 'ufo'
import { createOperationsGenerator, defineProvider } from '@nuxt/image/runtime'

const operationsGenerator = createOperationsGenerator()

export default defineProvider<{ baseURL?: string }>({
  getImage (src, { modifiers, baseURL }) {
    const config = useRuntimeConfig();
    baseURL = baseURL || config.public.storageUrl || ''
    const operations = operationsGenerator(modifiers)

    return {
      url: joinURL(baseURL, src + (operations ? '?' + operations : ''))
    }
  }
})
