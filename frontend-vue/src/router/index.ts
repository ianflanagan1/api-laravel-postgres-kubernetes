import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import HomeView from '@/views/HomeView.vue'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { requiresAuth: false },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/items',
    name: 'items',
    component: () => import('@/views/ItemsView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/items/:id',
    name: 'item',
    component: () => import('@/views/ItemView.vue'),
    meta: { requiresAuth: true },
    props: true,
  },
  {
    path: '/items/add',
    name: 'add-item',
    component: () => import('@/views/AddItemView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/items/edit/:id',
    name: 'edit-item',
    component: () => import('@/views/EditItemView.vue'),
    meta: { requiresAuth: true },
    props: true,
  },
  {
    path: '/:catchAll(.*)',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { requiresAuth: false },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// In a navigation guard:
// router.beforeEach((to, from, next) => {
//   if (to.meta.requiresAuth && !isAuthenticated) {
//     next('/login');
//   } else {
//     next();
//   }
// });

export default router
