<template>
    <ClientOnly>
        <div>
            <div :class="classes" :style="badgeStyles">
                <span>{{ badge.name }}</span>
            </div>
        </div>
    </ClientOnly>
</template>

<script lang="ts" setup>
import type { Badge } from '~/types/badge'

const props = defineProps<{
    badge: Badge
    variant?: 'solid' | 'outline'
    size?: 'sm' | 'md' | 'lg'
}>()

const colorMode = useColorMode()

const sizeClasses = {
    sm: 'text-xs px-2 py-1',
    md: 'text-sm px-3 py-2',
    lg: 'text-lg px-4 py-3',
}

const classes = computed(() => {
    const baseClasses = [sizeClasses[props.size || 'sm'], 'rounded-xl']

    if (props.variant === 'outline') {
        baseClasses.push('border')
    }

    return baseClasses
})

const badgeStyles = computed(() => {
    const isDark = colorMode.value === 'dark'
    const bgColor = isDark ? props.badge.dark_bg_color : props.badge.light_bg_color
    const textColor = isDark ? props.badge.dark_text_color : props.badge.light_text_color

    if (props.variant === 'outline') {
        return {
            borderColor: bgColor,
            color: textColor,
            backgroundColor: 'transparent',
        }
    }

    return {
        backgroundColor: bgColor,
        color: textColor,
    }
})
</script>