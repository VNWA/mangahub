import { defineStore } from 'pinia'

export type User = {
    id?: number;
    ulid?: string;
    name: string;
    email: string;
    avatar?: string | null;
    coin?: number;
    notify_email_new_chapter?: boolean;
    notify_email_comment_reply?: boolean;
    notify_email_recommendations?: boolean;
    notify_push_new_chapter?: boolean;
    notify_push_comment_reply?: boolean;
    privacy_public_profile?: boolean;
    privacy_show_reading_history?: boolean;
    privacy_show_favorites?: boolean;
    must_verify_email: boolean;
    has_password: boolean;
    roles: string[];
    connected_providers?: string[];
    providers?: string[];
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
}

export const useAuthStore = defineStore('auth', () => {
    const config = useRuntimeConfig();

    const tokenCookie = useCookie('token', {
        path: '/',
        sameSite: 'strict',
        secure: config.public.apiUrl.startsWith('https://'),
        maxAge: 60 * 60 * 24 * 365
    });

    const loggedCookie = useCookie('logged', {
        path: '/',
        sameSite: 'strict',
        secure: config.public.apiUrl.startsWith('https://'),
        maxAge: 60 * 60 * 24 * 365
    });

    const user = ref(<User>{});

    const { refresh: logout } = useHttp<any>('logout', {
        method: 'POST',
        immediate: false,
        onFetchResponse: ({ response }) => {
            if (response.status === 200) {
                reset();
                navigateTo('/');
            }
        }
    });

    const { refresh: fetchUser } = useHttp<any>('user', {
        immediate: false,
        onFetchResponse({ response }) {
            if (response.status === 200 && response._data?.ok && response._data?.user) {
                user.value = response._data.user
            }
        },
        onFetchResponseError({ response }) {
            if (response?.status === 401) {
                reset();
            }
        }
    });


    async function login(token?: string | null): Promise<void> {
        if (token) {
            tokenCookie.value = token;
        }
        loggedCookie.value = '1';
        await fetchUser();
        
        // Sync favorites and reading history from cookies
        if (import.meta.client) {
            try {
                const favoritesCookie = useCookie<number[]>('favorites', { default: () => [] });
                const historyCookie = useCookie<any[]>('reading_history', { default: () => [] });
                
                if (favoritesCookie.value.length > 0 || historyCookie.value.length > 0) {
                    await $http('/sync-data', {
                        method: 'POST',
                        body: {
                            favorites: favoritesCookie.value,
                            reading_history: historyCookie.value
                        }
                    });
                    
                    // Clear cookies after sync
                    favoritesCookie.value = [];
                    historyCookie.value = [];
                }
            } catch (error) {
                console.error('Failed to sync data:', error);
            }
        }
    }

    function reset(): void {
            tokenCookie.value = null;
        loggedCookie.value = null;
        user.value = <User>{}
    }

    function hasRole(name: string): boolean {
        return (user.value.roles ?? []).includes(name);
    }

    return {
        user,
        token: tokenCookie,
        logged: loggedCookie,
        login,
        logout,
        fetchUser,
        reset,
        hasRole,
    }
})