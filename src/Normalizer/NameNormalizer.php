<?php
declare(strict_types=1);

namespace App\Normalizer;

/**
 * Name normalization helper.
 *
 * Rules implemented:
 * - Trim and lowercase input, then apply ucwords.
 * - Preserves capitalization after common delimiters: space, hyphen, apostrophe.
 *
 * Assumptions & limitations:
 * - This is a pragmatic, language-agnostic normalizer. It does not fully
 *   implement locale-specific capitalization rules (e.g. "van", "de", "la").
 * - Hyphenated and apostrophe names are supported (e.g. "anna-marie", "o'connor").
 * TO DO:
 * - Consider adding locale-specific rules for common prefixes/suffixes.
 * - If strict locale rules are required, inject a locale-aware normalizer.
 */
class NameNormalizer
{
    public function normalize(string $fullName): string
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return '';
        }

        // Lowercase then capitalize words; include apostrophe and hyphen as separators
        $lower = mb_strtolower($fullName);
        $normalized = ucwords($lower, " -'");

        // Handle Mc / Mac patterns: "mcdonald" -> "McDonald", "macarthur" -> "MacArthur"
        // Use regex to match word boundaries.
        $normalized = preg_replace_callback('/\b(mc)([a-z])/i', function ($m) {
            return 'Mc' . strtoupper($m[2]);
        }, $normalized);

        $normalized = preg_replace_callback('/\b(mac)([a-z])/i', function ($m) {
            return 'Mac' . strtoupper($m[2]);
        }, $normalized);

        return $normalized;
    }
}
