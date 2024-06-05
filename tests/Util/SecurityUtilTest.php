<?php

namespace App\Tests\Util;

use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;

/**
 * Class SecurityUtilTest
 *
 * Test the security util
 *
 * @package App\Tests\Util
 */
class SecurityUtilTest extends TestCase
{
    private SecurityUtil $securityUtil;

    public function setUp(): void
    {
        $this->securityUtil = new SecurityUtil();
    }

    /**
     * Test if the security util is an instance of SecurityUtil
     *
     * @return void
     */
    public function testEscapeXss(): void
    {
        // test escaping a string with special characters
        $input = '<script>alert("XSS");</script>';
        $expectedOutput = '&lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;';
        $this->assertEquals($expectedOutput, $this->securityUtil->escapeString($input));
    }

    /**
     * Test if the security util is an instance of SecurityUtil
     *
     * @return void
     */
    public function testEscapeNonXss(): void
    {
        // test escaping a string without special characters
        $input = 'Hello, World!';
        $expectedOutput = 'Hello, World!';
        $this->assertEquals($expectedOutput, $this->securityUtil->escapeString($input));
    }
}
