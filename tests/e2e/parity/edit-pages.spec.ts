/**
 * OpenRiC Edit Page Parity Tests
 *
 * Verifies that every major edit/create form renders with the expected
 * number of form fields.  Uses the "authenticated" project so that
 * session state is pre-loaded from auth.setup.ts.
 *
 * NOTE: These tests navigate to "new" (create) forms to avoid needing
 * existing record IDs.  The field counts should match the reference
 * Heratio forms once full parity is reached.
 */
import { test, expect, Page } from '@playwright/test';

/* ---------- expected page definitions ---------- */
interface EditExpectation {
  /** Descriptive label */
  label: string;
  /** Relative URL for the create/edit form */
  path: string;
  /** Minimum number of <input> elements (includes hidden) */
  minInputs: number;
  /** Minimum number of <select> elements */
  minSelects: number;
  /** Minimum number of <textarea> elements */
  minTextareas: number;
  /** Minimum total form fields (inputs + selects + textareas) */
  minTotalFields: number;
  /** Whether at least one <form> tag must be present */
  requireForm: boolean;
}

const EXPECTED: EditExpectation[] = [
  {
    label: 'Create Information Object',
    path: 'informationobject/add',
    minInputs: 4,
    minSelects: 2,
    minTextareas: 1,
    minTotalFields: 7,
    requireForm: true,
  },
  {
    label: 'Create Actor (authority record)',
    path: 'actor/add',
    minInputs: 3,
    minSelects: 1,
    minTextareas: 1,
    minTotalFields: 5,
    requireForm: true,
  },
  {
    label: 'Create Repository',
    path: 'repository/add',
    minInputs: 3,
    minSelects: 1,
    minTextareas: 1,
    minTotalFields: 5,
    requireForm: true,
  },
  {
    label: 'Create Accession',
    path: 'accession/add',
    minInputs: 4,
    minSelects: 1,
    minTextareas: 1,
    minTotalFields: 6,
    requireForm: true,
  },
  {
    label: 'Create Donor',
    path: 'donor/add',
    minInputs: 2,
    minSelects: 0,
    minTextareas: 0,
    minTotalFields: 2,
    requireForm: true,
  },
  {
    label: 'Create Function',
    path: 'function/add',
    minInputs: 2,
    minSelects: 1,
    minTextareas: 1,
    minTotalFields: 4,
    requireForm: true,
  },
  {
    label: 'Create Term',
    path: 'term/add',
    minInputs: 2,
    minSelects: 1,
    minTextareas: 0,
    minTotalFields: 3,
    requireForm: true,
  },
  {
    label: 'Create Physical Object',
    path: 'physicalobject/add',
    minInputs: 2,
    minSelects: 1,
    minTextareas: 0,
    minTotalFields: 3,
    requireForm: true,
  },
  {
    label: 'Create Rights Holder',
    path: 'rightsholder/add',
    minInputs: 2,
    minSelects: 0,
    minTextareas: 0,
    minTotalFields: 2,
    requireForm: true,
  },
  {
    label: 'Create User',
    path: 'user/add',
    minInputs: 3,
    minSelects: 1,
    minTextareas: 0,
    minTotalFields: 4,
    requireForm: true,
  },
];

/* ---------- helper ---------- */
async function fieldCounts(page: Page) {
  const inputs = await page.locator('input').count();
  const selects = await page.locator('select').count();
  const textareas = await page.locator('textarea').count();
  const forms = await page.locator('form').count();
  return { inputs, selects, textareas, forms, total: inputs + selects + textareas };
}

/* ---------- tests ---------- */
test.describe('Edit/Create page field parity', () => {
  for (const exp of EXPECTED) {
    test(`${exp.label} — ${exp.path}`, async ({ page }) => {
      const response = await page.goto(exp.path, { waitUntil: 'domcontentloaded' });
      expect(response).not.toBeNull();

      // A 302 to /login means auth failed — skip gracefully
      const finalUrl = page.url();
      if (finalUrl.includes('/login')) {
        test.skip(true, 'Redirected to login — run with the "authenticated" project');
        return;
      }

      expect(response!.status()).toBeLessThan(500);

      const counts = await fieldCounts(page);

      // Form tag presence
      if (exp.requireForm) {
        expect(counts.forms).toBeGreaterThanOrEqual(1);
      }

      // Individual field-type minimums
      expect(counts.inputs).toBeGreaterThanOrEqual(exp.minInputs);
      expect(counts.selects).toBeGreaterThanOrEqual(exp.minSelects);
      expect(counts.textareas).toBeGreaterThanOrEqual(exp.minTextareas);

      // Aggregate field count
      expect(counts.total).toBeGreaterThanOrEqual(exp.minTotalFields);

      // Screenshot for visual review
      const slug = exp.path.replace(/[^a-zA-Z0-9]+/g, '-').replace(/-+$/, '');
      await page.screenshot({
        path: `tests/e2e/screenshots/edit-${slug}.png`,
        fullPage: true,
      });
    });
  }
});
