<?php

namespace App\Support;

class Markdown
{
    /**
     * Escape user-entered text (task titles, names) so it shows as plain text
     * in emails instead of being turned into links or formatting.
     */
    public static function escape(?string $text): string
    {
        return addcslashes((string) $text, '\\`*_{}[]()<>#+-.!|~');
    }
}
