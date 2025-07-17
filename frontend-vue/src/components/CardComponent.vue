<script setup lang="ts">
interface Props {
  bg?: string
}

const props = withDefaults(defineProps<Props>(), {
  bg: 'bg-gray-100',
})
</script>

<template>
  <div :class="`${props.bg} p-6 rounded-lg shadow-md`">
    <slot></slot>
  </div>
</template>
