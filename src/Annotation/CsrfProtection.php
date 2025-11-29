<?php

namespace App\Annotation;

use Attribute;

/**
 * Class CsrfProtection
 *
 * Attribute for controller methods that controls CSRF verification
 * Checked inside CsrfProtectionMiddleware when POST requests handled
 *
 * Warning: this is used for disabling custom CSRF validation for forms because forms handle validation themselves
 *
 * @package App\Annotation
 */
#[Attribute(Attribute::TARGET_METHOD)]
class CsrfProtection
{
    private bool $enabled;

    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if CSRF validation is enabled
     *
     * @return bool True when CSRF verification is required
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
