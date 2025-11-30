<?php

namespace App\Util;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Style\Conditional;

/**
 * Class ExportUtil
 *
 * Util for exporting data
 *
 * @package App\Util
 */
class ExportUtil
{
    /**
     * Export SLA history to Excel file
     *
     * @param array<array<string, float>> $slaHistory The sla history data
     * @param string|null $fileName Exported file name
     *
     * @return Response The download excel file response
     */
    public function exportSLAHistory(array $slaHistory, ?string $fileName = null): Response
    {
        if ($fileName == null) {
            $fileName = 'sla-history-' . date('Y-m-d') . '.xlsx';
        }

        // init spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // set header row style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '333333']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '666666']]]
        ];

        // set data row style
        $dataStyle = [
            'font' => ['color' => ['rgb' => 'CCCCCC']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E1E1E']],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '444444']]]
        ];

        // add headers
        $sheet->setCellValue('A1', 'Service Name');
        $sheet->setCellValue('B1', 'Month');
        $sheet->setCellValue('C1', 'SLA (%)');

        // apply header style
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        // set data rows
        $row = 2; // start from second row (after the headers)
        foreach ($slaHistory as $serviceName => $months) {
            foreach ($months as $month => $sla) {
                $sheet->setCellValue("A{$row}", $serviceName);
                $sheet->setCellValue("B{$row}", $month);
                $sheet->setCellValue("C{$row}", $sla);
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($dataStyle);
                $row++;
            }
        }

        // apply column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);

        // add conditional formatting for SLA column (change text color)
        $conditionalStyles = [];

        // red for SLA < 99
        $redStyle = new Conditional();
        $redStyle->setConditionType(Conditional::CONDITION_CELLIS);
        $redStyle->setOperatorType(Conditional::OPERATOR_LESSTHAN);
        $redStyle->addCondition(99);
        $redStyle->getStyle()->getFont()->getColor()->setRGB('FF1919'); // red text

        // green for SLA >= 99
        $greenStyle = new Conditional();
        $greenStyle->setConditionType(Conditional::CONDITION_CELLIS);
        $greenStyle->setOperatorType(Conditional::OPERATOR_GREATERTHANOREQUAL);
        $greenStyle->addCondition(99);
        $greenStyle->getStyle()->getFont()->getColor()->setRGB('00FF00'); // green text

        $conditionalStyles[] = $redStyle;
        $conditionalStyles[] = $greenStyle;

        // apply conditional styles to SLA column (text color)
        $sheet->getStyle("C2:C{$row}")->setConditionalStyles($conditionalStyles);

        // create new xlsx writer
        $writer = new Xlsx($spreadsheet);

        // start output buffering
        ob_start();

        // save spreadsheet to php://output
        $writer->save('php://output');

        // get contents of the buffer
        $output = ob_get_clean() ?: '';

        // return response with excel file
        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '.xlsx"');
        return $response;
    }

    /**
     * Merge monitoring statuses with service configuration
     *
     * @param array<int, array<string, mixed>> $snapshot Monitoring status snapshot
     * @param array<string, array<string, mixed>> $servicesConfig Monitored services configuration
     *
     * @return array<int, array<string, mixed>> The merged array
     */
    public function mergeStatusWithServiceConfig(array $snapshot, array $servicesConfig): array
    {
        $indexedStatuses = [];
        foreach ($snapshot as $entry) {
            $indexedStatuses[$entry['service_name']] = $entry;
        }

        $services = [];

        foreach ($servicesConfig as $serviceName => $config) {
            $entry = $indexedStatuses[$serviceName] ?? null;
            $services[] = $this->buildServiceEntry($serviceName, $config, $entry);
            unset($indexedStatuses[$serviceName]);
        }

        foreach ($indexedStatuses as $serviceName => $entry) {
            $services[] = $this->buildServiceEntry($serviceName, null, $entry);
        }

        return $services;
    }

    /**
     * Build service entry using config and snapshot data
     *
     * @param string $serviceName Service identifier
     * @param array<string, mixed>|null $config Service configuration
     * @param array<string, mixed>|null $status Monitoring snapshot entry
     *
     * @return array<string, mixed> The service entry
     */
    public function buildServiceEntry(string $serviceName, ?array $config, ?array $status): array
    {
        return [
            'service_name' => $serviceName,
            'display_name' => $config['display_name'] ?? $serviceName,
            'type' => $config['type'] ?? 'virtual',
            'monitoring' => $config['monitoring'] ?? null,
            'status' => $status['status'] ?? 'unknown',
            'message' => $status['message'] ?? null,
            'down_time_minutes' => $status['down_time_minutes'] ?? 0,
            'sla_timeframe' => $status['sla_timeframe'] ?? null,
            'current_sla' => $status['current_sla'] ?? null,
            'last_update_time' => $status['last_update_time'] ?? null
        ];
    }
}
