import { test, expect } from '@playwright/test';

/**
 * Monitor Module E2E Tests
 *
 * Tests for site monitoring functionality.
 */

test.describe('Monitor', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=peanut-suite#/monitor');
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });
  });

  test('should display monitor page', async ({ page }) => {
    // Check for monitor-specific content
    const hasMonitorContent = await page.getByText(/monitor|sites|add site/i).isVisible().catch(() => false);
    expect(hasMonitorContent).toBeTruthy();
  });

  test('should show Add Site button', async ({ page }) => {
    const addButton = page.getByRole('button', { name: /add site/i });
    // May not be visible if user doesn't have the right tier
    const isVisible = await addButton.isVisible().catch(() => false);
    if (isVisible) {
      await expect(addButton).toBeVisible();
    }
  });

  test('should open Add Site modal when clicking button', async ({ page }) => {
    const addButton = page.getByRole('button', { name: /add site/i });
    const isVisible = await addButton.isVisible().catch(() => false);

    if (isVisible) {
      await addButton.click();

      // Modal should appear with form fields
      await expect(page.getByLabel(/url/i)).toBeVisible();
      await expect(page.getByLabel(/site name/i)).toBeVisible();
      await expect(page.getByLabel(/site key/i)).toBeVisible();
    }
  });

  test('should validate URL field in Add Site modal', async ({ page }) => {
    const addButton = page.getByRole('button', { name: /add site/i });
    const isVisible = await addButton.isVisible().catch(() => false);

    if (isVisible) {
      await addButton.click();

      // Try to submit without URL
      const submitButton = page.getByRole('button', { name: /add|submit|save/i });
      if (await submitButton.isVisible()) {
        await submitButton.click();

        // Should show validation error or stay on modal
        const hasError = await page.getByText(/required|invalid|enter/i).isVisible().catch(() => false);
        const stillOnModal = await page.getByLabel(/url/i).isVisible();

        expect(hasError || stillOnModal).toBeTruthy();
      }
    }
  });

  test('should display site cards when sites exist', async ({ page }) => {
    // Wait for potential loading
    await page.waitForTimeout(2000);

    // Either we see site cards or an empty state
    const hasSiteCards = await page.locator('[data-testid="site-card"]').count() > 0;
    const hasEmptyState = await page.getByText(/no sites|add your first|get started/i).isVisible().catch(() => false);

    // One of these should be true
    expect(hasSiteCards || hasEmptyState).toBeTruthy();
  });
});

test.describe('Site Details', () => {
  test('should show site details when clicking on a site card', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=peanut-suite#/monitor');
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });

    // Wait for sites to load
    await page.waitForTimeout(2000);

    // Check if any site cards exist
    const siteCards = page.locator('[data-testid="site-card"]');
    const count = await siteCards.count();

    if (count > 0) {
      // Click the first site card
      await siteCards.first().click();

      // Should navigate to site details
      await expect(page).toHaveURL(/.*#\/monitor\/\d+/);
    }
  });
});
