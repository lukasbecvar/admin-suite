<?php

namespace App\Tests\Util;

use App\Util\ExportUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class ExportUtilTest
 *
 * Test cases for export util
 *
 * @package App\Tests\Util
 */
#[CoversClass(ExportUtil::class)]
class ExportUtilTest extends TestCase
{
    private ExportUtil $exportUtil;

    protected function setUp(): void
    {
        // create export util instance
        $this->exportUtil = new ExportUtil();
    }

    /**
     * Test export SLA history
     *
     * @return void
     */
    public function testExportSlaHistory(): void
    {
        // test data
        $slaHistory = [
            'Service A' => [
                '2024-01' => 98.5,
                '2024-02' => 99.2
            ],
            'Service B' => [
                '2024-01' => 97.8
            ]
        ];

        // call tested method
        $response = $this->exportUtil->exportSLAHistory($slaHistory);

        // assert response
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('attachment; filename="sla-history-', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Test export SLA history with custom file name
     *
     * @return void
     */
    public function testExportSlaHistoryWithCustomFileName(): void
    {
        // test data
        $fileName = 'custom-sla-history';
        $slaHistory = [
            'Service A' => [
                '2024-01' => 98.5
            ]
        ];

        // call tested method
        $response = $this->exportUtil->exportSLAHistory($slaHistory, $fileName);

        // assert response
        $this->assertStringContainsString("attachment; filename=\"{$fileName}.xlsx\"", $response->headers->get('Content-Disposition') ?? '');
    }
}
