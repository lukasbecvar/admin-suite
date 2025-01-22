<?php

namespace App\Annotation;

use Attribute;

/**
 * Class Authorization
 *
 * Annotation for controller methods to mark routes that require admin role
 * This annotation is checked in authorization middleware
 *
 * @package App\Annotation
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Authorization
{
    private string $authorization;

    public function __construct(string $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * Get authorization anotation value
     *
     * @return string The authorization value
     */
    public function getAuthorization(): string
    {
        return $this->authorization;
    }
}
