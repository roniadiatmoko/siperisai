<?php

use backend\models\ReportAnalyticsFilterForm;
use yii\helpers\Html;

/** @var ReportAnalyticsFilterForm $filter */
/** @var array $analytics */

$meta = $analytics['meta'];

function renderRows($labels, $data)
{
    $html = '';
    $count = count($labels);
    if ($count === 0) {
        return '<tr><td>-</td><td>0</td></tr>';
    }

    for ($i = 0; $i < $count; $i++) {
        $html .= '<tr><td>' . Html::encode((string) $labels[$i]) . '</td><td>' . (int) ($data[$i] ?? 0) . '</td></tr>';
    }

    return $html;
}
?>

<h2 style="margin-bottom: 4px;">Rekap Laporan Grafik K3L</h2>
<p style="margin-top:0;">Diekspor pada: <?= date('d-m-Y H:i:s') ?></p>

<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; margin-bottom: 12px;">
    <tr>
        <td width="35%"><strong>Periode</strong></td>
        <td><?= date('d-m-Y', (int) $meta['range_start']) ?> s/d <?= date('d-m-Y', (int) $meta['range_end']) ?></td>
    </tr>
    <tr>
        <td><strong>Acuan tanggal</strong></td>
        <td><?= $filter->date_basis === ReportAnalyticsFilterForm::DATE_BASIS_CREATED ? 'Waktu pelaporan' : 'Waktu kejadian' ?></td>
    </tr>
    <tr>
        <td><strong>Total laporan</strong></td>
        <td><?= (int) $meta['total_reports'] ?></td>
    </tr>
</table>

<h3 style="margin: 14px 0 6px; font-size: 14px;">1. Tren Laporan Harian</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; margin-bottom: 12px;">
    <tr><td><strong>Tanggal</strong></td><td><strong>Jumlah</strong></td></tr>
    <?= renderRows($analytics['trend']['labels'], $analytics['trend']['data']) ?>
</table>

<h3 style="margin: 14px 0 6px; font-size: 14px;">2. Komposisi Jenis Kejadian</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; margin-bottom: 12px;">
    <tr><td><strong>Jenis</strong></td><td><strong>Jumlah</strong></td></tr>
    <?= renderRows($analytics['incident_type']['labels'], $analytics['incident_type']['data']) ?>
</table>

<h3 style="margin: 14px 0 6px; font-size: 14px;">3. Distribusi Status</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; margin-bottom: 12px;">
    <tr><td><strong>Status</strong></td><td><strong>Jumlah</strong></td></tr>
    <?= renderRows($analytics['status']['labels'], $analytics['status']['data']) ?>
</table>

<h3 style="margin: 14px 0 6px; font-size: 14px;">4. Top Lokasi Kejadian</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; margin-bottom: 12px;">
    <tr><td><strong>Lokasi</strong></td><td><strong>Jumlah</strong></td></tr>
    <?= renderRows($analytics['top_locations']['labels'], $analytics['top_locations']['data']) ?>
</table>

<h3 style="margin: 14px 0 6px; font-size: 14px;">5. Korban vs Non-Korban</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; margin-bottom: 12px;">
    <tr><td><strong>Kategori</strong></td><td><strong>Jumlah</strong></td></tr>
    <?= renderRows($analytics['victim']['labels'], $analytics['victim']['data']) ?>
</table>

<h3 style="margin: 14px 0 6px; font-size: 14px;">6. Kerusakan Sarpras vs Tidak</h3>
<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px;">
    <tr><td><strong>Kategori</strong></td><td><strong>Jumlah</strong></td></tr>
    <?= renderRows($analytics['damage']['labels'], $analytics['damage']['data']) ?>
</table>
