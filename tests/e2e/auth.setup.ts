/**
 * OpenRiC Playwright Auth Setup
 *
 * Logs in once and saves session state so all "authenticated" project tests
 * can reuse the cookie jar without hitting the login form every time.
 *
 * Environment variables:
 *   TEST_USER  - login username  (default: demo@openric.org)
 *   TEST_PASS  - login password  (default: demo)
 *   OPENRIC_URL - base URL       (default: https://ric.theahg.co.za)
 */
import { test as setup, expect } from '@playwright/test';
import path from 'path';

const authFile = path.join(__dirname, '.auth', 'user.json');

setup('authenticate', async ({ page }) => {
  const baseURL = process.env.OPENRIC_URL || 'https://ric.theahg.co.za';
  const user = process.env.TEST_USER || 'demo@openric.org';
  const pass = process.env.TEST_PASS || 'demo';

  // Navigate to login page
  await page.goto(`${baseURL}/login`);

  // Wait for the login form to be visible
  await page.waitForSelector('form', { timeout: 10000 });

  // Fill credentials — try common field selectors
  const emailField =
    page.locator('input[name="email"]').or(
    page.locator('input[name="username"]')).or(
    page.locator('input[type="email"]'));
  await emailField.first().fill(user);

  const passwordField = page.locator('input[name="password"]').or(
    page.locator('input[type="password"]'));
  await passwordField.first().fill(pass);

  // Submit the form
  const submitButton =
    page.locator('button[type="submit"]').or(
    page.locator('input[type="submit"]'));
  await submitButton.first().click();

  // Wait for redirect away from /login — indicates success
  await page.waitForURL((url) => !url.pathname.includes('/login'), {
    timeout: 15000,
  });

  // Verify we are authenticated — look for a logout link or user menu
  const logoutIndicator =
    page.locator('a[href*="logout"]').or(
    page.locator('.user-menu')).or(
    page.locator('#user-menu')).or(
    page.locator('[data-testid="user-menu"]'));
  await expect(logoutIndicator.first()).toBeVisible({ timeout: 5000 });

  // Persist storage state (cookies + localStorage) for dependent tests
  await page.context().storageState({ path: authFile });
});
