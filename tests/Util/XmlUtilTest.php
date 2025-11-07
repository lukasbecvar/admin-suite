<?php

namespace Tests\Unit\Util;

use App\Util\XmlUtil;
use SimpleXMLElement;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class XmlUtilTest
 *
 * Tests for XML utility helper
 *
 * @package Tests\Unit\Util
 */
#[CoversClass(XmlUtil::class)]
class XmlUtilTest extends TestCase
{
    private XmlUtil $xmlUtil;
    private ErrorManager & MockObject $errorManager;

    protected function setUp(): void
    {
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->errorManager->method('handleError')->willReturnCallback(function (string $message, int $code): void {
            throw new HttpException($code, $message);
        });

        $this->xmlUtil = new XmlUtil($this->errorManager);
    }

    /**
     * Test check if XML request is recognized
     *
     * @return void
     */
    public function testIsXmlRequestRecognizesContentType(): void
    {
        // create request with XML content type
        $request = Request::create(
            uri: '/xml-test',
            method: 'POST',
            parameters: [],
            cookies: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/xml'],
            content: ''
        );

        // call tested method
        $result = $this->xmlUtil->isXmlRequest($request);

        // assert XML detection
        $this->assertTrue($result);
    }

    /**
     * Test check if XML request is recognized
     *
     * @return void
     */
    public function testIsXmlRequestRecognizesBodyContent(): void
    {
        // create request with XML content
        $request = Request::create(
            uri: '/xml-test',
            method: 'POST',
            parameters: [],
            cookies: [],
            files: [],
            server: [],
            content: '<root><node/></root>'
        );

        // call tested method
        $result = $this->xmlUtil->isXmlRequest($request);

        // assert XML detection
        $this->assertTrue($result);
    }

    /**
     * Test parsed into SimpleXML structure
     *
     * @return void
     */
    public function testParseXmlPayloadReturnsSimpleXml(): void
    {
        // call tested method
        $xml = $this->xmlUtil->parseXmlPayload('<log><token>abc</token></log>');

        // assert XML structure
        $this->assertEquals('abc', (string) $xml->token);
    }

    /**
     * Test empty payload triggers error handler
     *
     * @return void
     */
    public function testParseXmlPayloadThrowsOnEmptyPayload(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Payload body is empty');

        // call tested method
        $this->xmlUtil->parseXmlPayload('   ');
    }

    /**
     * Test formatting produces expected nodes and root name
     *
     * @return void
     */
    public function testFormatToXmlProducesExpectedStructure(): void
    {
        // call tested method
        $xmlString = $this->xmlUtil->formatToXml([
            'meta' => [
                'status' => 'ok',
                'count' => 2
            ],
            'items' => ['first', 'second']
        ], 'resources');

        // assert XML structure
        $this->assertStringContainsString('<resources>', $xmlString);
        $this->assertStringContainsString('<status>ok</status>', $xmlString);
        $this->assertStringContainsString('<count>2</count>', $xmlString);
        $xml = simplexml_load_string($xmlString);
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $this->assertEquals('resources', $xml->getName());
    }
}
