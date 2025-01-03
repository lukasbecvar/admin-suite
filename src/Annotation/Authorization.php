<?php

namespace App\Annotation;

use Attribute;

/**
 * Class Authorization
 *
 * Controller method annotation for authorization middleware
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
     * Get the authorization value
     *
     * @return string The authorization value
     */
    public function getAuthorization(): string
    {
        return $this->authorization;
    }
}
