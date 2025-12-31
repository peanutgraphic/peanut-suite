import { chromium, FullConfig } from '@playwright/test';

/**
 * Global setup for Playwright tests
 *
 * This runs once before all tests and can be used to:
 * - Authenticate users and save state
 * - Set up test data
 * - Configure the test environment
 */
async function globalSetup(config: FullConfig) {
  const { baseURL, storageState } = config.projects[0].use;

  // Skip authentication if no base URL is configured
  if (!baseURL || baseURL.includes('localhost')) {
    console.log('Skipping authentication setup - no remote base URL configured');
    return;
  }

  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Navigate to WordPress login
    await page.goto(`${baseURL}/wp-login.php`);

    // Check if we're on the login page
    const loginForm = await page.$('#loginform');

    if (loginForm) {
      // Perform login
      await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
      await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'admin');
      await page.click('#wp-submit');

      // Wait for navigation to dashboard
      await page.waitForURL('**/wp-admin/**', { timeout: 10000 });

      console.log('Successfully logged in to WordPress');
    }

    // Save authentication state
    if (storageState) {
      await context.storageState({ path: storageState as string });
    }
  } catch (error) {
    console.error('Global setup failed:', error);
    // Don't throw - allow tests to run without pre-authentication
  } finally {
    await browser.close();
  }
}

export default globalSetup;
