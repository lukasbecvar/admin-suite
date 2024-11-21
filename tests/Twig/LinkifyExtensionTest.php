<?php

namespace App\Tests\Twig;

use App\Twig\LinkifyExtension;
use PHPUnit\Framework\TestCase;

/**
 * Class LinkifyExtensionTest
 *
 * Test cases for linkify twig extension
 *
 * @package App\Tests\Twig
 */
class LinkifyExtensionTest extends TestCase
{
    private LinkifyExtension $linkifyExtension;

    protected function setUp(): void
    {
        $this->linkifyExtension = new LinkifyExtension();
    }

    /**
     * Link data provider
     *
     * @return array<int,array<int,string>> The link data
     */
    public function provideLinkifyTextData(): array
    {
        return [
            [
                'Check this out: http://example.com',
                'Check this out: <a href="http://example.com" class="link" target="_blank">http://example.com</a>'
            ],
            [
                'Visit https://example.com for more info.',
                'Visit <a href="https://example.com" class="link" target="_blank">https://example.com</a> for more info.'
            ],
            [
                'No links here!',
                'No links here!'
            ],
            [
                'Multiple links: http://example.com and https://example.org',
                'Multiple links: <a href="http://example.com" class="link" target="_blank">http://example.com</a> and <a href="https://example.org" class="link" target="_blank">https://example.org</a>'
            ],
            [
                '',
                ''
            ],
            [
                'http://',
                'http://'
            ]
        ];
    }

    /**
     * Test get filters
     *
     * @return void
     */
    public function testGetFilters(): void
    {
        // call tested method
        $filters = $this->linkifyExtension->getFilters();

        // assert result
        $this->assertCount(1, $filters);
        $this->assertEquals('linkify', $filters[0]->getName());
        $this->assertEquals([$this->linkifyExtension, 'linkifyText'], $filters[0]->getCallable());
    }

    /**
     * Test linkify text
     *
     * @dataProvider provideLinkifyTextData
     *
     * @return void
     */
    public function testLinkifyText(string $input, string $expected): void
    {
        // call tested method
        $result = $this->linkifyExtension->linkifyText($input);

        // assert result
        $this->assertEquals($expected, $result);
    }
}
