#!/usr/bin/env php
<?php
/**
 * OpenRiC Badge & Button Fixer (Pass 1)
 *
 * Scans all blade files in OpenRiC packages and:
 *   1. Adds field-level badges (Required / Recommended / Optional) to <label class="form-label">
 *      tags that are missing them.
 *   2. Replaces incorrect Bootstrap button classes:
 *        btn-light  -> atom-btn-white
 *        btn-dark   -> atom-btn-white
 *
 * Badge logic:
 *   - If the next input/select/textarea after the label has the 'required' attribute -> Required (bg-danger)
 *   - If the field name appears in the RECOMMENDED list                             -> Recommended (bg-warning)
 *   - Everything else                                                               -> Optional (bg-secondary)
 *
 * Usage:
 *   php bin/fix-badges-and-buttons.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv);
$base   = '/usr/share/nginx/OpenRiC/packages';

// ── Recommended field names (from archival standards ISAD(G), ISAAR(CPF), ISDIAH) ──
$recommendedFields = [
    'authorized_form_of_name', 'dates_of_existence', 'description_identifier',
    'institution_identifier', 'repository', 'level_of_description', 'extent_and_medium',
    'scope_and_content', 'archival_history', 'acquisition', 'arrangement',
    'conditions_governing_access', 'conditions_governing_reproduction',
    'language_of_material', 'script_of_material', 'finding_aids',
    'related_units_of_description', 'publication_note', 'sources',
    'rules_or_conventions', 'dates_of_creation_revision_deletion',
    'language_of_description', 'script_of_description',
    'type_of_entity', 'places', 'legal_status', 'functions',
    'mandates', 'internal_structures', 'general_context',
    'description_status', 'level_of_detail', 'maintenance_notes',
    'collecting_area', 'holdings', 'geocultural_context',
    'type', 'classification', 'dates_of_existence',
];

// Build lookup set for fast matching
$recommendedSet = array_flip($recommendedFields);

// ── Collect blade files ──
$bladeFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
foreach ($rii as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $bladeFiles[] = $file->getPathname();
    }
}

sort($bladeFiles);

$totalFilesChanged = 0;
$totalBadgesAdded  = 0;
$totalButtonsFixed = 0;

echo "===========================================\n";
echo "  OpenRiC Badge & Button Fixer  (Pass 1)\n";
echo "  Scanning: {$base}\n";
echo "  Blade files found: " . count($bladeFiles) . "\n";
if ($dryRun) echo "  MODE: dry-run (no files will be modified)\n";
echo "===========================================\n\n";

foreach ($bladeFiles as $filePath) {
    $original = file_get_contents($filePath);
    $content  = $original;
    $fileBadges  = 0;
    $fileButtons = 0;

    // ── 1. Fix badges on labels ──
    // Find <label class="form-label">...</label> without an existing badge
    // We process the file content as a whole so we can look-ahead for the next input element
    $lines = explode("\n", $content);
    $lineCount = count($lines);

    for ($i = 0; $i < $lineCount; $i++) {
        $line = $lines[$i];

        // Skip labels that already have a badge
        if (str_contains($line, 'badge bg-')) {
            continue;
        }

        // Match a label open tag with class="form-label"
        if (!preg_match('/<label[^>]*class="[^"]*form-label[^"]*"[^>]*>/', $line)) {
            continue;
        }

        // Check if this label has a closing </label> (single-line or within next few lines)
        $labelBlock = $line;
        $closingLine = $i;
        if (!str_contains($line, '</label>')) {
            // Look ahead up to 5 lines for closing tag
            for ($j = $i + 1; $j < min($i + 6, $lineCount); $j++) {
                $labelBlock .= "\n" . $lines[$j];
                if (str_contains($lines[$j], '</label>')) {
                    $closingLine = $j;
                    break;
                }
            }
        }

        if (!str_contains($labelBlock, '</label>')) {
            continue; // Can't find closing tag, skip
        }

        // Already has badge?
        if (str_contains($labelBlock, 'badge bg-')) {
            continue;
        }

        // Determine badge type by looking at context
        $badgeType = determineBadgeType($lines, $i, $closingLine, $lineCount, $recommendedSet);
        $badgeHtml = buildBadgeHtml($badgeType);

        // Insert badge before </label>
        $insertLine = $closingLine;
        $lines[$insertLine] = str_replace('</label>', $badgeHtml . '</label>', $lines[$insertLine]);
        $fileBadges++;
    }

    $content = implode("\n", $lines);

    // ── 2. Fix button classes ──
    $buttonPatterns = [
        '/\bbtn-light\b/'  => 'atom-btn-white',
        '/\bbtn-dark\b/'   => 'atom-btn-white',
    ];

    foreach ($buttonPatterns as $pattern => $replacement) {
        $count = 0;
        $content = preg_replace($pattern, $replacement, $content, -1, $count);
        $fileButtons += $count;
    }

    // ── Write if changed ──
    if ($content !== $original) {
        $totalFilesChanged++;
        $totalBadgesAdded  += $fileBadges;
        $totalButtonsFixed += $fileButtons;

        $rel = str_replace($base . '/', '', $filePath);
        echo "  FIXED  {$rel}";
        if ($fileBadges > 0)  echo "  [+{$fileBadges} badges]";
        if ($fileButtons > 0) echo "  [+{$fileButtons} buttons]";
        echo "\n";

        if (!$dryRun) {
            file_put_contents($filePath, $content);
        }
    }
}

echo "\n===========================================\n";
echo "  Summary\n";
echo "  Files changed:  {$totalFilesChanged}\n";
echo "  Badges added:   {$totalBadgesAdded}\n";
echo "  Buttons fixed:  {$totalButtonsFixed}\n";
echo "===========================================\n";

// ─────────────────────────────────────────────
// Helper functions
// ─────────────────────────────────────────────

/**
 * Determine badge type for a label by inspecting:
 *   1. The next input/select/textarea for a 'required' attribute
 *   2. The field name/id against the recommended list
 *   3. Fall back to Optional
 */
function determineBadgeType(array $lines, int $labelStart, int $labelEnd, int $lineCount, array $recommendedSet): string
{
    // Extract label text to check for required indicators
    $labelBlock = '';
    for ($k = $labelStart; $k <= $labelEnd; $k++) {
        $labelBlock .= $lines[$k] . ' ';
    }

    // Check if label itself has a required indicator (* or text-danger)
    if (preg_match('/\*\s*<\/span>/', $labelBlock) || str_contains($labelBlock, 'text-danger')) {
        return 'required';
    }

    // Look ahead from the label end for the next input/select/textarea (up to 10 lines)
    $fieldName = '';
    $hasRequired = false;

    for ($k = $labelEnd + 1; $k < min($labelEnd + 12, $lineCount); $k++) {
        $ahead = $lines[$k];

        // Stop if we hit another label or a closing div that likely ends this field group
        if (preg_match('/<label\s/', $ahead) || preg_match('/<\/div>\s*<\/div>/', $ahead)) {
            break;
        }

        // Check for input/select/textarea
        if (preg_match('/<(input|select|textarea)\s/', $ahead)) {
            // Extract name attribute
            if (preg_match('/name=["\']([^"\']+)["\']/', $ahead, $m)) {
                // Normalize: settings[foo_bar] -> foo_bar,  foo[bar] -> bar
                $fieldName = preg_replace('/.*\[([^\]]+)\].*/', '$1', $m[1]);
                if ($fieldName === $m[1]) {
                    $fieldName = $m[1]; // no brackets, use as-is
                }
            }
            // Extract id attribute as fallback
            if (empty($fieldName) && preg_match('/id=["\']([^"\']+)["\']/', $ahead, $m)) {
                $fieldName = $m[1];
            }
            // Check for required attribute
            if (preg_match('/\brequired\b/', $ahead)) {
                $hasRequired = true;
            }
            break;
        }
    }

    // Also check "for" attribute on the label itself as field-name hint
    if (empty($fieldName) && preg_match('/for=["\']([^"\']+)["\']/', $labelBlock, $m)) {
        $fieldName = $m[1];
    }

    if ($hasRequired) {
        return 'required';
    }

    // Normalize field name for recommended lookup
    $normalized = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName));
    if (isset($recommendedSet[$normalized])) {
        return 'recommended';
    }

    return 'optional';
}

/**
 * Build the badge HTML snippet.
 */
function buildBadgeHtml(string $type): string
{
    return match ($type) {
        'required'    => ' <span class="badge bg-danger ms-1">Required</span>',
        'recommended' => ' <span class="badge bg-warning ms-1">Recommended</span>',
        default       => ' <span class="badge bg-secondary ms-1">Optional</span>',
    };
}
