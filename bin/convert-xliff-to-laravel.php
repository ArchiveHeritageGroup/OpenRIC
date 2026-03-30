#!/usr/bin/env php
<?php

/**
 * Convert AtoM/Qubit XLIFF translations to Laravel PHP format
 * 
 * Source: /usr/share/nginx/archive/apps/qubit/i18n/{locale}/messages.xml
 * Target: /usr/share/nginx/OpenRiC/lang/{locale}/
 * 
 * Usage: php bin/convert-xliff-to-laravel.php
 */

$sourceDir = '/usr/share/nginx/archive/apps/qubit/i18n';
$targetDir = '/usr/share/nginx/OpenRiC/lang';

// Create target directory if not exists
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Get all locales from source
$locales = array_filter(scandir($sourceDir), function($dir) use ($sourceDir) {
    return is_dir($sourceDir . '/' . $dir) && !in_array($dir, ['.', '..']);
});

// Default English settings for OpenRiC-specific keys
$openricDefaults = require $targetDir . '/en/settings.php';

foreach ($locales as $locale) {
    $sourceFile = $sourceDir . '/' . $locale . '/messages.xml';
    $targetFile = $targetDir . '/' . $locale . '/messages.php';
    
    if (!file_exists($sourceFile)) {
        continue;
    }
    
    echo "Processing {$locale}...\n";
    
    // Parse XLIFF
    $xml = simplexml_load_file($sourceFile);
    if (!$xml) {
        echo "  Failed to parse XML for {$locale}\n";
        continue;
    }
    
    $translations = [];
    foreach ($xml->body->{'trans-unit'} as $unit) {
        $source = (string) $unit->source;
        $target = (string) $unit->target;
        
        // Clean up the source string
        $cleanSource = trim(preg_replace('/\s+/', ' ', $source));
        $cleanTarget = trim(preg_replace('/\s+/', ' ', $target));
        
        if (!empty($cleanSource)) {
            // Create a key from the source (sanitize)
            $key = preg_replace('/[^a-zA-Z0-9_\.]/', '_', $cleanSource);
            $key = strtolower(substr($key, 0, 100));
            $key = preg_replace('/_+/', '_', $key);
            $key = trim($key, '_');
            
            if (!empty($key) && strlen($key) > 2) {
                $translations[$key] = !empty($cleanTarget) ? $cleanTarget : $cleanSource;
            }
        }
    }
    
    // Add OpenRiC-specific translations (defaults)
    foreach ($openricDefaults as $section => $data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $fullKey = $section . '.' . $key . '.' . $subKey;
                        if (!isset($translations[$fullKey])) {
                            $translations[$fullKey] = $subValue;
                        }
                    }
                } else {
                    $fullKey = $section . '.' . $key;
                    if (!isset($translations[$fullKey])) {
                        $translations[$fullKey] = $value;
                    }
                }
            }
        } else {
            if (!isset($translations[$section])) {
                $translations[$section] = $data;
            }
        }
    }
    
    // Generate PHP file
    $output = "<?php\n\ndeclare(strict_types=1);\n\n";
    $output .= "/**\n";
    $output .= " * Language file for locale: {$locale}\n";
    $output .= " * Auto-generated from AtoM/Qubit XLIFF translations\n";
    $output .= " */\n\n";
    $output .= "return [\n";
    
    ksort($translations);
    
    foreach ($translations as $key => $value) {
        $safeKey = addslashes($key);
        $safeValue = addslashes($value);
        $output .= "    '{$safeKey}' => '{$safeValue}',\n";
    }
    
    $output .= "];\n";
    
    // Create locale directory
    if (!is_dir($targetDir . '/' . $locale)) {
        mkdir($targetDir . '/' . $locale, 0755, true);
    }
    
    file_put_contents($targetFile, $output);
    
    echo "  Created {$targetFile} with " . count($translations) . " translations\n";
}

echo "\nDone! Created language files for " . count($locales) . " locales.\n";