<?php

namespace App\Support;

class RichText
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ol><ul><li><sub><sup><blockquote><code><pre><span><div>';

    public static function clean(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value) ?? '';
        $value = strip_tags($value, self::ALLOWED_TAGS);
        $value = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value) ?? '';
        $value = preg_replace('/\s+(href|src)\s*=\s*("[^"]*javascript:[^"]*"|\'[^\']*javascript:[^\']*\'|[^\s>]*javascript:[^\s>]*)/i', '', $value) ?? '';

        return trim($value);
    }

    public static function plainText(?string $value): string
    {
        return trim(html_entity_decode(strip_tags((string) $value)));
    }
}
