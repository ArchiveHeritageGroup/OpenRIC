/**
 * OpenRiC Visual Regression — Screenshot Comparison
 *
 * Captures full-page screenshots of 20+ pages and compares them
 * against golden baselines stored in tests/e2e/visual/baselines/.
 *
 * First run:  npx playwright test visual/screenshots --update-snapshots
 * Later runs: npx playwright test visual/screenshots
 *
 * Thresholds are generous (maxDiffPixelRatio: 0.02) to allow for
 * dynamic content (timestamps, counts) while catching layout breaks.
 */
import { test, expect } from '@playwright/test';

interface ScreenshotPage {
  /** Name used for the baseline file */
  name: string;
  /** Relative URL */
  path: string;
  /** Optional selector to wait for before capturing */
  waitFor?: string;
}

const PAGES: ScreenshotPage[] = [
  // Public pages
  { name: 'home', path: '/' },
  { name: 'login', path: '/login' },
  { name: 'search-empty', path: '/search' },
  { name: 'search-results', path: '/search?query=test' },

  // Browse pages
  { name: 'browse-informationobject', path: '/informationobject/browse' },
  { name: 'browse-actor', path: '/actor/browse' },
  { name: 'browse-repository', path: '/repository/browse' },
  { name: 'browse-accession', path: '/accession/browse' },
  { name: 'browse-donor', path: '/donor/browse' },
  { name: 'browse-function', path: '/function/browse' },
  { name: 'browse-taxonomy', path: '/taxonomy/browse' },
  { name: 'browse-physicalobject', path: '/physicalobject/browse' },
  { name: 'browse-digitalobject', path: '/digitalobject/browse' },
  { name: 'browse-rightsholder', path: '/rightsholder/browse' },

  // Admin pages
  { name: 'admin-users', path: '/user/browse' },
  { name: 'admin-jobs', path: '/jobs/browse' },
  { name: 'admin-settings', path: '/settings' },
  { name: 'admin-reports', path: '/reports' },

  // Module landing pages
  { name: 'dedupe-dashboard', path: '/dedupe' },
  { name: 'condition-list', path: '/condition' },
  { name: 'gallery-index', path: '/gallery' },
  { name: 'heritage-index', path: '/heritage' },
  { name: 'doi-index', path: '/doi' },
];

const COMPARISON_OPTIONS = {
  maxDiffPixelRatio: 0.02,
  threshold: 0.2,
  animations: 'disabled' as const,
};

test.describe('Visual regression — screenshot comparison', () => {
  for (const pg of PAGES) {
    test(`screenshot: ${pg.name}`, async ({ page }) => {
      await page.goto(pg.path, { waitUntil: 'networkidle' });

      // Optional: wait for a specific selector if provided
      if (pg.waitFor) {
        await page.waitForSelector(pg.waitFor, { timeout: 10000 });
      }

      // Disable animations & transitions for deterministic captures
      await page.addStyleTag({
        content: `
          *, *::before, *::after {
            animation-duration: 0s !important;
            animation-delay: 0s !important;
            transition-duration: 0s !important;
            transition-delay: 0s !important;
          }
        `,
      });

      // Compare against baseline
      await expect(page).toHaveScreenshot(`${pg.name}.png`, {
        fullPage: true,
        ...COMPARISON_OPTIONS,
      });
    });
  }
});
