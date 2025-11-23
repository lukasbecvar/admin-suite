<?php

namespace App\Util;

use DateTime;

/**
 * Class AggregationUtil
 *
 * Util with methods used during data aggregation flows
 *
 * @package App\Util
 */
class AggregationUtil
{
    /**
     * Build aggregation months metadata for response payloads
     *
     * @param array<string, array<string, mixed>> $groupedMetrics Grouped metrics
     *
     * @return array{month_count: int, months: array<int, string>, period_summary: ?string}
     */
    public function buildMonthsMetadata(array $groupedMetrics): array
    {
        $months = [];

        foreach ($groupedMetrics as $group) {
            if (!isset($group['month'])) {
                continue;
            }

            $monthKey = (string) $group['month'];
            $months[$monthKey] = $monthKey;
        }

        ksort($months);

        $readableMonths = [];
        foreach ($months as $monthKey) {
            $monthDate = DateTime::createFromFormat('Y-m', $monthKey);
            if ($monthDate instanceof DateTime) {
                $readableMonths[] = $monthDate->format('F Y');
                continue;
            }

            $readableMonths[] = $monthKey;
        }

        $monthCount = count($readableMonths);
        $periodSummary = null;

        if ($monthCount > 0) {
            $firstMonth = $readableMonths[0];
            $lastMonth = $readableMonths[$monthCount - 1];
            $periodSummary = $firstMonth === $lastMonth ? $firstMonth : $firstMonth . ' – ' . $lastMonth;
        }

        return [
            'month_count' => $monthCount,
            'months' => $readableMonths,
            'period_summary' => $periodSummary
        ];
    }

    /**
     * Determine if aggregation is needed based on grouped metrics counts
     *
     * @param array<string, array<string, mixed>> $groupedMetrics Grouped metrics
     *
     * @return bool True if aggregation is needed, false otherwise
     */
    public function shouldAggregate(array $groupedMetrics): bool
    {
        foreach ($groupedMetrics as $group) {
            if (($group['count'] ?? 0) > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format a human-friendly aggregation success message
     *
     * @param array<string, int> $result Aggregation result data
     * @param array<string, mixed> $monthMetadata Processed month metadata
     *
     * @return string Success message
     */
    public function formatSuccessMessage(array $result, array $monthMetadata): string
    {
        $monthCount = (int) ($monthMetadata['month_count'] ?? 0);
        $monthSummary = '';

        if ($monthCount > 0) {
            $monthSummary = sprintf(
                ' covering %d month%s',
                $monthCount,
                $monthCount === 1 ? '' : 's'
            );
        }

        return sprintf(
            'Aggregated %d old metrics into %d monthly averages%s.',
            $result['deleted'] ?? 0,
            $result['created'] ?? 0,
            $monthSummary
        );
    }
}
