<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="min-h-screen pt-20 pb-20 text-center flex flex-col justify-center items-center">
    <i class="pi pi-exclamation-triangle text-yellow-500 text-7xl mb-5"></i>
    <h1 class="text-6xl font-bold mb-4">404 Not Found</h1>
    <p class="text-xl mb-5">This page does not exist</p>
    <RouterLink to="/" class="text-white bg-gray-700 hover:bg-gray-900 rounded-md px-3 py-2 mt-4"
      >Go Back</RouterLink
    >
  </section>
</template>
