<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img src="/vnwa/vnwa_icon.webp" :class="className" v-bind="$attrs" alt="VNWA"
        class="w-full h-full object-contain" />
</template>
