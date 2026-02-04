const localTokenStorage: TokenStorage = {
    get: async () => {
        const token = useCookie('token').value as string | undefined
        return token
    },

    set: async (app: NuxtApp, token?: string) => {
        useCookie('token').value = token as string
    },
}

export default defineAppConfig({
    ui: {
        colors: {
            primary: 'zinc',
            neutral: 'zinc',
        },

        textarea: {
            slots: {
                root: 'relative inline-flex items-center w-full',
                base: [
                    'w-full rounded-md border-0 appearance-none placeholder:text-dimmed focus:outline-none disabled:cursor-not-allowed disabled:opacity-75',
                    'transition-colors'
                ],
            },
        },
        container: {
            base: 'w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8'
        },

    },
    echo: {
        tokenStorage: localTokenStorage,
    },

})