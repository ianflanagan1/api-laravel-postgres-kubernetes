<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import api from '@/lib/axios'
import PulseLoader from 'vue-spinner/src/PulseLoader.vue'
import ItemListing from '@/components/ItemListing.vue'
import { type ApiResponsePaginated } from '@/types/common'
import type { ItemMinimal } from '@/types/item'
import { handleApi, type PageState } from '@/utils/apiHandler'

interface State {
  items: ItemMinimal[]
  current_page: number
  last_page: number
}

const state = reactive<State>({
  items: [],
  current_page: 0,
  last_page: 0,
})

const pageState = reactive<PageState>({
  isLoading: true,
})

const getItems = async () => {
  await handleApi(
    async () => {
      const page = state.current_page + 1
      const response = await api.get<ApiResponsePaginated<ItemMinimal>>(
        '/items?per_page=9&page=' + page,
      )

      state.items.push(...response.data.data)
      state.current_page = response.data.pagination.current_page
      state.last_page = response.data.pagination.last_page
    },
    undefined,
    pageState,
  )
}

onMounted(async () => {
  getItems()
})
</script>

<template>
  <div class="min-h-screen pt-20">
    <section class="bg-blue-50 p-4 py-10">
      <div class="container-xl lg:container m-auto">
        <h2 class="text-3xl font-bold text-gray-500 mb-6 text-center">Browse Items</h2>
        <!-- loaded content -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <ItemListing v-for="item in state.items" :key="item.id" :item="item" />
        </div>
        <button
          v-if="state.current_page < state.last_page && !pageState.isLoading"
          @click="getItems"
          class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-full w-full focus:outline-none focus:shadow-outline mt-4 block"
        >
          Load more
        </button>
        <!-- loading spinner -->
        <div v-if="pageState.isLoading" class="text-center text-gray-500 py-6">
          <PulseLoader color="black" />
        </div>
      </div>
    </section>
  </div>
</template>
