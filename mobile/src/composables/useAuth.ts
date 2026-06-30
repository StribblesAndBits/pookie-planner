import { ref, computed } from 'vue';
import api from '@/services/api';

export interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  color_preference?: string | null;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

const user = ref<User | null>(null);
const token = ref<string | null>(localStorage.getItem('auth_token'));

export function useAuth() {
  const isAuthenticated = computed(() => !!token.value);

  function setSession(newToken: string, newUser: User) {
    token.value = newToken;
    user.value = newUser;
    localStorage.setItem('auth_token', newToken);
  }

  function clearSession() {
    token.value = null;
    user.value = null;
    localStorage.removeItem('auth_token');
  }

  async function register(payload: {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    password_confirmation: string;
    color_preference?: string;
  }) {
    const { data } = await api.post('/register', payload);
    setSession(data.token, data.user);
    return data;
  }

  async function login(payload: { email: string; password: string }) {
    const { data } = await api.post('/login', payload);
    setSession(data.token, data.user);
    return data;
  }

  async function logout() {
    await api.post('/logout');
    clearSession();
  }

  async function forgotPassword(email: string) {
    const { data } = await api.post('/forgot-password', { email });
    return data;
  }

  async function fetchUser() {
    const { data } = await api.get('/me');
    user.value = data;
    return data;
  }

  return {
    user,
    token,
    isAuthenticated,
    register,
    login,
    logout,
    forgotPassword,
    fetchUser,
  };
}

