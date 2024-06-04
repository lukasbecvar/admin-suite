<?php

namespace App\Util;

/**
 * Class SecurityUtil
 *
 * The utility class for security
 *
 * @package App\Util
 */
class SecurityUtil
{
    /**
     * Escape the string
     *
     * @param string $string The string to escape
     *
     * @return string|null The escaped string
     */
    public function escapeString(string $string): ?string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5);
    }
}
