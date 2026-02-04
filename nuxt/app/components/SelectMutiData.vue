<script setup lang="ts">
type SelectOption = {
  label: string
  value: string
}

const props = defineProps<{
  url: string
  modelValue?: string[]
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string[]): void
}>()

/* fetch options */
const { data, status, execute } = useLazyHttp<SelectOption[]>(props.url, {
  key: `select-mutiple-${props.url}`,
  transform: (res: any) =>
    res.data.map((c: any) => ({
      label: c.name,
      value: c.id.toString(),
    })),
})

function onOpen() {
  if (!data.value?.length) execute()
}

/* ✅ COMPUTED V-MODEL (NO WATCH LOOP) */
const internalValue = computed<string[]>({
  get() {
    return Array.isArray(props.modelValue)
      ? props.modelValue.map(v => v.toString())
      : []
  },
  set(val) {
    emit('update:modelValue', val)
  },
})
</script>

<template>
  <USelectMenu v-model="internalValue" multiple :items="data ?? []" :loading="status === 'pending'" label-key="label"
    value-key="value" :placeholder="placeholder || 'Select multiple'" class="w-full" @update:open="onOpen" />
</template>
