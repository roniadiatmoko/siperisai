<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Report $report */

$this->title = 'Laporan Terkirim';
?>

<div class="report-success">
    <div class="alert alert-success">
        Terima kasih atas partisipasi Anda dalam menjaga keselamatan dan kesehatan kerja.
        Laporan Anda telah berhasil diterima dan akan ditindaklanjuti oleh Tim K3L.
        "Satu laporan yang Anda sampaikan hari ini dapat menjadi langkah pencegahan terjadinya kecelakaan di masa depan."

        <?php if (isset($report) && $report !== null): ?>
            <hr>
            <div><strong>Nomor Laporan:</strong> <?= Html::encode($report->report_number) ?></div>
            <div><strong>Lokasi:</strong> <?= Html::encode($report->location ? $report->location->name : '-') ?></div>
            <div><strong>Detail Lokasi:</strong> <?= nl2br(Html::encode($report->detail_lokasi ?: '-')) ?></div>
            <div class="mt-2"><?= Html::a('Cek Progres Laporan', ['track', 'report_number' => $report->report_number], ['class' => 'btn btn-sm btn-outline-primary']) ?></div>
        <?php endif; ?>
    </div>

</div>