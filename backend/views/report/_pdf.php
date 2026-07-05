<?php

use common\models\Report;
use yii\helpers\Html;

/** @var common\models\Report $model */

$statusLabels = [
    Report::STATUS_NOT_APPROVED => 'Tidak Disetujui Ketua Tim',
    Report::STATUS_SUBMITTED => 'Dikirimkan ke Sekretaris',
    Report::STATUS_TEAM_APPROVED => 'Dikirimkan ke Ketua Tim K3L',
    Report::STATUS_SECRETARY_FINALIZED => 'Finalisasi Tindakan Sekretaris',
    Report::STATUS_COORDINATOR_FOLLOW_UP => 'Tindak Lanjut Koordinator Bidang',
    Report::STATUS_SECRETARY_REVIEW => 'Dikirimkan ke Sekretaris',
    Report::STATUS_CLOSED => 'Tindak Lanjut Koordinator Bidang',
];

$victimConditionLabels = Report::victimConditionOptions();
$teamLeadNote = '-';
$teamLeadDecision = '-';

for ($index = count($model->statusHistories) - 1; $index >= 0; $index--) {
    $history = $model->statusHistories[$index];
    if (in_array($history->status_to, [Report::STATUS_SECRETARY_FINALIZED, Report::STATUS_NOT_APPROVED], true)) {
        $teamLeadDecision = $statusLabels[$history->status_to] ?? $history->status_to;
        $teamLeadNote = !empty($history->note) ? $history->note : '-';
        break;
    }
}

?>

<h2 style="margin-bottom: 4px;">Laporan Keselamatan Kerja</h2>
<p style="margin-top: 0;">No: <?= Html::encode($model->report_number) ?></p>

<h3 style="margin: 18px 0 8px; font-size: 14px;">Data Laporan Pelapor</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px;">
    <tr>
        <td width="35%"><strong>Status</strong></td>
        <td><?= Html::encode($statusLabels[$model->status] ?? '-') ?></td>
    </tr>
    <tr>
        <td><strong>Lokasi</strong></td>
        <td><?= Html::encode($model->location ? $model->location->name : '-') ?></td>
    </tr>
    <tr>
        <td><strong>Pelapor</strong></td>
        <td><?= Html::encode($model->reporter ? $model->reporter->username : ($model->reporter_name ?: '-')) ?></td>
    </tr>
    <tr>
        <td><strong>Laporan Anonim</strong></td>
        <td><?= $model->is_anonymous ? 'Ya' : 'Tidak' ?></td>
    </tr>
    <tr>
        <td><strong>Waktu Kejadian</strong></td>
        <td><?= date('d-m-Y H:i', (int) $model->incident_time) ?></td>
    </tr>
    <tr>
        <td><strong>Deskripsi</strong></td>
        <td><?= nl2br(Html::encode($model->description)) ?></td>
    </tr>
    <tr>
        <td><strong>Ada Korban</strong></td>
        <td><?= $model->has_victim ? 'Ya' : 'Tidak' ?></td>
    </tr>
    <tr>
        <td><strong>Nama Korban</strong></td>
        <td><?= Html::encode($model->victim_name ?: '-') ?></td>
    </tr>
    <tr>
        <td><strong>Kondisi Korban</strong></td>
        <td><?= Html::encode($victimConditionLabels[$model->victim_condition] ?? '-') ?></td>
    </tr>
    <tr>
        <td><strong>Detail Kondisi Korban</strong></td>
        <td><?= nl2br(Html::encode($model->victim_condition_detail ?: '-')) ?></td>
    </tr>
    <tr>
        <td><strong>Ada Kerusakan Sarpras</strong></td>
        <td><?= $model->has_property_damage ? 'Ya' : 'Tidak' ?></td>
    </tr>
    <tr>
        <td><strong>Detail Kerusakan</strong></td>
        <td><?= nl2br(Html::encode($model->property_damage_detail ?: '-')) ?></td>
    </tr>
    <tr>
        <td><strong>Saksi Kejadian</strong></td>
        <td><?= nl2br(Html::encode($model->witness ?: '-')) ?></td>
    </tr>
    <tr>
        <td><strong>Catatan Pelapor</strong></td>
        <td><?= nl2br(Html::encode($model->additional_notes ?: '-')) ?></td>
    </tr>
</table>

<h3 style="margin: 18px 0 8px; font-size: 14px;">Hasil Telaah Sekretaris</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px;">
    <tr>
        <td><strong>Jenis Kejadian</strong></td>
        <td><?= Html::encode($model->incident_type ?: '-') ?></td>
    </tr>
    <tr>
        <td><strong>Rekomendasi</strong></td>
        <td><?= nl2br(Html::encode($model->recommendation ?: '-')) ?></td>
    </tr>
    <tr>
        <td><strong>PIC</strong></td>
        <td><?= Html::encode($model->picUser ? $model->picUser->username : '-') ?></td>
    </tr>
    <tr>
        <td><strong>Data yang Kurang</strong></td>
        <td><?= nl2br(Html::encode($model->missing_data_note ?: '-')) ?></td>
    </tr>
</table>

<h3 style="margin: 18px 0 8px; font-size: 14px;">Hasil Catatan Ketua Tim</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px;">
    <tr>
        <td width="35%"><strong>Keputusan</strong></td>
        <td><?= Html::encode($teamLeadDecision) ?></td>
    </tr>
    <tr>
        <td><strong>Catatan Ketua Tim</strong></td>
        <td><?= nl2br(Html::encode($teamLeadNote)) ?></td>
    </tr>
    <tr>
        <td><strong>Tindak Lanjut Koordinator</strong></td>
        <td><?= nl2br(Html::encode($model->coordinator_follow_up_note ?: '-')) ?></td>
    </tr>
</table>