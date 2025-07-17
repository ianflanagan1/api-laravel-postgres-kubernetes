import { test, describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import ItemsView from '../../views/ItemsView.vue'

// describe('HelloWorld', () => {
//   it('renders properly', () => {
//     const wrapper = mount(HelloWorld, { props: { msg: 'Hello Vitest' } })
//     expect(wrapper.text()).toContain('Hello Vitest')
//   })
// })

const user = {
  name: 'Matt',
  age: 22,
}

test('Matt is 22', () => {
  expect(user.name).toBe('Matt')
  expect(user.age).toBe(22)
})

test('mount component', async () => {
  expect(ItemsView).toBeTruthy()

  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', component: { template: '<div>Home</div>' } },
      { path: '/items', component: { template: '<div>Items</div>' } },
      { path: '/items/add', component: { template: '<div>Add Items</div>' } },
    ],
  })

  const wrapper = mount(ItemsView, {
    global: {
      plugins: [router],
    },
    // props: {
    //   msg: "Hello from Vitest"
    // }
  })

  await router.isReady()

  expect(wrapper.text()).toContain('Browse Items')
})
