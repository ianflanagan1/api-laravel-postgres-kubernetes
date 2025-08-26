<script setup lang="ts">
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/axios'
import { type ApiResponseSuccess } from '@/types/common'
import { handleApi } from '@/utils/apiHandler'
import logo from '@/assets/img/logo.svg'

const toast = useToast()
const auth = useAuthStore()
const router = useRouter()

interface LogoutResponseData {
  result: string
}

const isActiveLink = (routePath: string): boolean => {
  const route = useRoute()
  return route.path === routePath
}

const handleLogout = async () => {
  await handleApi(async () => {
    await api.post<ApiResponseSuccess<LogoutResponseData>>('/logout')
    toast.success('Logged out')
  })

  auth.clearToken()
  router.push('/')
}
</script>

<template>
  <nav class="bg-gray-700 border-b border-gray-500 fixed z-10 top-0 w-full">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
      <div class="flex h-20 items-center justify-between">
        <div
          v-if="auth.isLoggedIn"
          class="flex flex-1 items-center justify-center md:items-stretch md:justify-start"
        >
          <!-- Logo -->
          <RouterLink class="flex flex-shrink-0 items-center mr-4" to="/">
            <img class="size-14" :src="logo" alt="Items" />
            <span class="hidden md:block text-white text-2xl font-bold ml-2">Items</span>
          </RouterLink>
          <div class="md:ml-auto pt-2">
            <div class="flex space-x-2">
              <RouterLink
                to="/"
                :class="[
                  isActiveLink('/') ? 'bg-gray-900 hover:bg-gray-900' : 'hover:bg-gray-900',
                  'text-white',
                  'rounded-md',
                  'px-3',
                  'py-2',
                ]"
                >Home</RouterLink
              >
              <RouterLink
                to="/items"
                :class="[
                  isActiveLink('/items') ? 'bg-gray-900 hover:bg-gray-900' : 'hover:bg-gray-900',
                  'text-white',
                  'rounded-md',
                  'px-3',
                  'py-2',
                ]"
                >Items</RouterLink
              >
              <RouterLink
                to="/items/add"
                :class="[
                  isActiveLink('/items/add')
                    ? 'bg-gray-900 hover:bg-gray-900'
                    : 'hover:bg-gray-900',
                  'text-white',
                  'rounded-md',
                  'px-3',
                  'py-2',
                ]"
                >Add Item</RouterLink
              >
              <button
                @click="handleLogout"
                :class="['hover:bg-gray-900', 'text-white', 'rounded-md', 'px-3', 'py-2']"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
        <div
          v-else
          class="flex flex-1 items-center justify-center md:items-stretch md:justify-start"
        >
          <!-- Logo -->
          <RouterLink class="flex flex-shrink-0 items-center mr-4" to="/">
            <img class="size-14" :src="logo" alt="Items" />
            <span class="hidden md:block text-white text-2xl font-bold ml-2">Items</span>
          </RouterLink>
          <div class="md:ml-auto pt-2">
            <div class="flex space-x-2">
              <RouterLink
                to="/"
                :class="[
                  isActiveLink('/') ? 'bg-gray-900 hover:bg-gray-900' : 'hover:bg-gray-900',
                  'text-white',
                  'rounded-md',
                  'px-3',
                  'py-2',
                ]"
                >Home</RouterLink
              >
              <RouterLink
                to="/login"
                :class="[
                  isActiveLink('/login') ? 'bg-gray-900 hover:bg-gray-900' : 'hover:bg-gray-900',
                  'text-white',
                  'rounded-md',
                  'px-3',
                  'py-2',
                ]"
                >Login</RouterLink
              >
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>
