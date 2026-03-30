#!/usr/bin/env php
<?php
/**
 * OpenRiC Badge Fixer (Pass 2)
 *
 * Catches labels missed by Pass 1:
 *   - Single-line <label class="form-label">Text</label> without badge
 *   - Labels where text is on the same line as the opening tag but </label> is on a later line
 *   - Required detection via text-danger class or *</span> pattern
 *
 * Usage:
 *   php bin/fix-badges-pass2.php [--dry-run]
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
echo "  OpenRiC Badge Fixer  (Pass 2)\n";
echo "  Scanning: {$base}\n";
echo "  Blade files found: " . count($bladeFiles) . "\n";
if ($dryRun) echo "  MODE: dry-run (no files will be modified)\n";
echo "===========================================\n\n";

foreach ($bladeFiles as $filePath) {
    $original = file_get_contents($filePath);
    $content  = $original;
    $fileBadges = 0;

    // ── Pattern A: Single-line labels ──
    // Match: <label class="form-label">...text...</label>  (no badge inside)
    $content = preg_replace_callback(
        '/<label([^>]*class="[^"]*form-label[^"]*"[^>]*)>((?:(?!badge bg-).)*?)<\/label>/s',
        function ($match) use ($recommendedSet, &$fileBadges) {
            $attrs     = $match[1];
            $innerHtml = $match[2];

            // Already has a badge? (safety check)
            if (str_contains($innerHtml, 'badge bg-')) {
                return $match[0];
            }

            $badgeType = determineBadgePass2($attrs, $innerHtml, $recommendedSet);
            $badge     = buildBadge($badgeType);
            $fileBadges++;

            return '<label' . $attrs . '>' . $innerHtml . $badge . '</label>';
        },
        $content
    );

    // ── Pattern B: Multiline labels where opening tag + text is on one line, close on another ──
    // These are labels like:
    //   <label for="x" class="form-label">Some text
    //     <span ...>...</span>
    //   </label>
    // We handle them line-by-line
    $lines = explode("\n", $content);
    $lineCount = count($lines);
    $inOpenLabel = false;
    $openLabelLine = -1;

    for ($i = 0; $i < $lineCount; $i++) {
        $line = $lines[$i];

        // Skip if already has badge
        if (str_contains($line, 'badge bg-')) {
            $inOpenLabel = false;
            continue;
        }

        // Detect label open without close on same line
        if (preg_match('/<label[^>]*class="[^"]*form-label[^"]*"[^>]*>/', $line) && !str_contains($line, '</label>')) {
            $inOpenLabel = true;
            $openLabelLine = $i;
            continue;
        }

        // If we're inside an open label and find the close tag
        if ($inOpenLabel && str_contains($line, '</label>')) {
            // Check that no badge was added between open and close
            $hasBadge = false;
            for ($j = $openLabelLine; $j <= $i; $j++) {
                if (str_contains($lines[$j], 'badge bg-')) {
                    $hasBadge = true;
                    break;
                }
            }

            if (!$hasBadge) {
                // Gather full label block for analysis
                $labelBlock = '';
                for ($j = $openLabelLine; $j <= $i; $j++) {
                    $labelBlock .= $lines[$j] . "\n";
                }
                $badgeType = determineBadgeFromBlock($labelBlock, $lines, $i, $lineCount, $recommendedSet);
                $badge = buildBadge($badgeType);
                $lines[$i] = str_replace('</label>', $badge . '</label>', $lines[$i]);
                $fileBadges++;
            }

            $inOpenLabel = false;
        }
    }

    $content = implode("\n", $lines);

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
echo "  Pass 2 Summary\n";
echo "  Files changed:  {$totalFilesChanged}\n";
echo "  Badges added:   {$totalBadgesAdded}\n";
echo "===========================================\n";

// ─────────────────────────────────────────────
// Helper functions
// ─────────────────────────────────────────────

/**
 * Determine badge type from label attributes and inner HTML (single-line).
 */
function determineBadgePass2(string $attrs, string $innerHtml, array $recommendedSet): string
{
    // Required indicators in the label content
    if (preg_match('/\*\s*<\/span>/', $innerHtml) || str_contains($innerHtml, 'text-danger')) {
        return 'required';
    }

    // Extract field name from "for" attribute
    $fieldName = '';
    if (preg_match('/for=["\']([^"\']+)["\']/', $attrs, $m)) {
        $fieldName = $m[1];
    }

    $normalized = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName));
    if (!empty($normalized) && isset($recommendedSet[$normalized])) {
        return 'recommended';
    }

    return 'optional';
}

/**
 * Determine badge type from a multiline label block + surrounding context.
 */
function determineBadgeFromBlock(string $labelBlock, array $lines, int $closeLine, int $lineCount, array $recommendedSet): string
{
    // Required indicators
    if (preg_match('/\*\s*<\/span>/', $labelBlock) || str_contains($labelBlock, 'text-danger')) {
        return 'required';
    }

    // Extract "for" attribute
    $fieldName = '';
    if (preg_match('/for=["\']([^"\']+)["\']/', $labelBlock, $m)) {
        $fieldName = $m[1];
    }

    // Look ahead for required attribute on input
    if (empty($fieldName) || !isset($recommendedSet[strtolower($fieldName)])) {
        for ($k = $closeLine + 1; $k < min($closeLine + 10, $lineCount); $k++) {
            $ahead = $lines[$k];
            if (preg_match('/<(input|select|textarea)\s/', $ahead)) {
                if (preg_match('/\brequired\b/', $ahead)) {
                    return 'required';
                }
                if (preg_match('/name=["\']([^"\']+)["\']/', $ahead, $m)) {
                    $fieldName = preg_replace('/.*\[([^\]]+)\].*/', '$1', $m[1]);
                }
                break;
            }
            if (preg_match('/<label\s/', $ahead)) {
                break;
            }
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
function buildBadge(string $type): string
{
    return match ($type) {
        'required'    => ' <span class="badge bg-danger ms-1">Required</span>',
        'recommended' => ' <span class="badge bg-warning ms-1">Recommended</span>',
        default       => ' <span class="badge bg-secondary ms-1">Optional</span>',
    };
}
