<?php

namespace App\Tests\Annotation;

use PHPUnit\Framework\TestCase;
use App\Annotation\Authorization;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class AuthorizationTest
 *
 * Test cases for authorization attribute
 *
 * @package App\Tests\Annotation
 */
#[CoversClass(Authorization::class)]
class AuthorizationTest extends TestCase
{
    /**
     * Test get authorization value
     *
     * @return void
     */
    public function testGetAuthorization(): void
    {
        $authorization = new Authorization('OWNER');

        // call tested method
        $result = $authorization->getAuthorization();

        // assert result
        $this->assertSame('OWNER', $result);
    }
}
