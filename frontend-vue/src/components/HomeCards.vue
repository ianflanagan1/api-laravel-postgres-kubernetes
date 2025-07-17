<script setup lang="ts">
import { RouterLink } from 'vue-router'
import CardComponent from '@/components/CardComponent.vue'
</script>

<template>
  <section class="py-4">
    <div class="container-xl lg:container m-auto">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-lg">
        <CardComponent>
          <h2 class="text-2xl font-bold">Browse items</h2>
          <p class="mt-2 mb-4">This card is about browsing items</p>
          <RouterLink
            to="/items"
            class="inline-block bg-black text-white rounded-lg px-4 py-2 hover:bg-gray-700"
          >
            Browse Items
          </RouterLink>
        </CardComponent>
        <CardComponent bg="bg-gray-100">
          <h2 class="text-2xl font-bold">Add Item</h2>
          <p class="mt-2 mb-4">This card is about adding items</p>
          <RouterLink
            to="/items/add"
            class="inline-block bg-gray-500 text-white rounded-lg px-4 py-2 hover:bg-gray-600"
          >
            Add Item
          </RouterLink>
        </CardComponent>
      </div>
    </div>
  </section>
</template>
