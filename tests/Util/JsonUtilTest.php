<?php

namespace Tests\Unit\Util;

use App\Util\JsonUtil;
use PHPUnit\Framework\TestCase;

/**
 * Class JsonUtilTest
 *
 * Test cases for json util
 *
 * @package Tests\Unit\Util
 */
class JsonUtilTest extends TestCase
{
    private JsonUtil $jsonUtil;

    protected function setUp(): void
    {
        // create json util instance
        $this->jsonUtil = new JsonUtil();
    }

    /**
     * Test get json with different targets
     *
     * @return void
     */
    public function testGetJsonFromFile(): void
    {
        // arrange test data
        $expectedData = ['key' => 'value'];
        $filePath = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($filePath, json_encode($expectedData));

        // call tested method
        $result = $this->jsonUtil->getJson($filePath);

        // assert result
        $this->assertEquals($expectedData, $result);

        // delete test file
        unlink($filePath);
    }

    /**
     * Test get json with different targets
     *
     * @return void
     */
    public function testGetJsonWithInvalidData(): void
    {
        // arrange test data
        $invalidJson = '{"key": "value"';
        $filePath = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($filePath, $invalidJson);

        // call tested method
        $result = $this->jsonUtil->getJson($filePath);

        // assert result
        $this->assertEmpty($result);

        // delete test file
        unlink($filePath);
    }
}
