import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    { path: '/login',    component: () => import('@/pages/Auth/Login.vue'),    meta: { guest: true } },
    { path: '/register', component: () => import('@/pages/Auth/Register.vue'), meta: { guest: true } },

    { path: '/',          redirect: '/dashboard' },
    { path: '/dashboard', component: () => import('@/pages/Dashboard.vue'),          meta: { auth: true } },
    { path: '/profile',   component: () => import('@/pages/Profile/Edit.vue'),       meta: { auth: true } },
    { path: '/exercises', component: () => import('@/pages/Exercises/Index.vue'),    meta: { auth: true } },
    { path: '/routines',              component: () => import('@/pages/Routines/Index.vue'),   meta: { auth: true } },
    { path: '/routines/create',       component: () => import('@/pages/Routines/Builder.vue'), meta: { auth: true } },
    { path: '/routines/:id/edit',     component: () => import('@/pages/Routines/Builder.vue'), meta: { auth: true } },
    { path: '/workouts/:id',          component: () => import('@/pages/Workouts/ActiveSession.vue'), meta: { auth: true } },
    { path: '/analytics',             component: () => import('@/pages/Analytics/Index.vue'),  meta: { auth: true } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const token = localStorage.getItem('token');
    if (to.meta.auth && !token) return '/login';
    if (to.meta.guest && token) return '/dashboard';
});

export default router;
