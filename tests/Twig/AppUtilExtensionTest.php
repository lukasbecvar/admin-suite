<?php

namespace App\Tests\Twig;

use App\Util\AppUtil;
use App\Twig\AppUtilExtension;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class AppUtilExtensionTest
 *
 * Test cases for app util twig extension
 *
 * @package App\Tests\Twig
 */
#[CoversClass(AppUtilExtension::class)]
class AppUtilExtensionTest extends TestCase
{
    private AppUtil & MockObject $appUtil;
    private AppUtilExtension $appUtilExtension;

    protected function setUp(): void
    {
        $this->appUtil = $this->getMockBuilder(AppUtil::class)->disableOriginalConstructor()->getMock();
        $this->appUtilExtension = new AppUtilExtension($this->appUtil);
    }

    /**
     * Test isFeatureFlagDisabled function
     *
     * @return void
     */
    public function testIsFeatureFlagDisabledFunction(): void
    {
        // mock isFeatureFlagDisabled method
        $this->appUtil->method('isFeatureFlagDisabled')->willReturn(true);

        // get functions
        $functions = $this->appUtilExtension->getFunctions();

        // find isFeatureFlagDisabled function
        $isFeatureFlagDisabledFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'isFeatureFlagDisabled') {
                $isFeatureFlagDisabledFunction = $function;
                break;
            }
        }

        // assert function exists
        $this->assertNotNull($isFeatureFlagDisabledFunction);

        // get callable
        $callable = $isFeatureFlagDisabledFunction->getCallable();
        $this->assertIsCallable($callable);
    }
}
