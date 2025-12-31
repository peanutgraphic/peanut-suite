import { test, expect } from '@playwright/test';

/**
 * Dashboard E2E Tests
 *
 * These tests verify the main dashboard functionality.
 */

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the plugin's admin page
    await page.goto('/wp-admin/admin.php?page=peanut-suite');
  });

  test('should load the dashboard page', async ({ page }) => {
    // Check that the main app container loads
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });
  });

  test('should display navigation sidebar', async ({ page }) => {
    // Wait for app to load
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });

    // Check for navigation items
    await expect(page.getByRole('link', { name: /dashboard/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /clients/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /sites/i })).toBeVisible();
  });

  test('should show welcome message or stats', async ({ page }) => {
    // Wait for dashboard content to load
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });

    // Dashboard should have some kind of overview content
    const hasStats = await page.getByText(/total|active|clients|sites/i).isVisible().catch(() => false);
    const hasWelcome = await page.getByText(/welcome|getting started/i).isVisible().catch(() => false);

    expect(hasStats || hasWelcome).toBeTruthy();
  });
});

test.describe('Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=peanut-suite');
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });
  });

  test('should navigate to Clients page', async ({ page }) => {
    await page.getByRole('link', { name: /clients/i }).click();
    await expect(page).toHaveURL(/.*#\/clients/);
  });

  test('should navigate to Settings page', async ({ page }) => {
    await page.getByRole('link', { name: /settings/i }).click();
    await expect(page).toHaveURL(/.*#\/settings/);
  });

  test('should navigate to Monitor page', async ({ page }) => {
    const monitorLink = page.getByRole('link', { name: /monitor/i });
    if (await monitorLink.isVisible().catch(() => false)) {
      await monitorLink.click();
      await expect(page).toHaveURL(/.*#\/monitor/);
    }
  });
});
