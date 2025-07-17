<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { ItemMinimal } from '@/types/item'

const props = defineProps<Props>()

interface Props {
  item: ItemMinimal
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-md relative">
    <div class="p-4">
      <div class="mb-6">
        <h3 class="text-xl font-bold">{{ item.name }}</h3>
      </div>
      <RouterLink
        :to="'/items/' + item.id"
        class="h-[36px] bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-center text-sm"
      >
        Read More
      </RouterLink>
    </div>
  </div>
</template>
