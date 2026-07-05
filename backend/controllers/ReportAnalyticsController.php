<?php

namespace backend\controllers;

use backend\models\ReportAnalyticsFilterForm;
use backend\services\ReportAnalyticsService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class ReportAnalyticsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Yii::$app->user->can('viewReportAnalytics');
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'export-pdf' => ['get'],
                    'export-excel' => ['get'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $this->ensurePermission();

        $filter = $this->buildFilter();
        $analytics = $this->buildService()->buildAnalytics($filter);

        return $this->render('index', [
            'filter' => $filter,
            'analytics' => $analytics,
        ]);
    }

    public function actionExportPdf()
    {
        $this->ensurePermission();

        $filter = $this->buildFilter();
        $analytics = $this->buildService()->buildAnalytics($filter);

        $html = $this->renderPartial('export-pdf', [
            'filter' => $filter,
            'analytics' => $analytics,
        ]);

        $tmpDir = Yii::getAlias('@runtime/report-analytics');
        FileHelper::createDirectory($tmpDir, 0775, true);

        $fileName = 'rekap-laporan-grafik-' . date('Ymd-His') . '.pdf';
        $filePath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;

        Yii::$app->pdfService->renderHtmlToFile($html, $filePath);

        return Yii::$app->response->sendFile($filePath, $fileName, ['inline' => false]);
    }

    public function actionExportExcel()
    {
        $this->ensurePermission();

        if (!class_exists(Spreadsheet::class)) {
            Yii::$app->session->setFlash('error', 'Library spreadsheet belum tersedia di server.');
            return $this->redirect(['index']);
        }

        $filter = $this->buildFilter();
        $analytics = $this->buildService()->buildAnalytics($filter);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Grafik');

        $meta = $analytics['meta'];
        $sheet->setCellValue('A1', 'Rekap Laporan Grafik K3L');
        $sheet->setCellValue('A2', 'Periode');
        $sheet->setCellValue('B2', date('d-m-Y', (int) $meta['range_start']) . ' s/d ' . date('d-m-Y', (int) $meta['range_end']));
        $sheet->setCellValue('A3', 'Acuan Tanggal');
        $sheet->setCellValue('B3', $filter->date_basis === ReportAnalyticsFilterForm::DATE_BASIS_CREATED ? 'Waktu pelaporan' : 'Waktu kejadian');
        $sheet->setCellValue('A4', 'Total Laporan');
        $sheet->setCellValue('B4', (int) $meta['total_reports']);

        $row = 6;
        $row = $this->writeDatasetSection($sheet, $row, 'Tren Laporan Harian', $analytics['trend']['labels'], $analytics['trend']['data']);
        $row = $this->writeDatasetSection($sheet, $row + 1, 'Komposisi Jenis Kejadian', $analytics['incident_type']['labels'], $analytics['incident_type']['data']);
        $row = $this->writeDatasetSection($sheet, $row + 1, 'Distribusi Status', $analytics['status']['labels'], $analytics['status']['data']);
        $row = $this->writeDatasetSection($sheet, $row + 1, 'Top Lokasi Kejadian', $analytics['top_locations']['labels'], $analytics['top_locations']['data']);
        $row = $this->writeDatasetSection($sheet, $row + 1, 'Korban vs Non-Korban', $analytics['victim']['labels'], $analytics['victim']['data']);
        $this->writeDatasetSection($sheet, $row + 1, 'Kerusakan vs Tidak', $analytics['damage']['labels'], $analytics['damage']['data']);

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = (string) ob_get_clean();

        $fileName = 'rekap-laporan-grafik-' . date('Ymd-His') . '.xlsx';

        return Yii::$app->response->sendContentAsFile(
            $content,
            $fileName,
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function writeDatasetSection($sheet, $startRow, $title, array $labels, array $values)
    {
        $sheet->setCellValue('A' . $startRow, $title);
        $sheet->setCellValue('A' . ($startRow + 1), 'Label');
        $sheet->setCellValue('B' . ($startRow + 1), 'Total');

        $row = $startRow + 2;
        if (empty($labels)) {
            $sheet->setCellValue('A' . $row, '-');
            $sheet->setCellValue('B' . $row, 0);
            return $row;
        }

        foreach ($labels as $index => $label) {
            $sheet->setCellValue('A' . $row, (string) $label);
            $sheet->setCellValue('B' . $row, (int) ($values[$index] ?? 0));
            $row++;
        }

        return $row - 1;
    }

    private function buildFilter()
    {
        $filter = new ReportAnalyticsFilterForm();
        $filter->load(Yii::$app->request->queryParams);
        if (!$filter->validate()) {
            $filter->period = ReportAnalyticsFilterForm::PERIOD_30D;
            $filter->date_basis = ReportAnalyticsFilterForm::DATE_BASIS_INCIDENT;
            $filter->start_date = null;
            $filter->end_date = null;
            $filter->validate();
        }

        return $filter;
    }

    private function buildService()
    {
        return new ReportAnalyticsService();
    }

    private function ensurePermission()
    {
        if (!Yii::$app->user->can('viewReportAnalytics')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses ke laporan grafik.');
        }
    }
}
