# Form Fields Audit Report - OpenRiC vs Heratio

**Date:** 2026-03-29  
**Purpose:** Compare form fields and controls between Heratio and OpenRiC packages

## Summary by Package

| Package | Diff Files | New Files | Status |
|---------|------------|-----------|--------|
| openric-core | 0 | 0 | ✅ OK |
| openric-accession | 0 | 0 | ✅ OK |
| openric-help | 0 | 0 | ✅ OK |
| openric-ingest | 0 | 0 | ✅ OK |
| openric-auth | 0 | 0 | ✅ OK |
| openric-access-request | 7 | 0 | ⚠️ Minor styling |
| openric-api | 0 | 1 | ℹ️ New feature |
| openric-backup | 4 | 0 | ⚠️ Review |
| openric-cart | 4 | 1 | ⚠️ Review |
| openric-condition | 1 | 5 | ⚠️ Review |
| openric-data-migration | 15 | 1 | 🔴 High priority |
| openric-dedupe | 21 | 2 | 🔴 High priority |
| openric-display | 15 | 4 | 🔴 High priority |
| openric-doi-manage | 11 | 0 | 🔴 High priority |
| openric-dropdown-manage | 2 | 0 | ⚠️ Minor |
| openric-exhibition | 12 | 1 | 🔴 High priority |
| openric-export | 0 | 1 | ℹ️ New feature |
| openric-favorites | 4 | 0 | ⚠️ Review |
| openric-feedback | 6 | 0 | ⚠️ Review |
| openric-gallery | 0 | 1 | ℹ️ New feature |
| openric-integrity | 0 | 2 | ℹ️ New feature |
| openric-jobs-manage | 5 | 1 | ⚠️ Review |
| openric-label | 1 | 1 | ⚠️ Minor |
| openric-landing-page | 9 | 0 | 🔴 High priority |
| openric-provenance | 0 | 2 | ℹ️ New feature |
| openric-reports | 31 | 9 | 🔴 High priority |
| openric-research | 0 | 1 | ℹ️ New feature |
| openric-search | 18 | 2 | 🔴 High priority |
| openric-spectrum | 14 | 2 | 🔴 High priority |
| openric-static-page | 3 | 1 | ⚠️ Review |
| openric-statistics | 3 | 0 | ⚠️ Review |
| openric-translation | 3 | 0 | ⚠️ Review |
| openric-user-manage | 7 | 0 | ⚠️ Review |
| openric-workflow | 21 | 2 | 🔴 High priority |

## Priority Packages to Review

### 🔴 HIGH PRIORITY (15+ differences)
1. **openric-reports** (31 diff, 9 new) - Many report forms differ
2. **openric-dedupe** (21 diff, 2 new) - Authority record deduplication
3. **openric-workflow** (21 diff, 2 new) - Workflow management forms
4. **openric-search** (18 diff, 2 new) - Search interface differences
5. **openric-data-migration** (15 diff, 1 new) - Import/export forms
6. **openric-display** (15 diff, 4 new) - Display settings
7. **openric-spectrum** (14 diff, 2 new) - SPECTRUM compliance forms
8. **openric-doi-manage** (11 diff) - DOI management
9. **openric-exhibition** (12 diff, 1 new) - Exhibition management

### ⚠️ MEDIUM PRIORITY (5-10 differences)
10. **openric-landing-page** (9 diff) - Landing page editor
11. **openric-access-request** (7 diff) - Minor styling
12. **openric-user-manage** (7 diff) - User management forms
13. **openric-feedback** (6 diff) - Feedback forms
14. **openric-favorites** (4 diff) - Favorites management
15. **openric-backup** (4 diff) - Backup settings
16. **openric-cart** (4 diff, 1 new) - E-commerce checkout
17. **openric-static-page** (3 diff) - Static pages
18. **openric-statistics** (3 diff) - Statistics dashboard
19. **openric-translation** (3 diff) - Translation interface

### ✅ OK - No Issues
- openric-core
- openric-accession
- openric-help
- openric-ingest
- openric-auth

## Key Findings

### 1. Access Request Package
Minor styling differences only:
- Layout extends: `openric-theme` vs `theme`
- Session flash key: `session('notice')` vs `session('success')`
- Data access: Array notation vs Object property

### 2. Cart Package
**checkout.blade.php has significant differences:**
- OpenRiC: Simple payment method selection only
- Heratio: Full e-commerce with:
  - Billing information (name, email, phone, address)
  - Shipping address
  - Multiple payment options

### 3. Core & Accession Packages
✅ **No differences found** - These packages are fully compatible

## Recommendations

1. **High priority packages** need detailed field-by-field comparison
2. **Cart checkout** should be updated to include e-commerce fields
3. **Data model consistency** should be verified for all differing packages
4. **NEW files** should be reviewed for missing functionality

## Next Steps

1. Create detailed field maps for high-priority packages
2. Update cart checkout form to match Heratio e-commerce
3. Verify data models support the expected fields
4. Test form submissions after updates
