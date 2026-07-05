<?php

use common\models\Report;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Report $model */

$this->title = 'Preview Finalisasi Laporan';
$this->params['breadcrumbs'][] = ['label' => 'Daftar Laporan', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$victimConditionLabels = Report::victimConditionOptions();
?>

<div class="report-preview">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <p class="text-muted">Pastikan data sudah benar sebelum finalisasi.</p>

            <h5 class="mb-3">Data Laporan Pelapor</h5>
            <table class="table table-striped align-middle">
                <tr><th>No Laporan</th><td><?= Html::encode($model->report_number) ?></td></tr>
                <tr><th>Lokasi</th><td><?= Html::encode($model->location ? $model->location->name : '-') ?></td></tr>
                <tr><th>Pelapor</th><td><?= Html::encode($model->reporter ? $model->reporter->username : ($model->reporter_name ?: '-')) ?></td></tr>
                <tr><th>Laporan Anonim</th><td><?= $model->is_anonymous ? 'Ya' : 'Tidak' ?></td></tr>
                <tr><th>Waktu Kejadian</th><td><?= date('d-m-Y H:i', (int) $model->incident_time) ?></td></tr>
                <tr><th>Deskripsi</th><td><?= nl2br(Html::encode($model->description)) ?></td></tr>
                <tr><th>Ada Korban</th><td><?= $model->has_victim ? 'Ya' : 'Tidak' ?></td></tr>
                <tr><th>Nama Korban</th><td><?= Html::encode($model->victim_name ?: '-') ?></td></tr>
                <tr><th>Kondisi Korban</th><td><?= Html::encode($victimConditionLabels[$model->victim_condition] ?? '-') ?></td></tr>
                <tr><th>Detail Kondisi Korban</th><td><?= nl2br(Html::encode($model->victim_condition_detail ?: '-')) ?></td></tr>
                <tr><th>Ada Kerusakan Sarpras</th><td><?= $model->has_property_damage ? 'Ya' : 'Tidak' ?></td></tr>
                <tr><th>Detail Kerusakan</th><td><?= nl2br(Html::encode($model->property_damage_detail ?: '-')) ?></td></tr>
                <tr><th>Saksi Kejadian</th><td><?= nl2br(Html::encode($model->witness ?: '-')) ?></td></tr>
                <tr><th>Catatan Pelapor</th><td><?= nl2br(Html::encode($model->additional_notes ?: '-')) ?></td></tr>
            </table>

            <h5 class="mb-3">Hasil Telaah Sekretaris</h5>
            <table class="table table-striped align-middle">
                <tr><th>Jenis Kejadian</th><td><?= Html::encode($model->incident_type ?: '-') ?></td></tr>
                <tr><th>Rekomendasi</th><td><?= nl2br(Html::encode($model->recommendation ?: '-')) ?></td></tr>
                <tr><th>PIC</th><td><?= Html::encode($model->picUser ? $model->picUser->username : '-') ?></td></tr>
                <tr><th>Data yang Kurang</th><td><?= nl2br(Html::encode($model->missing_data_note ?: '-')) ?></td></tr>
            </table>

            <div class="d-flex gap-2">
                <?= Html::beginForm(['finalize', 'id' => $model->id], 'post') ?>
                <?= Html::submitButton('Finalisasi kirim ke Ketua Tim K3L', ['class' => 'btn btn-success']) ?>
                <?= Html::endForm() ?>
                <?= Html::a('Kembali Edit', ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>
</div>