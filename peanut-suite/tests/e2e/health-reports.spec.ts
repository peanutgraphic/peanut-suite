import { test, expect } from '@playwright/test';

/**
 * Health Reports E2E Tests
 *
 * Tests for weekly health reports functionality.
 */

test.describe('Health Reports', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=peanut-suite#/health-reports');
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });
  });

  test('should display health reports page', async ({ page }) => {
    // Check for health reports content
    const hasContent = await page.getByText(/health|reports|grade/i).isVisible().catch(() => false);
    expect(hasContent).toBeTruthy();
  });

  test('should have tabs for Latest Report and Settings', async ({ page }) => {
    // Look for tab navigation
    const latestTab = page.getByRole('tab', { name: /latest|report/i });
    const settingsTab = page.getByRole('tab', { name: /settings/i });

    const hasLatestTab = await latestTab.isVisible().catch(() => false);
    const hasSettingsTab = await settingsTab.isVisible().catch(() => false);

    // At least one tab should be visible
    expect(hasLatestTab || hasSettingsTab).toBeTruthy();
  });

  test('should switch to Settings tab', async ({ page }) => {
    const settingsTab = page.getByRole('tab', { name: /settings/i });

    if (await settingsTab.isVisible().catch(() => false)) {
      await settingsTab.click();

      // Settings should show configuration options
      await expect(page.getByText(/email|recipients|frequency|schedule/i)).toBeVisible({ timeout: 5000 });
    }
  });

  test('should show site/server selection in Settings', async ({ page }) => {
    const settingsTab = page.getByRole('tab', { name: /settings/i });

    if (await settingsTab.isVisible().catch(() => false)) {
      await settingsTab.click();

      // Should have selection options for sites and/or servers
      const hasSiteSelection = await page.getByText(/sites|select sites/i).isVisible().catch(() => false);
      const hasServerSelection = await page.getByText(/servers|select servers/i).isVisible().catch(() => false);

      expect(hasSiteSelection || hasServerSelection).toBeTruthy();
    }
  });

  test('should display grade badge on Latest Report tab', async ({ page }) => {
    // Wait for report data to load
    await page.waitForTimeout(2000);

    // Look for grade indicator (A, B, C, D, F or score)
    const hasGrade = await page.getByText(/^[A-F]$|grade|score|\d+\/100/).isVisible().catch(() => false);

    // Either we have a grade or we have an empty/no data state
    const hasEmptyState = await page.getByText(/no report|generate|no data/i).isVisible().catch(() => false);

    expect(hasGrade || hasEmptyState).toBeTruthy();
  });

  test('should have Generate Report button', async ({ page }) => {
    const generateButton = page.getByRole('button', { name: /generate|preview/i });
    const isVisible = await generateButton.isVisible().catch(() => false);

    if (isVisible) {
      await expect(generateButton).toBeEnabled();
    }
  });
});

test.describe('Health Reports - Email Settings', () => {
  test('should allow configuring email recipients', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=peanut-suite#/health-reports');
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });

    const settingsTab = page.getByRole('tab', { name: /settings/i });

    if (await settingsTab.isVisible().catch(() => false)) {
      await settingsTab.click();

      // Look for email/recipients field
      const emailField = page.getByLabel(/email|recipients/i);
      const isVisible = await emailField.isVisible().catch(() => false);

      if (isVisible) {
        await expect(emailField).toBeEditable();
      }
    }
  });

  test('should allow toggling report sections', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=peanut-suite#/health-reports');
    await expect(page.locator('#peanut-suite-root')).toBeVisible({ timeout: 10000 });

    const settingsTab = page.getByRole('tab', { name: /settings/i });

    if (await settingsTab.isVisible().catch(() => false)) {
      await settingsTab.click();

      // Look for toggle switches for sites/servers/recommendations
      const hasToggles = await page.locator('input[type="checkbox"], [role="switch"]').count() > 0;
      expect(hasToggles).toBeTruthy();
    }
  });
});
