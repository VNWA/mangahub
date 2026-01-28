
export default defineNuxtPlugin(async (nuxtApp) => {
    const auth = useAuthStore();
    const echoAppConfig = useEchoAppConfig()

    const config = useRuntimeConfig();
    if (auth.logged) {
        await auth.fetchUser();
    }
})