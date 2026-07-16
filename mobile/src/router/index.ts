import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import DashboardPage from '../views/DashboardPage.vue';
import LoginPage from '../views/LoginPage.vue';
import RegisterPage from '../views/RegisterPage.vue';
import ForgotPasswordPage from '../views/ForgotPasswordPage.vue';
import ProfilePage from '../views/ProfilePage.vue';
import UtilitiesPage from '../views/UtilitiesPage.vue';
import JulesPage from '../views/JulesPage.vue';
import { useAuth } from '@/composables/useAuth';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPage
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterPage
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: ForgotPasswordPage
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: DashboardPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: ProfilePage,
    meta: { requiresAuth: true }
  },
  {
    path: '/utilities',
    name: 'Utilities',
    component: UtilitiesPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/jules',
    name: 'Jules',
    component: JulesPage,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

const APP_TITLE = 'Pookie Pls';
const PUBLIC_ONLY_PATHS = new Set(['/login', '/register', '/forgot-password']);

function formatRouteTitle(name?: string): string {
  if (!name) {
    return APP_TITLE;
  }

  const friendlyName = name
    .replace(/([a-z])([A-Z])/g, '$1 $2')
    .replace(/\s+/g, ' ')
    .trim();

  return `${friendlyName} - ${APP_TITLE}`;
}

router.afterEach((to) => {
  document.title = formatRouteTitle(typeof to.name === 'string' ? to.name : undefined);
});

router.beforeEach(async (to) => {
  const { isAuthenticated, user, fetchUser } = useAuth();

  if (to.meta.requiresAuth) {
    if (!isAuthenticated.value) return '/login';
    if (!user.value) {
      try {
        await fetchUser();
      } catch {
        return '/login';
      }
    }
    return true;
  }

  if (PUBLIC_ONLY_PATHS.has(to.path) && isAuthenticated.value) {
    if (!user.value) {
      try {
        await fetchUser();
      } catch {
        return true;
      }
    }
    return '/dashboard';
  }

  return true;
});

export default router
