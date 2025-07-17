<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section>
    <div class="container m-auto py-6 px-6">
      <RouterLink to="/items" class="text-gray-500 hover:text-gray-600 flex items-center">
        <i class="pi pi-arrow-circle-left mr-3"></i> Back to Item Listings
      </RouterLink>
    </div>
  </section>
</template>
