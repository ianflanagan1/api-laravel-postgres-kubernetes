<script setup lang="ts">
import { defineProps, reactive, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import PulseLoader from 'vue-spinner/src/PulseLoader.vue'
import BackButton from '@/components/BackButton.vue'
import api from '@/lib/axios'
import { type ApiResponseSuccess } from '@/types/common'
import type { ItemFull } from '@/types/item'
import { handleApi, type PageState } from '@/utils/apiHandler'

const router = useRouter()
const toast = useToast()

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
})

interface State {
  item: ItemFull | Record<string, never>
}

const state = reactive<State>({
  item: {},
})

const pageState = reactive<PageState>({
  isLoading: true,
})

const deleteItem = async () => {
  await handleApi(async () => {
    await api.delete(`/items/${props.id}`)
    router.push('/items')
    toast.success('Item deleted')
  })
}

onMounted(async () => {
  await handleApi(
    async () => {
      const response = await api.get<ApiResponseSuccess<ItemFull>>(`/items/${props.id}`)
      state.item = response.data.data
    },
    undefined,
    pageState,
  )
})
</script>

<template>
  <BackButton />
  <!-- loading spinner -->
  <div v-if="pageState.isLoading" class="text-center text-gray-500 py-6">
    <PulseLoader class="mt-20" color="black" />
  </div>
  <!-- loaded content -->
  <section v-else class="bg-gray-50">
    <div class="container m-auto py-10 px-6">
      <div class="grid grid-cols-1 md:grid-cols-70/30 w-full gap-6">
        <main>
          <div class="bg-white p-6 rounded-lg shadow-md text-center md:text-left">
            <h1 class="text-3xl font-bold mb-4">{{ state.item.name }}</h1>
            <div class="text-gray-500 mb-4">{{ state.item.type }}</div>
          </div>
        </main>

        <div class="bg-white p-6 rounded-lg shadow-md mt-6">
          <h3 class="text-xl font-bold mb-6">Manage Item</h3>
          <RouterLink
            :to="`/items/edit/${state.item.id}`"
            class="bg-gray-500 hover:bg-gray-600 text-white text-center font-bold py-2 px-4 rounded-full w-full focus:outline-none focus:shadow-outline mt-4 block"
            >Edit Item</RouterLink
          >
          <button
            @click="deleteItem"
            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-full w-full focus:outline-none focus:shadow-outline mt-4 block"
          >
            Delete Item
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
