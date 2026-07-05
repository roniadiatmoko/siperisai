<?php

namespace backend\services;

use backend\models\ReportAnalyticsFilterForm;
use common\models\Report;
use yii\db\Expression;
use yii\db\Query;

class ReportAnalyticsService
{
    public function buildAnalytics(ReportAnalyticsFilterForm $filter)
    {
        $range = $filter->getRangeTimestamps();
        $dateColumn = $filter->getDateColumn();

        $trend = $this->buildTrend($dateColumn, $range['start'], $range['end']);
        $incidentType = $this->buildIncidentTypeComposition($dateColumn, $range['start'], $range['end']);
        $status = $this->buildStatusDistribution($dateColumn, $range['start'], $range['end']);
        $topLocations = $this->buildTopLocations($dateColumn, $range['start'], $range['end']);
        $victim = $this->buildBinaryStats($dateColumn, $range['start'], $range['end'], 'has_victim', 'Ada Korban', 'Tidak Ada Korban');
        $damage = $this->buildBinaryStats($dateColumn, $range['start'], $range['end'], 'has_property_damage', 'Ada Kerusakan', 'Tidak Ada Kerusakan');

        $totalReports = (int) array_sum($trend['data']);

        return [
            'meta' => [
                'range_start' => $range['start'],
                'range_end' => $range['end'],
                'date_column' => $dateColumn,
                'total_reports' => $totalReports,
            ],
            'trend' => $trend,
            'incident_type' => $incidentType,
            'status' => $status,
            'top_locations' => $topLocations,
            'victim' => $victim,
            'damage' => $damage,
        ];
    }

    private function baseQuery($dateColumn, $start, $end)
    {
        return (new Query())
            ->from(['r' => Report::tableName()])
            ->andWhere(['between', 'r.' . $dateColumn, (int) $start, (int) $end]);
    }

    private function buildTrend($dateColumn, $start, $end)
    {
        $rows = $this->baseQuery($dateColumn, $start, $end)
            ->select([
                'day' => new Expression('DATE(FROM_UNIXTIME(r.' . $dateColumn . '))'),
                'total' => new Expression('COUNT(*)'),
            ])
            ->groupBy(new Expression('DATE(FROM_UNIXTIME(r.' . $dateColumn . '))'))
            ->orderBy(['day' => SORT_ASC])
            ->all();

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(string) $row['day']] = (int) $row['total'];
        }

        $labels = [];
        $data = [];
        for ($cursor = (int) $start; $cursor <= (int) $end; $cursor += 86400) {
            $day = date('Y-m-d', $cursor);
            $labels[] = date('d M', $cursor);
            $data[] = $byDay[$day] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function buildIncidentTypeComposition($dateColumn, $start, $end)
    {
        $rows = $this->baseQuery($dateColumn, $start, $end)
            ->leftJoin(['it' => '{{%ref_incident_type}}'], 'CAST(r.incident_type AS UNSIGNED) = it.id')
            ->select([
                'label' => new Expression("COALESCE(it.name, 'Belum ditentukan')"),
                'total' => new Expression('COUNT(*)'),
            ])
            ->groupBy(new Expression("COALESCE(it.name, 'Belum ditentukan')"))
            ->orderBy(['total' => SORT_DESC])
            ->all();

        return $this->toLabelValueDataset($rows);
    }

    private function buildStatusDistribution($dateColumn, $start, $end)
    {
        $statusLabels = [
            Report::STATUS_NOT_APPROVED => 'Tidak Disetujui Ketua Tim',
            Report::STATUS_SUBMITTED => 'Dikirimkan ke Sekretaris',
            Report::STATUS_SECRETARY_REVIEW => 'Dikirimkan ke Sekretaris',
            Report::STATUS_TEAM_APPROVED => 'Dikirimkan ke Ketua Tim K3L',
            Report::STATUS_SECRETARY_FINALIZED => 'Finalisasi Tindakan Sekretaris',
            Report::STATUS_COORDINATOR_FOLLOW_UP => 'Tindak Lanjut Koordinator Bidang',
            Report::STATUS_CLOSED => 'Selesai',
        ];

        $rows = $this->baseQuery($dateColumn, $start, $end)
            ->select([
                'status' => 'r.status',
                'total' => new Expression('COUNT(*)'),
            ])
            ->groupBy(['r.status'])
            ->orderBy(['total' => SORT_DESC])
            ->all();

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $labels[] = $statusLabels[$status] ?? $status;
            $values[] = (int) $row['total'];
        }

        return [
            'labels' => $labels,
            'data' => $values,
        ];
    }

    private function buildTopLocations($dateColumn, $start, $end)
    {
        $rows = $this->baseQuery($dateColumn, $start, $end)
            ->leftJoin(['l' => '{{%location}}'], 'r.location_id = l.id')
            ->select([
                'label' => new Expression("COALESCE(l.name, 'Lokasi tidak diketahui')"),
                'total' => new Expression('COUNT(*)'),
            ])
            ->groupBy(new Expression("COALESCE(l.name, 'Lokasi tidak diketahui')"))
            ->orderBy(['total' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->toLabelValueDataset($rows);
    }

    private function buildBinaryStats($dateColumn, $start, $end, $column, $yesLabel, $noLabel)
    {
        $rows = $this->baseQuery($dateColumn, $start, $end)
            ->select([
                'flag' => 'r.' . $column,
                'total' => new Expression('COUNT(*)'),
            ])
            ->groupBy(['r.' . $column])
            ->all();

        $yes = 0;
        $no = 0;
        foreach ($rows as $row) {
            if ((int) $row['flag'] === 1) {
                $yes = (int) $row['total'];
            } else {
                $no += (int) $row['total'];
            }
        }

        return [
            'labels' => [$yesLabel, $noLabel],
            'data' => [$yes, $no],
        ];
    }

    private function toLabelValueDataset(array $rows)
    {
        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = (string) $row['label'];
            $values[] = (int) $row['total'];
        }

        return [
            'labels' => $labels,
            'data' => $values,
        ];
    }
}
