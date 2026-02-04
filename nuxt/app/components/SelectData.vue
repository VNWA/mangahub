<script setup lang="ts">
type SelectOption = {
  label: string
  value: string
}

const props = defineProps<{
  url: string
  modelValue?: string
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | undefined): void
}>()

/* fetch options */
const { data, status, execute } = useLazyHttp<SelectOption[]>(props.url, {
  key: `select-${props.url}`,
  transform: (res: any) =>
    res.data.map((c: any) => ({
      label: c.name,
      value: c.id.toString(),
    })),
})

function onOpen() {
  if (!data.value?.length) execute()
}

/* internal value — KHÔNG NULL */
const internalValue = ref<string | undefined>(undefined)

/* parent -> child */
watch(
  () => props.modelValue,
  (val) => {
    internalValue.value = val ? val.toString() : undefined
  },
  { immediate: true }
)

/* child -> parent */
watch(internalValue, (val) => {
  emit('update:modelValue', val)
})

</script>
<template>
  <USelectMenu v-model="internalValue" :items="data ?? []" :loading="status === 'pending'" label-key="label"
    value-key="value" :placeholder="placeholder || 'Select'" class="w-full" @update:open="onOpen" />
</template>
