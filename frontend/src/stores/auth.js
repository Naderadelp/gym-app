import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/index.js';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'));
    const token = ref(localStorage.getItem('token') || null);

    function setAuth(userData, tokenValue) {
        user.value = userData;
        token.value = tokenValue;
        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('token', tokenValue);
    }

    function clearAuth() {
        user.value = null;
        token.value = null;
        localStorage.removeItem('user');
        localStorage.removeItem('token');
    }

    async function login(email, password) {
        const { data } = await api.post('/auth/login', { email, password });
        setAuth(data.data.user, data.data.token);
    }

    async function register(payload) {
        const { data } = await api.post('/auth/register', payload);
        setAuth(data.data.user, data.data.token);
    }

    async function logout() {
        await api.post('/auth/logout').catch(() => {});
        clearAuth();
    }

    const isLoggedIn = () => !!token.value;

    return { user, token, login, register, logout, isLoggedIn };
});
