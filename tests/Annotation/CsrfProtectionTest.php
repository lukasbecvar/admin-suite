<?php

namespace App\Tests\Annotation;

use PHPUnit\Framework\TestCase;
use App\Annotation\CsrfProtection;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class CsrfProtectionTest
 *
 * Test cases for csrf protection annotation
 *
 * @package App\Tests\Annotation
 */
#[CoversClass(CsrfProtection::class)]
class CsrfProtectionTest extends TestCase
{
    /**
     * Test that is enabled by default
     *
     * @return void
     */
    public function testIsEnabledReturnsTrueByDefault(): void
    {
        $attribute = new CsrfProtection();

        // call tested method
        $result = $attribute->isEnabled();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test that is disabled by constructor
     *
     * @return void
     */
    public function testIsEnabledReflectsConstructorArgument(): void
    {
        $attribute = new CsrfProtection(false);

        // call tested method
        $result = $attribute->isEnabled();

        // assert result
        $this->assertFalse($result);
    }
}
