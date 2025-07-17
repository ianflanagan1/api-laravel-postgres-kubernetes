<script setup lang="ts">
import { RouterView } from 'vue-router'
import NavBar from '@/components/NavBar.vue'
</script>

<template>
  <!-- <div class="flex flex-col min-h-screen"> -->
  <NavBar />
  <main class="min-h-screen">
    <!-- <main class="min-h-[calc(100vh-80px)]"> -->
    <!-- <main class="flex-grow min-h-[calc(100vh-5rem)] flex flex-col"> -->
    <!-- <main class="flex flex-col min-h-[calc(100vh-5rem)] items-center justify-center px-4 bg-gray-50"> -->
    <RouterView />
  </main>
  <!-- </div> -->
</template>

<!-- <template>
  <NavBar />
  <RouterView />
</template> -->
