import axios from '@/axios';
import upload from '@/routes/upload';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { useToast } from 'vue-toastification';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function $tempDelete(paths: string[]) {
    if (paths.length === 0) {
        return;
    }
    return axios.post(upload.tempDelete().url, { paths: paths });
}

export function $toast(message: string, type?: 'success' | 'error' | 'warning' | 'info') {
    if (!type) {
        type = 'info';
    }
    const toast = useToast();
    return toast[type](message);
}
