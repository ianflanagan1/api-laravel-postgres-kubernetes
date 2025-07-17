<script setup lang="ts">
import { defineProps, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import PulseLoader from 'vue-spinner/src/PulseLoader.vue'
import api from '@/lib/axios'
import type { ItemFull } from '@/types/item'
import { handleApi, type PageState, type ErrorState } from '@/utils/apiHandler'
import type { ApiResponseSuccess } from '@/types/common'

const router = useRouter()
const toast = useToast()

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
})

interface State {
  item: ItemFull
}

const state = reactive<State>({
  item: {
    id: '',
    name: '',
    type: '',
  },
})

const errorState = reactive<ErrorState>({
  showError: false,
  error: null,
})

const pageState = reactive<PageState>({
  isLoading: true,
})

const handleSubmit = async () => {
  await handleApi(async () => {
    const response = await api.put<ApiResponseSuccess<ItemFull>>(`/items/${props.id}`, state.item)
    router.push(`/items/${props.id}`)
    toast.success('Item updated')
  }, errorState)
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
  <section class="min-h-screen pt-20 bg-gray-50">
    <div class="container m-auto max-w-2xl py-24">
      <div class="bg-white px-6 py-8 mb-4 shadow-md rounded-md border m-4 md:m-0">
        <!-- loading spinner -->
        <div v-if="pageState.isLoading" class="text-center text-gray-500 py-6">
          <PulseLoader color="black" />
        </div>
        <!-- loaded content -->
        <form v-else @submit.prevent="handleSubmit">
          <h2 class="text-3xl text-center font-semibold mb-6">Edit Item</h2>
          <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Name</label>
            <input
              type="text"
              v-model="state.item.name"
              id="name"
              name="name"
              class="border rounded w-full py-2 px-3 mb-2"
              placeholder="Item name"
              required
            />
          </div>
          <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Type</label>
            <input
              type="text"
              v-model="state.item.type"
              id="type"
              name="type"
              class="border rounded w-full py-2 px-3 mb-2"
              placeholder="Item type"
              required
            />
          </div>
          <div
            v-if="errorState.showError"
            class="p-3 bg-red-100 text-red-700 border border-red-400 rounded-md font-semibold mb-4"
          >
            {{ errorState.error }}
          </div>
          <div>
            <button
              class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-full w-full focus:outline-none focus:shadow-outline"
              type="submit"
            >
              Edit Item
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>
