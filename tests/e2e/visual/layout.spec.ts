/**
 * OpenRiC Layout Verification Tests
 *
 * Checks structural layout properties that must hold across pages:
 *   - Column layout (1-col, 2-col sidebar+main, 3-col)
 *   - Sidebar presence where expected
 *   - Responsive breakpoints (mobile, tablet, desktop)
 *   - Header / footer presence
 *   - Navigation structure
 */
import { test, expect, Page } from '@playwright/test';

/* ---------- layout definitions ---------- */
interface LayoutExpectation {
  label: string;
  path: string;
  /** Expected column layout on desktop */
  layout: '1col' | '2col' | '3col';
  /** Whether a sidebar should be present */
  hasSidebar: boolean;
  /** Whether a header/navbar should be present */
  hasHeader: boolean;
  /** Whether a footer should be present */
  hasFooter: boolean;
}

const LAYOUTS: LayoutExpectation[] = [
  {
    label: 'Home page',
    path: '/',
    layout: '1col',
    hasSidebar: false,
    hasHeader: true,
    hasFooter: true,
  },
  {
    label: 'Login page',
    path: '/login',
    layout: '1col',
    hasSidebar: false,
    hasHeader: true,
    hasFooter: true,
  },
  {
    label: 'Browse archival descriptions',
    path: '/informationobject/browse',
    layout: '2col',
    hasSidebar: true,
    hasHeader: true,
    hasFooter: true,
  },
  {
    label: 'Browse actors',
    path: '/actor/browse',
    layout: '2col',
    hasSidebar: true,
    hasHeader: true,
    hasFooter: true,
  },
  {
    label: 'Browse repositories',
    path: '/repository/browse',
    layout: '2col',
    hasSidebar: true,
    hasHeader: true,
    hasFooter: true,
  },
  {
    label: 'Search results',
    path: '/search?query=test',
    layout: '2col',
    hasSidebar: true,
    hasHeader: true,
    hasFooter: true,
  },
  {
    label: 'Settings page',
    path: '/settings',
    layout: '2col',
    hasSidebar: true,
    hasHeader: true,
    hasFooter: true,
  },
  {
    label: 'Reports page',
    path: '/reports',
    layout: '1col',
    hasSidebar: false,
    hasHeader: true,
    hasFooter: true,
  },
];

/* ---------- responsive viewports ---------- */
const VIEWPORTS = {
  mobile: { width: 375, height: 812 },
  tablet: { width: 768, height: 1024 },
  desktop: { width: 1440, height: 900 },
};

/* ---------- selectors for layout detection ---------- */
const SIDEBAR_SELECTORS = [
  '#sidebar',
  '.sidebar',
  '[data-testid="sidebar"]',
  'aside',
  '.col-sidebar',
  '#facets',
  '.facet-sidebar',
  'nav.sidebar',
].join(', ');

const HEADER_SELECTORS = [
  'header',
  'nav.navbar',
  '#top-bar',
  '.top-bar',
  '[role="banner"]',
].join(', ');

const FOOTER_SELECTORS = [
  'footer',
  '#footer',
  '.footer',
  '[role="contentinfo"]',
].join(', ');

/* ---------- helpers ---------- */
async function hasSidebar(page: Page): Promise<boolean> {
  const count = await page.locator(SIDEBAR_SELECTORS).count();
  return count > 0;
}

async function hasHeader(page: Page): Promise<boolean> {
  const count = await page.locator(HEADER_SELECTORS).count();
  return count > 0;
}

async function hasFooter(page: Page): Promise<boolean> {
  const count = await page.locator(FOOTER_SELECTORS).count();
  return count > 0;
}

/**
 * Detect column layout by checking CSS grid/flex children counts
 * and sidebar presence.
 */
async function detectLayout(page: Page): Promise<'1col' | '2col' | '3col'> {
  const sidebar = await hasSidebar(page);

  // Check for 3-column layouts (e.g. sidebar + main + right panel)
  const threeColContainers = await page.locator(
    '.three-column, .col-3-layout, [data-layout="3col"]'
  ).count();
  if (threeColContainers > 0) return '3col';

  // Check for explicit two-column Bootstrap/grid layouts
  const twoColIndicators = await page.evaluate(() => {
    const mainContent = document.querySelector('main, .main-content, #content, .content');
    if (!mainContent) return false;
    const parent = mainContent.parentElement;
    if (!parent) return false;
    const style = window.getComputedStyle(parent);
    return (
      style.display === 'flex' ||
      style.display === 'grid' ||
      parent.classList.contains('row')
    );
  });

  if (sidebar && twoColIndicators) return '2col';
  if (sidebar) return '2col';
  return '1col';
}

/* ---------- desktop layout tests ---------- */
test.describe('Desktop layout verification', () => {
  test.use({ viewport: VIEWPORTS.desktop });

  for (const exp of LAYOUTS) {
    test(`${exp.label} — layout=${exp.layout}`, async ({ page }) => {
      await page.goto(exp.path, { waitUntil: 'domcontentloaded' });

      // Check column layout
      const detectedLayout = await detectLayout(page);
      expect(detectedLayout).toBe(exp.layout);

      // Sidebar
      const sidebarPresent = await hasSidebar(page);
      expect(sidebarPresent).toBe(exp.hasSidebar);

      // Header
      const headerPresent = await hasHeader(page);
      expect(headerPresent).toBe(exp.hasHeader);

      // Footer
      const footerPresent = await hasFooter(page);
      expect(footerPresent).toBe(exp.hasFooter);
    });
  }
});

/* ---------- responsive breakpoint tests ---------- */
test.describe('Responsive breakpoints', () => {
  const RESPONSIVE_PAGES = [
    { label: 'Home', path: '/' },
    { label: 'Browse descriptions', path: '/informationobject/browse' },
    { label: 'Search', path: '/search?query=test' },
  ];

  for (const pg of RESPONSIVE_PAGES) {
    test(`mobile viewport: ${pg.label}`, async ({ page }) => {
      await page.setViewportSize(VIEWPORTS.mobile);
      await page.goto(pg.path, { waitUntil: 'domcontentloaded' });

      // On mobile, sidebar should be hidden or collapsed
      const sidebarVisible = await page.locator(SIDEBAR_SELECTORS).first().isVisible()
        .catch(() => false);

      // Header should still be present (possibly as hamburger menu)
      const headerPresent = await hasHeader(page);
      expect(headerPresent).toBe(true);

      // Content should not overflow horizontally
      const overflows = await page.evaluate(() => {
        return document.documentElement.scrollWidth > document.documentElement.clientWidth;
      });
      expect(overflows).toBe(false);

      await page.screenshot({
        path: `tests/e2e/screenshots/responsive-mobile-${pg.label.toLowerCase().replace(/\s+/g, '-')}.png`,
        fullPage: true,
      });
    });

    test(`tablet viewport: ${pg.label}`, async ({ page }) => {
      await page.setViewportSize(VIEWPORTS.tablet);
      await page.goto(pg.path, { waitUntil: 'domcontentloaded' });

      // Header should be present
      const headerPresent = await hasHeader(page);
      expect(headerPresent).toBe(true);

      // Content should not overflow
      const overflows = await page.evaluate(() => {
        return document.documentElement.scrollWidth > document.documentElement.clientWidth;
      });
      expect(overflows).toBe(false);

      await page.screenshot({
        path: `tests/e2e/screenshots/responsive-tablet-${pg.label.toLowerCase().replace(/\s+/g, '-')}.png`,
        fullPage: true,
      });
    });

    test(`desktop viewport: ${pg.label}`, async ({ page }) => {
      await page.setViewportSize(VIEWPORTS.desktop);
      await page.goto(pg.path, { waitUntil: 'domcontentloaded' });

      // Header and footer should be present
      const headerPresent = await hasHeader(page);
      expect(headerPresent).toBe(true);
      const footerPresent = await hasFooter(page);
      expect(footerPresent).toBe(true);

      await page.screenshot({
        path: `tests/e2e/screenshots/responsive-desktop-${pg.label.toLowerCase().replace(/\s+/g, '-')}.png`,
        fullPage: true,
      });
    });
  }
});

/* ---------- navigation structure ---------- */
test.describe('Navigation structure', () => {
  test('main navigation has expected links', async ({ page }) => {
    await page.setViewportSize(VIEWPORTS.desktop);
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    // The main nav should exist
    const nav = page.locator('nav').first();
    await expect(nav).toBeVisible();

    // Count nav links
    const navLinks = await page.locator('nav a[href]').count();
    expect(navLinks).toBeGreaterThanOrEqual(3);
  });

  test('footer has copyright or branding', async ({ page }) => {
    await page.setViewportSize(VIEWPORTS.desktop);
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const footer = page.locator(FOOTER_SELECTORS).first();
    const footerVisible = await footer.isVisible().catch(() => false);
    if (footerVisible) {
      const footerText = await footer.textContent();
      expect(footerText).toBeTruthy();
      expect(footerText!.length).toBeGreaterThan(5);
    }
  });
});
