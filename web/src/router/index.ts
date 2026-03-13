import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/LoginPage.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/',
      redirect: '/documents',
    },
    {
      path: '/documents',
      name: 'documents',
      component: () => import('@/pages/DocumentsPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/documents/:id',
      name: 'document-detail',
      component: () => import('@/pages/DocumentDetailPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/chat/:sessionId',
      name: 'chat',
      component: () => import('@/pages/ChatPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/pages/NotFoundPage.vue'),
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && authStore.isAuthenticated) {
    return { name: 'documents' }
  }
})

export default router
