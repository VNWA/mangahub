<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';

interface Option {
    value: string | number | null;
    label: string;
    disabled?: boolean;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        defaultValue?: string | number | null;
        options: Option[];
        placeholder?: string;
        disabled?: boolean;
        required?: boolean;
        class?: HTMLAttributes['class'];
        id?: string;
    }>(),
    {
        placeholder: 'Chọn...',
        disabled: false,
        required: false,
    },
);

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number | null): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});

const isOpen = ref(false);
const selectRef = ref<HTMLSelectElement | null>(null);

const selectedOption = computed(() => {
    return props.options.find((opt) => opt.value === modelValue.value);
});

const displayValue = computed(() => {
    return selectedOption.value?.label || props.placeholder;
});

const handleSelect = (option: Option) => {
    if (option.disabled) {
        return;
    }
    modelValue.value = option.value;
    isOpen.value = false;
};

const toggleOpen = (event?: MouseEvent) => {
    if (!props.disabled) {
        event?.stopPropagation();
        nextTick(() => {
            isOpen.value = !isOpen.value;
        });
    }
};

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
    if (selectRef.value && !selectRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
};

// Listen for outside clicks
onMounted(() => {
    if (typeof window !== 'undefined') {
        // Delay to avoid immediate close when opening
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 100);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        document.removeEventListener('click', handleClickOutside);
    }
});
</script>

<template>
    <div ref="selectRef" class="relative w-full" :class="props.class">
        <button
            :id="id"
            type="button"
            :disabled="disabled"
            :required="required"
            @click="toggleOpen"
            :class="
                cn(
                    'flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none',
                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                    !selectedOption && 'text-muted-foreground',
                )
            "
        >
            <span class="truncate">{{ displayValue }}</span>
            <ChevronDown
                :class="cn('h-4 w-4 shrink-0 text-muted-foreground transition-transform', isOpen && 'rotate-180')"
            />
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 mt-1 max-h-[300px] w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
            >
                <div
                    v-for="option in options"
                    :key="String(option.value)"
                    @click="handleSelect(option)"
                    :class="
                        cn(
                            'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none',
                            'hover:bg-accent hover:text-accent-foreground',
                            'focus:bg-accent focus:text-accent-foreground',
                            option.value === modelValue && 'bg-accent text-accent-foreground',
                            option.disabled && 'pointer-events-none opacity-50',
                        )
                    "
                >
                    {{ option.label }}
                </div>
            </div>
        </Transition>
    </div>
</template>
