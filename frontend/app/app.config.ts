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
    },
    echo: {
        tokenStorage: localTokenStorage,
    },

})