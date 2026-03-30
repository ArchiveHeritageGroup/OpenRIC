# Audit Methodology: Heratio -> OpenRiC Parity

**Purpose:** Measure and close the gap between Heratio (Laravel 12 reference) and
OpenRiC (Laravel 12 + RiC-O target).  Every claim of "done" must be backed by
auditable evidence from at least two of the four testing layers.

**Heratio** = the reference implementation at `https://heratio.theahg.co.za`
**OpenRiC** = the target implementation at `https://ric.theahg.co.za`

---

## 4-Layer Testing Pipeline

```
Layer 1: PHP Audit Scripts    (static analysis — routes, views, controls)
Layer 2: HTTP Parity          (live server comparison — curl-based)
Layer 3: Playwright E2E       (browser-level — DOM, layout, screenshots)
Layer 4: AI-Assisted QA       (Claude — semantic review, gap analysis)
```

Each layer catches different classes of defects:

| Layer | Catches | Misses |
|-------|---------|--------|
| 1 — PHP Scripts | Missing routes, views, controllers, fields | Runtime JS errors, visual bugs |
| 2 — HTTP Parity | 404s, 500s, redirect loops, missing controls | JS-rendered content, UX flow |
| 3 — Playwright E2E | Layout breaks, missing elements, visual regressions | Semantic correctness |
| 4 — AI-Assisted QA | Logic gaps, naming errors, incomplete flows | Nothing (human-in-the-loop) |

---

## Part 1: PHP Audit Scripts

### Overview — 9 Parity Questions

Every package audit answers these questions:

| # | Question | Script |
|---|----------|--------|
| 1 | Are all Heratio routes present in OpenRiC? | `audit-routes.php` |
| 2 | Do all routes return HTTP 200? | `audit-routes.php` |
| 3 | Does each page have the correct number of controls (links, buttons, inputs)? | `audit-routes.php`, `audit-controls.php` |
| 4 | Are all Blade views present? | `audit-views.php` |
| 5 | Are all controller methods implemented? | `audit-controllers.php` |
| 6 | Are all service methods implemented? | `audit-services.php` |
| 7 | Do forms have the correct field counts? | `audit-forms.php` |
| 8 | Are all config keys present? | `audit-config.php` |
| 9 | Are all migrations present? | `audit-migrations.php` |

### Prerequisites

```bash
# Required packages
sudo apt install php-cli php-curl php-dom php-json jq

# Verify PHP
php -v   # PHP 8.4+

# Verify Laravel routes are loadable
cd /usr/share/nginx/OpenRiC
php artisan route:list --json | jq length
```

### Script Reference

All scripts live in `/usr/share/nginx/OpenRiC/bin/` and are run from the
project root.

#### 1. `audit-routes.php` — Route & Control Audit

Tests every GET route: HTTP status, link count, button count, form count.
Writes a JSON report to `storage/logs/`.

```bash
# Basic usage (unauthenticated)
php bin/audit-routes.php

# With authentication cookie
php bin/audit-routes.php --base-url=https://ric.theahg.co.za --cookie="laravel_session=abc123"

# Against Heratio for comparison
php bin/audit-routes.php --base-url=https://heratio.theahg.co.za --cookie="laravel_session=xyz789"
```

**Output:** `storage/logs/audit-report-YYYY-MM-DD-HHmmss.json`

Each entry contains:
```json
{
  "status": "PASS",
  "method": "GET",
  "uri": "informationobject/browse",
  "name": "informationobject.browse",
  "http": 200,
  "controls": {
    "buttons": 3,
    "links": 42,
    "inputs": 5,
    "selects": 2,
    "textareas": 0,
    "forms": 1,
    "tables": 1
  }
}
```

#### 2. `audit-views.php` — View Completeness

Compares Blade view files between Heratio and OpenRiC packages.

```bash
php bin/audit-views.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

**Output:** List of missing views per package, percentage complete.

#### 3. `audit-controllers.php` — Controller Method Coverage

Parses controller classes and compares method signatures.

```bash
php bin/audit-controllers.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

**Output:** Missing methods per controller, method signature mismatches.

#### 4. `audit-services.php` — Service Layer Coverage

Compares service class methods between implementations.

```bash
php bin/audit-services.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

**Output:** Missing service methods, incomplete interface implementations.

#### 5. `audit-controls.php` — Page Control Comparison

Fetches both Heratio and OpenRiC versions of each page and compares DOM
element counts side-by-side.

```bash
php bin/audit-controls.php \
  --heratio-url=https://heratio.theahg.co.za \
  --openric-url=https://ric.theahg.co.za \
  --heratio-cookie="laravel_session=abc" \
  --openric-cookie="laravel_session=xyz"
```

**Output:** Per-page comparison table with delta columns.

#### 6. `audit-forms.php` — Form Field Parity

Compares input/select/textarea counts on every form page.

```bash
php bin/audit-forms.php \
  --heratio-url=https://heratio.theahg.co.za \
  --openric-url=https://ric.theahg.co.za
```

**Output:** Field count differences per form, missing fields.

#### 7. `audit-config.php` — Configuration Key Parity

Compares config files between Heratio and OpenRiC packages.

```bash
php bin/audit-config.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

**Output:** Missing config keys per package.

#### 8. `audit-migrations.php` — Migration Coverage

Lists migration files in both systems and identifies gaps.

```bash
php bin/audit-migrations.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

**Output:** Missing migrations, table coverage percentage.

#### 9. `audit-css.php` — CSS Class Parity

Compares CSS classes used in Blade views to ensure OpenRiC uses
`openric-btn-*` and `var(--openric-primary)` instead of the old
`atom-btn-*` and `var(--ahg-primary)` patterns.

```bash
php bin/audit-css.php --path=/usr/share/nginx/OpenRiC/packages
```

**Output:** List of files still using old class names.

#### 10. `audit-permissions.php` — ACL Coverage

Checks that all Heratio ACL permission strings exist in OpenRiC.

```bash
php bin/audit-permissions.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

**Output:** Missing permission strings, ACL coverage percentage.

#### 11. `audit-i18n.php` — Translation Key Coverage

Compares translation keys between the two systems.

```bash
php bin/audit-i18n.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

**Output:** Missing translation keys per locale.

#### 12. `audit-api.php` — API Endpoint Parity

Compares API route definitions and response schemas.

```bash
php bin/audit-api.php \
  --heratio-url=https://heratio.theahg.co.za/api/v2 \
  --openric-url=https://ric.theahg.co.za/api/v2
```

**Output:** Missing endpoints, response schema differences.

### Auto-Fix Scripts

| Script | Purpose | Usage |
|--------|---------|-------|
| `fix-css-classes.php` | Replace `atom-btn-*` with `openric-btn-*` | `php bin/fix-css-classes.php --path=packages/` |
| `fix-css-vars.php` | Replace `var(--ahg-primary)` with `var(--openric-primary)` | `php bin/fix-css-vars.php --path=packages/` |
| `fix-route-names.php` | Rename route names from Heratio convention to OpenRiC | `php bin/fix-route-names.php --path=packages/` |
| `fix-namespace.php` | Replace Heratio namespaces with OpenRiC namespaces | `php bin/fix-namespace.php --path=packages/` |

### The 12 Rules

Every audit and every code change must comply with these rules:

| # | Rule | Example |
|---|------|---------|
| 1 | CSS button classes use `openric-btn-*` | `openric-btn-primary`, not `atom-btn-*` |
| 2 | CSS variables use `var(--openric-primary)` | `var(--openric-primary)`, not `var(--ahg-primary)` |
| 3 | Route names use `openric.` prefix where needed | `openric.accession.browse` |
| 4 | Namespaces use `OpenRiC\\` | `OpenRiC\Core\Models\QubitInformationObject` |
| 5 | Config keys use `openric-` prefix | `openric-cart.checkout_enabled` |
| 6 | Views use `openric-` package prefix | `openric-accession::browse` |
| 7 | Service providers extend the correct base | `Illuminate\Support\ServiceProvider` |
| 8 | All Blade templates extend `openric-theme::layout` | Not `ahg-theme::layout` |
| 9 | All DB tables use the existing Qubit schema names | `information_object`, `actor`, etc. |
| 10 | All models use the `App\Models` or package namespace | Not Heratio namespaces |
| 11 | JavaScript assets reference `/vendor/openric/` | Not `/vendor/ahg/` or `/vendor/atom/` |
| 12 | Test classes use `OpenRiC\Tests\` namespace | Not `Heratio\Tests\` |

---

## Step-by-Step Audit Process

### Step 1: Generate Route Baseline

```bash
cd /usr/share/nginx/OpenRiC

# OpenRiC routes
php artisan route:list --json > storage/logs/openric-routes.json

# Heratio routes (if accessible)
ssh heratio "cd /usr/share/nginx/Heratio && php artisan route:list --json" > storage/logs/heratio-routes.json

# Compare
jq -r '.[].uri' storage/logs/heratio-routes.json | sort > /tmp/heratio-uris.txt
jq -r '.[].uri' storage/logs/openric-routes.json | sort > /tmp/openric-uris.txt
diff /tmp/heratio-uris.txt /tmp/openric-uris.txt
```

### Step 2: Run Route Audit

```bash
php bin/audit-routes.php --base-url=https://ric.theahg.co.za --cookie="$SESSION_COOKIE"
```

### Step 3: Run View Audit

```bash
php bin/audit-views.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

### Step 4: Run Controller Audit

```bash
php bin/audit-controllers.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

### Step 5: Run Service Audit

```bash
php bin/audit-services.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
```

### Step 6: Run Control Comparison

```bash
php bin/audit-controls.php \
  --heratio-url=https://heratio.theahg.co.za \
  --openric-url=https://ric.theahg.co.za \
  --heratio-cookie="$HERATIO_COOKIE" \
  --openric-cookie="$OPENRIC_COOKIE"
```

### Step 7: Run CSS Audit

```bash
php bin/audit-css.php --path=/usr/share/nginx/OpenRiC/packages
```

### Step 8: Run Playwright E2E Suite

```bash
cd /usr/share/nginx/OpenRiC
OPENRIC_URL=https://ric.theahg.co.za TEST_USER=admin@openric.org TEST_PASS=secret \
  npx playwright test
```

### Step 9: AI-Assisted Review

Feed audit reports to Claude for gap analysis:

```
Given:
  - storage/logs/audit-report-*.json
  - Playwright test results in test-results/
  - The 12 Rules above

Identify:
  1. Routes present in Heratio but missing in OpenRiC
  2. Pages with significantly fewer controls than Heratio
  3. Forms with missing fields
  4. Violations of the 12 Rules
  5. Priority ranking for fixes
```

---

## 3-Phase Execution Plan

### Phase A: Static Analysis (offline, no server needed)

| Task | Script | Time |
|------|--------|------|
| Compare route definitions | `audit-routes.php` (route:list only) | 30s |
| Compare view files | `audit-views.php` | 1m |
| Compare controller methods | `audit-controllers.php` | 1m |
| Compare service methods | `audit-services.php` | 1m |
| Check CSS classes | `audit-css.php` | 30s |
| Compare config keys | `audit-config.php` | 30s |
| Compare migrations | `audit-migrations.php` | 30s |

### Phase B: Live Server Comparison (requires running servers)

| Task | Script | Time |
|------|--------|------|
| HTTP status of all routes | `audit-routes.php` (with curl) | 5m |
| Control count comparison | `audit-controls.php` | 10m |
| Form field comparison | `audit-forms.php` | 5m |
| API endpoint comparison | `audit-api.php` | 3m |

### Phase C: Browser-Level Testing (requires Playwright)

| Task | Test file | Time |
|------|-----------|------|
| Browse page parity | `tests/e2e/parity/browse-pages.spec.ts` | 2m |
| Edit form parity | `tests/e2e/parity/edit-pages.spec.ts` | 2m |
| Visual regression | `tests/e2e/visual/screenshots.spec.ts` | 5m |
| Layout verification | `tests/e2e/visual/layout.spec.ts` | 3m |

---

## Output Files Summary

| File | Source | Contents |
|------|--------|----------|
| `storage/logs/audit-report-*.json` | `audit-routes.php` | Per-route HTTP status and control counts |
| `storage/logs/openric-routes.json` | `artisan route:list` | All registered routes |
| `storage/logs/view-audit-*.txt` | `audit-views.php` | Missing views per package |
| `storage/logs/controller-audit-*.txt` | `audit-controllers.php` | Missing controller methods |
| `storage/logs/service-audit-*.txt` | `audit-services.php` | Missing service methods |
| `storage/logs/css-audit-*.txt` | `audit-css.php` | Files with old CSS patterns |
| `tests/e2e/screenshots/*.png` | Playwright | Full-page captures per test |
| `test-results/` | Playwright | HTML report, traces, diffs |

---

## Replicating on Another Instance

To run the full audit on a fresh OpenRiC deployment:

```bash
# 1. Clone
git clone <repo-url> /usr/share/nginx/OpenRiC
cd /usr/share/nginx/OpenRiC

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies (for Playwright)
npm install
npx playwright install --with-deps chromium

# 4. Set environment
cp .env.example .env
php artisan key:generate

# 5. Configure audit targets
export OPENRIC_URL=https://your-openric-instance.example.com
export HERATIO_URL=https://your-heratio-instance.example.com
export TEST_USER=admin@openric.org
export TEST_PASS=your-password

# 6. Run Phase A (static)
php bin/audit-views.php --heratio=/path/to/Heratio --openric=/usr/share/nginx/OpenRiC
php bin/audit-controllers.php --heratio=/path/to/Heratio --openric=/usr/share/nginx/OpenRiC
php bin/audit-css.php --path=/usr/share/nginx/OpenRiC/packages

# 7. Run Phase B (live)
php bin/audit-routes.php --base-url=$OPENRIC_URL --cookie="$COOKIE"

# 8. Run Phase C (browser)
npx playwright test

# 9. Review
npx playwright show-report
```

---

## Part 2: AI-Assisted Testing

### When to Use AI-Assisted QA

AI review (Layer 4) is used after Layers 1-3 have run.  Feed the outputs
to Claude with specific prompts:

#### Gap Analysis Prompt

```
You are auditing OpenRiC for parity with Heratio.

Here are the audit results:
  [paste audit-report JSON]

Here are the Playwright test results:
  [paste test summary]

For each package, answer:
  1. What routes are missing?
  2. What pages have fewer controls than expected?
  3. What forms are missing fields?
  4. What CSS rules violate the 12 Rules?
  5. Priority ranking (P1=blocker, P2=important, P3=nice-to-have)
```

#### Semantic Review Prompt

```
Review this Blade view for correctness:
  [paste view contents]

Check:
  1. Does it extend openric-theme::layout (not ahg-theme::layout)?
  2. Does it use openric-btn-* classes (not atom-btn-*)?
  3. Does it use var(--openric-primary) (not var(--ahg-primary))?
  4. Are all @include paths correct for OpenRiC packages?
  5. Are form actions pointing to valid OpenRiC routes?
```

#### Fix Verification Prompt

```
I fixed [describe fix].

Before: [paste old code]
After:  [paste new code]

Verify:
  1. Does the fix comply with the 12 Rules?
  2. Does the fix break any existing functionality?
  3. Are there related files that need the same fix?
```

---

## Part 2: Playwright E2E Testing

### Setup

```bash
cd /usr/share/nginx/OpenRiC

# Install Playwright and browsers
npm install -D @playwright/test
npx playwright install --with-deps chromium

# Verify installation
npx playwright --version
```

### Configuration

The Playwright config is at `/usr/share/nginx/OpenRiC/playwright.config.ts`:

```typescript
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
```

**Key design decisions:**

- The `setup` project runs `auth.setup.ts` first to log in and save session state
- The `authenticated` project depends on `setup` and reuses the saved session
- The `chromium` project runs unauthenticated tests (public pages)
- Screenshots are captured on every test; traces only on first retry (saves disk)

### Test Structure

```
tests/e2e/
  auth.setup.ts                    # Login once, save session
  .auth/
    user.json                      # Saved session state (gitignored)
  parity/
    browse-pages.spec.ts           # 15 browse page tests
    edit-pages.spec.ts             # 10 edit form tests
  visual/
    screenshots.spec.ts            # 23 screenshot comparison tests
    layout.spec.ts                 # Layout and responsive tests
    baselines/                     # Golden screenshot baselines
  screenshots/                     # Test-captured screenshots
```

### Test Patterns

#### Browse Page Pattern

Each browse test navigates to a page and checks:
1. HTTP status is not 500
2. Page title contains expected text
3. Minimum heading count
4. Minimum link count
5. Minimum button count
6. Captures full-page screenshot

```typescript
test('Information Objects', async ({ page }) => {
  const response = await page.goto('informationobject/browse');
  expect(response!.status()).toBeLessThan(500);
  const title = await page.title();
  expect(title.toLowerCase()).toContain('browse');
  const headings = await page.locator('h1, h2, h3, h4, h5, h6').count();
  expect(headings).toBeGreaterThanOrEqual(1);
});
```

#### Edit Form Pattern

Each edit test navigates to a create form and checks:
1. Not redirected to login (auth working)
2. At least one `<form>` tag present
3. Minimum input/select/textarea counts
4. Aggregate field count meets threshold

#### Visual Regression Pattern

Uses Playwright's built-in `toHaveScreenshot()` with baselines:
1. First run with `--update-snapshots` creates golden images
2. Subsequent runs compare against baselines
3. Threshold: `maxDiffPixelRatio: 0.02` (2% tolerance for dynamic content)
4. Animations disabled via injected CSS

#### Layout Verification Pattern

Checks structural properties:
1. Column layout detection (1col/2col/3col)
2. Sidebar presence via multiple selector strategies
3. Header/footer presence
4. Responsive behavior at mobile (375px), tablet (768px), desktop (1440px)
5. No horizontal overflow at any breakpoint

### Authenticated Testing

The auth setup flow:

1. `auth.setup.ts` navigates to `/login`
2. Fills email/username and password from environment variables
3. Submits the form
4. Waits for redirect away from `/login`
5. Verifies a logout link or user menu is visible
6. Saves cookies and localStorage to `tests/e2e/.auth/user.json`
7. All tests in the `authenticated` project reuse this state

Environment variables:

| Variable | Default | Purpose |
|----------|---------|---------|
| `OPENRIC_URL` | `https://ric.theahg.co.za` | Base URL of the OpenRiC instance |
| `HERATIO_URL` | `https://heratio.theahg.co.za` | Base URL of the Heratio instance |
| `TEST_USER` | `demo@openric.org` | Login username/email |
| `TEST_PASS` | `demo` | Login password |

### Running Tests

```bash
# Run all tests
npx playwright test

# Run only browse-page parity tests
npx playwright test parity/browse-pages

# Run only edit-page parity tests
npx playwright test parity/edit-pages

# Run only visual regression tests
npx playwright test visual/screenshots

# Run only layout tests
npx playwright test visual/layout

# Run only the authenticated project
npx playwright test --project=authenticated

# Update visual baselines
npx playwright test visual/screenshots --update-snapshots

# Run with visible browser (debug mode)
npx playwright test --headed

# Run a single test by name
npx playwright test -g "Information Objects"

# Generate HTML report
npx playwright show-report
```

### Visual Regression Workflow

```
1. Initial baseline creation:
   npx playwright test visual/screenshots --update-snapshots

2. After code changes:
   npx playwright test visual/screenshots

3. If diffs are expected (intentional changes):
   npx playwright test visual/screenshots --update-snapshots

4. If diffs are unexpected (regressions):
   Fix the code, then re-run tests.

5. Review diffs in the HTML report:
   npx playwright show-report
```

The HTML report shows side-by-side comparisons with highlighted diff regions.

---

## Combined Workflow Diagram

```
                        +---------------------------+
                        |   Start: Package Audit    |
                        +---------------------------+
                                    |
                 +------------------+------------------+
                 |                                     |
        +--------v--------+                   +--------v--------+
        |  Phase A: Static |                   | Phase B: Live   |
        |  (no server)     |                   | (server needed) |
        +--------+--------+                   +--------+--------+
                 |                                     |
     +-----------+-----------+              +----------+----------+
     |           |           |              |          |          |
  views.php  ctrl.php  svc.php       routes.php  controls.php  forms.php
     |           |           |              |          |          |
     +-----+-----+-----+----+              +----+-----+----+----+
           |                                     |
           v                                     v
  +--------+--------+                   +--------+--------+
  | Static Report   |                   | HTTP Report     |
  | (views, methods)|                   | (status, counts)|
  +---------+-------+                   +--------+--------+
            |                                    |
            +----------------+-------------------+
                             |
                    +--------v--------+
                    | Phase C:        |
                    | Playwright E2E  |
                    +--------+--------+
                             |
              +--------------+--------------+
              |              |              |
         browse.spec    edit.spec    visual.spec
              |              |              |
              +------+-------+------+------+
                     |
                     v
            +--------+--------+
            | E2E Report      |
            | (screenshots,   |
            |  test results)  |
            +--------+--------+
                     |
                     v
            +--------+--------+
            | Layer 4: Claude |
            | AI-Assisted QA  |
            +--------+--------+
                     |
                     v
            +--------+---------+
            | Prioritized Gap  |
            | Report           |
            | P1/P2/P3 fixes   |
            +------------------+
```

---

## Replication Instructions

To replicate this full audit methodology on a new server:

### 1. Server Requirements

- PHP 8.4+ with curl, dom, json extensions
- Node.js 20+ with npm
- Chromium (installed via Playwright)
- Access to both Heratio and OpenRiC codebases
- Network access to both live servers (for HTTP tests)

### 2. Install Toolchain

```bash
# PHP audit tools (no extra packages beyond Laravel's requirements)
php -m | grep -E "curl|dom|json"

# Playwright
cd /usr/share/nginx/OpenRiC
npm install -D @playwright/test
npx playwright install --with-deps chromium

# Optional: jq for JSON processing
sudo apt install jq
```

### 3. Configure Environment

```bash
# Create a .env.testing or export directly
export OPENRIC_URL=https://ric.theahg.co.za
export HERATIO_URL=https://heratio.theahg.co.za
export TEST_USER=admin@openric.org
export TEST_PASS=your-secure-password
```

### 4. Run Full Audit

```bash
# Phase A
php bin/audit-views.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
php bin/audit-controllers.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
php bin/audit-services.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
php bin/audit-css.php --path=/usr/share/nginx/OpenRiC/packages
php bin/audit-config.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC
php bin/audit-migrations.php --heratio=/usr/share/nginx/Heratio --openric=/usr/share/nginx/OpenRiC

# Phase B
php bin/audit-routes.php --base-url=$OPENRIC_URL --cookie="$SESSION_COOKIE"
php bin/audit-controls.php \
  --heratio-url=$HERATIO_URL --openric-url=$OPENRIC_URL \
  --heratio-cookie="$HERATIO_COOKIE" --openric-cookie="$OPENRIC_COOKIE"
php bin/audit-forms.php --heratio-url=$HERATIO_URL --openric-url=$OPENRIC_URL
php bin/audit-api.php --heratio-url=$HERATIO_URL/api/v2 --openric-url=$OPENRIC_URL/api/v2

# Phase C
npx playwright test

# Phase D (manual)
# Feed reports to Claude for AI-assisted gap analysis
```

### 5. Interpret Results

After all phases complete:

1. **Count missing routes** from `audit-routes.php` output
2. **Count missing views** from `audit-views.php` output
3. **Count missing methods** from `audit-controllers.php` and `audit-services.php`
4. **Count control deltas** from `audit-controls.php` (more than 20% difference = needs work)
5. **Count Playwright failures** from `npx playwright show-report`
6. **Calculate overall parity score**: `(passing tests / total tests) * 100`

A package is "done" when:
- All Heratio routes exist in OpenRiC (100% route parity)
- All pages return HTTP 200 (no broken pages)
- Control counts are within 10% of Heratio (functional parity)
- All Playwright tests pass (visual parity)
- No violations of the 12 Rules (code quality parity)
