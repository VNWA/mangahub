<template>
  <div class="space-y-2">
    <label v-if="label" class="text-sm font-semibold text-zinc-900 dark:text-white">{{ label }}</label>
    <div class="flex flex-wrap gap-2">
      <UButton v-for="item in items" :key="item.id" :label="item.label"
        :variant="selectedIds.includes(item.id) ? 'soft' : 'ghost'"
        :color="selectedIds.includes(item.id) ? 'primary' : 'neutral'" size="sm" @click="toggleSelect(item.id)" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Item {
  id: string
  label: string
}

interface Props {
  items: Item[]
  modelValue?: string[]
  label?: string
  multiple?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  multiple: true
})

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const selectedIds = computed({
  get: () => props.modelValue || [],
  set: (value) => emit('update:modelValue', value)
})

const toggleSelect = (id: string) => {
  if (props.multiple) {
    const updated = selectedIds.value.includes(id)
      ? selectedIds.value.filter(item => item !== id)
      : [...selectedIds.value, id]
    selectedIds.value = updated
  } else {
    selectedIds.value = selectedIds.value.includes(id) ? [] : [id]
  }
}
</script>
