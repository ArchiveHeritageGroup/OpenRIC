import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30000,
  retries: 1,
  use: {
    baseURL: process.env.OPENRIC_URL || 'https://ric.theahg.co.za',
    screenshot: 'on',
    trace: 'on-first-retry',
  },
  projects: [
    { name: 'setup', testMatch: /auth\.setup\.ts/ },
    {
      name: 'authenticated',
      dependencies: ['setup'],
      use: { storageState: 'tests/e2e/.auth/user.json' },
    },
    { name: 'chromium', use: { browserName: 'chromium' } },
  ],
});
