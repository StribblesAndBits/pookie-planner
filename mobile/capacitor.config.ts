import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'io.ionic.starter',
  appName: 'mobile',
  webDir: 'dist',
  server: {
    hostname: 'app',
    androidScheme: 'http',
    cleartext: true,
  },
};

export default config;
