<script setup lang="ts">
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/lib/axios'
import PulseLoader from 'vue-spinner/src/PulseLoader.vue'
import { useAuthStore } from '@/stores/auth'
import { type ApiResponseSuccess } from '@/types/common'
import { handleApi, type PageState, type ErrorState } from '@/utils/apiHandler'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()

interface Payload {
  email: string
  password: string
}

interface LoginResponseData {
  token: string
}

const form = reactive<Payload>({
  email: 'test@user.com', // todo: remove test email and password
  password: '12345678',
})

const errorState = reactive<ErrorState>({
  showError: false,
  error: null,
})

const pageState = reactive<PageState>({
  isLoading: false,
})

const handleSubmit = async () => {
  await handleApi(
    async () => {
      const responsePromise = await api.post<ApiResponseSuccess<LoginResponseData>>('/login', form)
      form.password = ''

      const response = await responsePromise

      if (response.data?.data?.token) {
        auth.setToken(response.data.data.token)
        router.push('/')
        toast.success('Logged in')
      } else {
        errorState.error = 'Server error'
        errorState.showError = true
      }
    },
    errorState,
    pageState,
  )
}
</script>

<template>
  <div class="flex items-center justify-center bg-gray-50 min-h-screen pt-20 pb-20">
    <div class="bg-white rounded-xl shadow-md p-8 max-w-md w-full">
      <h2 class="text-3xl font-bold text-gray-600 mb-8 text-center">Login</h2>
      <div v-if="pageState.isLoading" class="text-center text-gray-500 py-6">
        <PulseLoader color="black" />
      </div>
      <form v-else @submit.prevent="handleSubmit" class="space-y-6">
        <div>
          <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
          <input
            id="email"
            type="email"
            v-model="form.email"
            placeholder="Email"
            required
            class="block w-full rounded-md border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition"
          />
        </div>
        <div>
          <label for="password" class="block text-sm font-semibold text-gray-700 mb-1"
            >Password</label
          >
          <input
            id="password"
            type="password"
            v-model="form.password"
            placeholder="Password"
            required
            class="block w-full rounded-md border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition"
          />
        </div>
        <div
          v-if="errorState.showError"
          class="p-3 bg-red-100 text-red-700 border border-red-400 rounded-md font-semibold"
        >
          {{ errorState.error }}
        </div>
        <div>
          <button
            type="submit"
            :disabled="pageState.isLoading"
            class="w-full rounded-md bg-gray-600 hover:bg-gray-700 text-white py-3 font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Login
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
