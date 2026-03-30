#!/usr/bin/env php
<?php
/**
 * OpenRiC Badge Fixer (Pass 3)
 *
 * Final sweep using DOTALL regex to catch any remaining multiline labels
 * that Passes 1 and 2 missed. Matches <label...>...\n...</label> across
 * line boundaries.
 *
 * Usage:
 *   php bin/fix-badges-pass3.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv);
$base   = '/usr/share/nginx/OpenRiC/packages';

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
$recommendedSet = array_flip($recommendedFields);

// Collect blade files
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

echo "===========================================\n";
echo "  OpenRiC Badge Fixer  (Pass 3 - DOTALL)\n";
echo "  Scanning: {$base}\n";
echo "  Blade files found: " . count($bladeFiles) . "\n";
if ($dryRun) echo "  MODE: dry-run (no files will be modified)\n";
echo "===========================================\n\n";

foreach ($bladeFiles as $filePath) {
    $original = file_get_contents($filePath);
    $content  = $original;
    $fileBadges = 0;

    // ── DOTALL multiline match ──
    // Match <label ... class="...form-label..." ...> ... </label> across lines
    // Only match if there is NO badge already inside
    $content = preg_replace_callback(
        '/<label([^>]*class="[^"]*form-label[^"]*"[^>]*)>(.*?)<\/label>/s',
        function ($match) use ($recommendedSet, &$fileBadges, $filePath, $original) {
            $attrs     = $match[1];
            $innerHtml = $match[2];

            // Skip if badge already present
            if (str_contains($innerHtml, 'badge bg-')) {
                return $match[0];
            }

            // Determine badge type
            $badgeType = determineBadgePass3($attrs, $innerHtml, $filePath, $match[0], $recommendedSet);
            $badge     = buildBadgeP3($badgeType);
            $fileBadges++;

            return '<label' . $attrs . '>' . $innerHtml . $badge . '</label>';
        },
        $content
    );

    if ($content !== $original) {
        $totalFilesChanged++;
        $totalBadgesAdded += $fileBadges;

        $rel = str_replace($base . '/', '', $filePath);
        echo "  FIXED  {$rel}  [+{$fileBadges} badges]\n";

        if (!$dryRun) {
            file_put_contents($filePath, $content);
        }
    }
}

echo "\n===========================================\n";
echo "  Pass 3 Summary\n";
echo "  Files changed:  {$totalFilesChanged}\n";
echo "  Badges added:   {$totalBadgesAdded}\n";
echo "===========================================\n";

// ─────────────────────────────────────────────
// Helper functions
// ─────────────────────────────────────────────

/**
 * Determine badge type from label attributes, inner HTML, and surrounding file context.
 */
function determineBadgePass3(string $attrs, string $innerHtml, string $filePath, string $fullMatch, array $recommendedSet): string
{
    // Required indicators in the label content
    if (preg_match('/\*\s*<\/span>/', $innerHtml) || str_contains($innerHtml, 'text-danger')) {
        return 'required';
    }

    // Extract field name from "for" attribute on the label
    $fieldName = '';
    if (preg_match('/for=["\']([^"\']+)["\']/', $attrs, $m)) {
        $fieldName = $m[1];
    }

    // If no "for", try to find field name from the inner HTML text
    if (empty($fieldName)) {
        $plainText = strip_tags($innerHtml);
        $plainText = trim(preg_replace('/\s+/', ' ', $plainText));
        // Convert label text to snake_case for matching
        $snaked = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $plainText));
        $snaked = trim($snaked, '_');
        if (isset($recommendedSet[$snaked])) {
            return 'recommended';
        }
    }

    // Look for the next input/select/textarea after this label in the file
    $fileContent = file_get_contents($filePath);
    $matchPos = strpos($fileContent, $fullMatch);
    if ($matchPos !== false) {
        $afterLabel = substr($fileContent, $matchPos + strlen($fullMatch), 500);

        // Check for required attribute on next form element
        if (preg_match('/<(input|select|textarea)\s[^>]*\brequired\b/i', $afterLabel)) {
            return 'required';
        }

        // Extract name from next form element
        if (preg_match('/<(input|select|textarea)\s[^>]*name=["\']([^"\']+)["\']/i', $afterLabel, $m)) {
            $fieldName = preg_replace('/.*\[([^\]]+)\].*/', '$1', $m[2]);
        }
    }

    $normalized = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName));
    if (!empty($normalized) && isset($recommendedSet[$normalized])) {
        return 'recommended';
    }

    return 'optional';
}

/**
 * Build badge HTML.
 */
function buildBadgeP3(string $type): string
{
    return match ($type) {
        'required'    => ' <span class="badge bg-danger ms-1">Required</span>',
        'recommended' => ' <span class="badge bg-warning ms-1">Recommended</span>',
        default       => ' <span class="badge bg-secondary ms-1">Optional</span>',
    };
}
