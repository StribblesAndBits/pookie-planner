import axios from 'axios';

const configuredBaseUrl = import.meta.env.VITE_API_BASE_URL as string | undefined;

const api = axios.create({
  baseURL: configuredBaseUrl?.trim() || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Attach token to every request if present
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
