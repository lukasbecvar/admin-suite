<?php

namespace App\Tests\Util;

use App\Util\AggregationUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class AggregationUtilTest
 *
 * Test cases for aggregation util
 *
 * @package App\Tests\Util
 */
#[CoversClass(AggregationUtil::class)]
class AggregationUtilTest extends TestCase
{
    private AggregationUtil $aggregationUtil;

    protected function setUp(): void
    {
        $this->aggregationUtil = new AggregationUtil();
    }

    /**
     * Test build months metadata returns ordered summary
     *
     * @return void
     */
    public function testBuildMonthsMetadataReturnsOrderedSummary(): void
    {
        $groupedMetrics = [
            'service|cpu|2025-03' => ['month' => '2025-03'],
            'service|cpu|2025-01' => ['month' => '2025-01'],
            'service|cpu|2025-02' => ['month' => '2025-02']
        ];

        // call tested method
        $metadata = $this->aggregationUtil->buildMonthsMetadata($groupedMetrics);

        // assert result
        $this->assertSame(3, $metadata['month_count']);
        $this->assertSame('January 2025 – March 2025', $metadata['period_summary']);
        $this->assertSame(['January 2025', 'February 2025', 'March 2025'], $metadata['months']);
    }

    /**
     * Test should aggregate detects multi record groups
     *
     * @return void
     */
    public function testShouldAggregateDetectsMultiRecordGroups(): void
    {
        // call tested method and assert result
        $this->assertFalse($this->aggregationUtil->shouldAggregate([
            'group-a' => ['count' => 1],
            'group-b' => ['count' => 1]
        ]));

        // call tested method and assert result
        $this->assertTrue($this->aggregationUtil->shouldAggregate([
            'group-a' => ['count' => 2],
            'group-b' => ['count' => 1]
        ]));
    }

    /**
     * Test format success message includes month count
     *
     * @return void
     */
    public function testFormatSuccessMessageIncludesMonthCount(): void
    {
        // call tested method
        $message = $this->aggregationUtil->formatSuccessMessage(
            ['deleted' => 54, 'created' => 6],
            ['month_count' => 6]
        );

        // assert result
        $this->assertSame('Aggregated 54 old metrics into 6 monthly averages covering 6 months.', $message);
    }
}
