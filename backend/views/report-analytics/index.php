<?php

use backend\models\ReportAnalyticsFilterForm;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var ReportAnalyticsFilterForm $filter */
/** @var array $analytics */

$this->title = 'Laporan Grafik';
$this->params['breadcrumbs'][] = $this->title;

$chartPayload = Json::htmlEncode($analytics);
$filterFormName = $filter->formName();
$filterParams = [
    $filterFormName => [
        'period' => $filter->period,
        'date_basis' => $filter->date_basis,
        'start_date' => $filter->start_date,
        'end_date' => $filter->end_date,
    ],
];
?>

<div class="report-analytics-index">
    <div class="card card-outline card-primary mb-3">
        <div class="card-body">
            <?= Html::beginForm(['index'], 'get', ['class' => 'row g-2 align-items-end']) ?>
            <div class="col-md-3">
                <label class="form-label">Periode</label>
                <?= Html::dropDownList($filterFormName . '[period]', $filter->period, ReportAnalyticsFilterForm::periodOptions(), ['id' => 'analytics-period', 'class' => 'form-select']) ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Acuan tanggal</label>
                <?= Html::dropDownList($filterFormName . '[date_basis]', $filter->date_basis, ReportAnalyticsFilterForm::dateBasisOptions(), ['class' => 'form-select']) ?>
            </div>
            <div class="col-md-2 custom-date-input">
                <label class="form-label">Tanggal mulai</label>
                <?= Html::input('date', $filterFormName . '[start_date]', $filter->start_date, ['class' => 'form-control']) ?>
            </div>
            <div class="col-md-2 custom-date-input">
                <label class="form-label">Tanggal selesai</label>
                <?= Html::input('date', $filterFormName . '[end_date]', $filter->end_date, ['class' => 'form-control']) ?>
            </div>
            <div class="col-md-2 d-grid">
                <?= Html::submitButton('Terapkan', ['class' => 'btn btn-primary']) ?>
            </div>
            <?= Html::endForm() ?>

            <div class="d-flex gap-2 mt-3">
                <?= Html::a('Export PDF', array_merge(['export-pdf'], $filterParams), ['class' => 'btn btn-outline-danger btn-sm']) ?>
                <?= Html::a('Export Excel', array_merge(['export-excel'], $filterParams), ['class' => 'btn btn-outline-success btn-sm']) ?>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><strong>Grafik Tren Laporan</strong></div>
                <div class="card-body"><canvas id="trendChart" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><strong>Ringkasan</strong></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Total Laporan:</strong> <?= (int) ($analytics['meta']['total_reports'] ?? 0) ?></p>
                    <p class="mb-1"><strong>Rentang:</strong>
                        <?= date('d-m-Y', (int) ($analytics['meta']['range_start'] ?? time())) ?>
                        s/d
                        <?= date('d-m-Y', (int) ($analytics['meta']['range_end'] ?? time())) ?>
                    </p>
                    <p class="mb-0"><strong>Acuan:</strong> <?= ($analytics['meta']['date_column'] ?? 'incident_time') === 'created_at' ? 'Waktu pelaporan' : 'Waktu kejadian' ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><strong>Jenis Kejadian</strong></div>
                <div class="card-body"><canvas id="incidentTypeChart" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><strong>Distribusi Status</strong></div>
                <div class="card-body"><canvas id="statusChart" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><strong>Top Lokasi Kejadian</strong></div>
                <div class="card-body"><canvas id="locationChart" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header"><strong>Korban</strong></div>
                <div class="card-body"><canvas id="victimChart" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header"><strong>Kerusakan Sarpras</strong></div>
                <div class="card-body"><canvas id="damageChart" height="220"></canvas></div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', ['depends' => [\yii\web\JqueryAsset::class]]);

$js = <<<'JS'
(function () {
    const payload = CHART_PAYLOAD;

    function createChart(id, config) {
        const canvas = document.getElementById(id);
        if (!canvas) {
            return;
        }

        new Chart(canvas, config);
    }

    const palette = ['#198754', '#0d6efd', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d', '#17a2b8', '#343a40'];

    createChart('trendChart', {
        type: 'line',
        data: {
            labels: payload.trend.labels,
            datasets: [{
                label: 'Jumlah laporan',
                data: payload.trend.data,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.2)',
                tension: 0.35,
                fill: true
            }]
        },
        options: {responsive: true, maintainAspectRatio: false}
    });

    createChart('incidentTypeChart', {
        type: 'doughnut',
        data: {
            labels: payload.incident_type.labels,
            datasets: [{data: payload.incident_type.data, backgroundColor: palette}]
        },
        options: {responsive: true, maintainAspectRatio: false}
    });

    createChart('statusChart', {
        type: 'bar',
        data: {
            labels: payload.status.labels,
            datasets: [{label: 'Jumlah', data: payload.status.data, backgroundColor: '#0d6efd'}]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y'
        }
    });

    createChart('locationChart', {
        type: 'bar',
        data: {
            labels: payload.top_locations.labels,
            datasets: [{label: 'Jumlah', data: payload.top_locations.data, backgroundColor: '#198754'}]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y'
        }
    });

    createChart('victimChart', {
        type: 'doughnut',
        data: {
            labels: payload.victim.labels,
            datasets: [{data: payload.victim.data, backgroundColor: ['#dc3545', '#20c997']}]
        },
        options: {responsive: true, maintainAspectRatio: false}
    });

    createChart('damageChart', {
        type: 'doughnut',
        data: {
            labels: payload.damage.labels,
            datasets: [{data: payload.damage.data, backgroundColor: ['#fd7e14', '#0dcaf0']}]
        },
        options: {responsive: true, maintainAspectRatio: false}
    });

    const periodSelect = document.getElementById('analytics-period');
    const customInputs = document.querySelectorAll('.custom-date-input');

    function toggleCustomDateInputs() {
        const isCustom = periodSelect && periodSelect.value === 'custom';
        customInputs.forEach(function (item) {
            item.style.display = isCustom ? '' : 'none';
        });
    }

    if (periodSelect) {
        periodSelect.addEventListener('change', toggleCustomDateInputs);
    }

    toggleCustomDateInputs();
})();
JS;

$this->registerJs(str_replace('CHART_PAYLOAD', $chartPayload, $js));
