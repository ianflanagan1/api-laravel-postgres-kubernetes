<script setup lang="ts">
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/lib/axios'
import { handleApi, type ErrorState } from '@/utils/apiHandler'
import { type ApiResponseSuccess } from '@/types/common'
import type { ItemFull } from '@/types/item'

const router = useRouter()
const toast = useToast()

type Payload = Omit<ItemFull, 'id'>

const state = reactive<Payload>({
  name: '',
  type: '',
})

const errorState = reactive<ErrorState>({
  showError: false,
  error: null,
})

const handleSubmit = async () => {
  const newItem: Payload = {
    name: state.name,
    type: state.type,
  }
  await handleApi(async () => {
    const response = await api.post<ApiResponseSuccess<ItemFull>>('/items', newItem)
    const itemId = response.data?.data?.id
    if (itemId) {
      router.push(`/items/${itemId}`)
      toast.success('Item created')
    } else {
      toast.error('Item added, but could not retrieve ID for navigation')
    }
  }, errorState)
}
</script>

<template>
  <section class="min-h-screen pt-20 bg-gray-50">
    <div class="container m-auto max-w-2xl py-24">
      <div class="bg-white px-6 py-8 mb-4 shadow-md rounded-md border m-4 md:m-0">
        <form @submit.prevent="handleSubmit">
          <h2 class="text-3xl text-center font-semibold mb-6">Add Item</h2>
          <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Name</label>
            <input
              type="text"
              v-model="state.name"
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
              v-model="state.type"
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
              Add Item
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>
