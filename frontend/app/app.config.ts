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
            primary: 'blue',
            neutral: 'slate',
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
    },
    echo: {
        tokenStorage: localTokenStorage,
    },

})