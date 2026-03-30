/**
 * OpenRiC Browse Page Parity Tests
 *
 * Verifies that every major browse/index page renders correctly with
 * the expected DOM structure: title text, heading count, minimum link
 * and button counts.  Each test also captures a full-page screenshot
 * for the visual-regression pipeline.
 */
import { test, expect, Page } from '@playwright/test';

/* ---------- expected page definitions ---------- */
interface BrowseExpectation {
  /** Descriptive label used in the test name */
  label: string;
  /** Relative URL (no leading slash) */
  path: string;
  /** Substring expected in <title> */
  titleContains: string;
  /** Minimum number of <h1>–<h6> elements */
  minHeadings: number;
  /** Minimum number of <a> elements */
  minLinks: number;
  /** Minimum number of <button> elements */
  minButtons: number;
}

const EXPECTED: BrowseExpectation[] = [
  {
    label: 'Information Objects (archival descriptions)',
    path: 'informationobject/browse',
    titleContains: 'Browse',
    minHeadings: 1,
    minLinks: 5,
    minButtons: 0,
  },
  {
    label: 'Actors (authority records)',
    path: 'actor/browse',
    titleContains: 'Browse',
    minHeadings: 1,
    minLinks: 5,
    minButtons: 0,
  },
  {
    label: 'Repositories (archival institutions)',
    path: 'repository/browse',
    titleContains: 'Browse',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Accessions',
    path: 'accession/browse',
    titleContains: 'Accession',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Donors',
    path: 'donor/browse',
    titleContains: 'Donor',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Functions',
    path: 'function/browse',
    titleContains: 'Function',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Terms / Subjects',
    path: 'taxonomy/browse',
    titleContains: 'Taxonomy',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Physical Objects (storage)',
    path: 'physicalobject/browse',
    titleContains: 'Physical',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Digital Objects',
    path: 'digitalobject/browse',
    titleContains: 'Digital',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Rights Holders',
    path: 'rightsholder/browse',
    titleContains: 'Rights',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Users',
    path: 'user/browse',
    titleContains: 'User',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Jobs',
    path: 'jobs/browse',
    titleContains: 'Job',
    minHeadings: 1,
    minLinks: 2,
    minButtons: 0,
  },
  {
    label: 'Search Results',
    path: 'search?query=test',
    titleContains: 'Search',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Reports',
    path: 'reports',
    titleContains: 'Report',
    minHeadings: 1,
    minLinks: 3,
    minButtons: 0,
  },
  {
    label: 'Settings',
    path: 'settings',
    titleContains: 'Setting',
    minHeadings: 1,
    minLinks: 5,
    minButtons: 0,
  },
];

/* ---------- helper ---------- */
async function countElements(page: Page, selector: string): Promise<number> {
  return page.locator(selector).count();
}

/* ---------- tests ---------- */
test.describe('Browse page parity', () => {
  for (const exp of EXPECTED) {
    test(`${exp.label} — ${exp.path}`, async ({ page }) => {
      // Navigate
      const response = await page.goto(exp.path, { waitUntil: 'domcontentloaded' });
      expect(response).not.toBeNull();
      expect(response!.status()).toBeLessThan(500);

      // Title check
      const title = await page.title();
      expect(title.toLowerCase()).toContain(exp.titleContains.toLowerCase());

      // Heading count
      const headings = await countElements(page, 'h1, h2, h3, h4, h5, h6');
      expect(headings).toBeGreaterThanOrEqual(exp.minHeadings);

      // Link count
      const links = await countElements(page, 'a[href]');
      expect(links).toBeGreaterThanOrEqual(exp.minLinks);

      // Button count
      const buttons = await countElements(page, 'button, input[type="submit"], input[type="button"]');
      expect(buttons).toBeGreaterThanOrEqual(exp.minButtons);

      // Full-page screenshot (saved to test-results by Playwright config)
      const slug = exp.path.replace(/[^a-zA-Z0-9]+/g, '-').replace(/-+$/, '');
      await page.screenshot({
        path: `tests/e2e/screenshots/browse-${slug}.png`,
        fullPage: true,
      });
    });
  }
});
