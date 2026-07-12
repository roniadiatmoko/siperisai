<?php

use common\models\Report;
use yii\helpers\Html;

/** @var common\models\Report $model */

$box = static function ($checked) {
    return $checked ? '[X]' : '[ ]';
};

$normalize = static function ($value) {
    $value = strtolower((string) $value);
    return preg_replace('/\s+/', ' ', trim($value));
};

$contains = static function ($haystack, array $needles) use ($normalize) {
    $haystack = $normalize($haystack);
    foreach ($needles as $needle) {
        if (str_contains($haystack, $normalize($needle))) {
            return true;
        }
    }
    return false;
};

$categoryName = $model->incidentType && $model->incidentType->incidentCategory
    ? (string) $model->incidentType->incidentCategory->name
    : '';
$incidentTypeName = $model->incidentType ? (string) $model->incidentType->name : '';

$isPelaporanBahaya = $contains($categoryName, ['bahaya']);
$isSakitAkibatKerja = $contains($categoryName, ['sakit akibat kerja', 'penyakit akibat kerja', 'pak', 'medis']);
$isInsidenK3 = $contains($categoryName, ['insiden', 'k3', 'kecelakaan', 'near miss', 'nearmiss', 'hampir celaka']);

$isKondisiTidakAman = $contains($incidentTypeName, ['kondisi tidak aman']);
$isPerilakuTidakAman = $contains($incidentTypeName, ['perilaku tidak aman']);
$isPAK = $contains($incidentTypeName, ['penyakit akibat kerja', 'pak']);
$isGawatMedis = $contains($incidentTypeName, ['kegawatdaruratan medis', 'gawat darurat', 'medis']);
$isNearMiss = $contains($incidentTypeName, ['hampir celaka', 'near miss', 'nearmiss']);
$isKecelakaanKerja = $contains($incidentTypeName, ['kecelakaan kerja']);

$isVictimConscious = (string) $model->victim_condition === Report::VICTIM_CONDITION_CONSCIOUS;
$isVictimUnconscious = (string) $model->victim_condition === Report::VICTIM_CONDITION_UNCONSCIOUS;
$isVictimInjured = (string) $model->victim_condition === Report::VICTIM_CONDITION_INJURED;
$isVictimNotInjured = (string) $model->victim_condition === Report::VICTIM_CONDITION_NOT_INJURED;

$isCauseDirect = (string) $model->cause_group === Report::CAUSE_GROUP_DIRECT;
$isCauseIndirect = (string) $model->cause_group === Report::CAUSE_GROUP_INDIRECT;
$isCauseUnsafeCondition = (string) $model->cause_subtype === Report::CAUSE_SUBTYPE_UNSAFE_CONDITION;
$isCauseUnsafeBehavior = (string) $model->cause_subtype === Report::CAUSE_SUBTYPE_UNSAFE_BEHAVIOR;
$isCausePersonal = (string) $model->cause_subtype === Report::CAUSE_SUBTYPE_PERSONAL;
$isCauseWork = (string) $model->cause_subtype === Report::CAUSE_SUBTYPE_WORK;

$isValidVerification = (string) $model->status !== Report::STATUS_NOT_APPROVED;

$uploadBasePath = Yii::getAlias(Yii::$app->params['app.uploadPath']);
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$imagePaths = [];
$nonImageFiles = [];

foreach ($model->attachments as $attachment) {
    $relative = ltrim((string) $attachment->file_path, '/');
    $fullPath = rtrim($uploadBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $ext = strtolower(pathinfo((string) $attachment->original_name, PATHINFO_EXTENSION));

    if (in_array($ext, $imageExtensions, true) && is_file($fullPath)) {
        $imagePaths[] = $fullPath;
    } else {
        $nonImageFiles[] = (string) $attachment->original_name;
    }
}

$detailLokasiText = trim((string) $model->detail_lokasi);
$lokasiText = trim((string) ($model->location ? $model->location->name : '-'));
if ($detailLokasiText !== '') {
    $lokasiText .= ' - ' . $detailLokasiText;
}

$waktuKejadianTanggal = date('d / m / Y', (int) $model->incident_time);
$waktuKejadianJam = date('H:i', (int) $model->incident_time);

?>

<style>
    body { font-family: serif; font-size: 11px; color: #111; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #8f8f8f; padding: 6px; vertical-align: top; }
    .no-border td { border: none; padding: 0; }
    .title { text-align: center; font-weight: bold; font-size: 33px; line-height: 1.25; margin-bottom: 12px; }
    .label-cell { width: 30%; font-weight: bold; background: #f4f4f4; }
    .section-title { margin-top: 12px; margin-bottom: 0; background: #154f61; color: #fff; font-weight: bold; padding: 5px 8px; font-size: 12px; }
    .hint { font-style: italic; color: #6f6f6f; font-size: 10px; }
    .line-space { min-height: 38px; }
    .line-space-lg { min-height: 62px; }
    .checkbox-line { margin-right: 14px; white-space: nowrap; }
    .img-box { width: 48%; border: 1px solid #8f8f8f; padding: 4px; margin: 4px 1%; display: inline-block; text-align: center; }
    .img-box img { max-width: 100%; max-height: 150px; }
    .signature td { text-align: center; height: 76px; }
</style>

<div class="title">
    FORMULIR<br>
    PELAPORAN BAHAYA, KECELAKAAN KERJA DAN PENYAKIT AKIBAT KERJA<br>
    BALAI KESELAMATAN DAN KESEHATAN KERJA<br>
    DISNAKERTRANS PROVINSI JAWA TENGAH
</div>

<table>
    <tr>
        <td class="label-cell" style="width:20%;">No. Laporan</td>
        <td style="width:30%;"><?= Html::encode($model->report_number ?: '(diisi otomatis sistem)') ?></td>
        <td class="label-cell" style="width:20%;">Tanggal Cetak</td>
        <td style="width:30%;"><?= date('d-m-Y') ?></td>
    </tr>
</table>

<div class="section-title">BAGIAN I &mdash; DIISI OLEH PELAPOR</div>
<table>
    <tr>
        <td class="label-cell">1. Lokasi Kejadian</td>
        <td><?= Html::encode($lokasiText) ?></td>
    </tr>
    <tr>
        <td class="label-cell">2. Waktu Kejadian</td>
        <td>Tanggal: <?= Html::encode($waktuKejadianTanggal) ?> &nbsp;&nbsp;&nbsp; Jam: <?= Html::encode($waktuKejadianJam) ?> WIB</td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>3. Kronologi Kejadian (jelaskan singkat kejadian yang dilihat/dialami)</strong>
            <div class="line-space-lg"><?= nl2br(Html::encode($model->description ?: '')) ?></div>
        </td>
    </tr>
    <tr>
        <td class="label-cell">4. Apakah Ada Korban?</td>
        <td>
            <?= Html::encode((int) $model->has_victim === 1 ? 'Ya' : 'Tidak') ?>
        </td>
    </tr>
    <tr>
        <td class="label-cell">Nama Korban</td>
        <td><?= Html::encode($model->victim_name ?: '-') ?></td>
    </tr>
    <tr>
        <td class="label-cell">Kondisi Korban</td>
        <td>
            <?php
                $kondisiKorban = '';
                if ($isVictimConscious) {
                    $kondisiKorban = 'Sadar';
                } elseif ($isVictimUnconscious) {
                    $kondisiKorban = 'Tidak Sadar';
                } elseif ($isVictimNotInjured) {
                    $kondisiKorban = 'Tidak Cidera';
                } elseif ($isVictimInjured) {
                    $kondisiKorban = 'Cidera / Luka / Sakit';
                }
            ?>
            <?= Html::encode($kondisiKorban ?: '-') ?><?php if ($kondisiKorban && $model->victim_condition_detail): ?> - <?= Html::encode($model->victim_condition_detail) ?><?php endif; ?>
        </td>
    </tr>
    <tr>
        <td class="label-cell">5. Kerusakan Sarana/Prasarana?</td>
        <td>
            <?= Html::encode((int) $model->has_property_damage === 1 ? 'Ya' : 'Tidak') ?><?php if ((int) $model->has_property_damage === 1 && $model->property_damage_detail): ?> - <?= Html::encode($model->property_damage_detail) ?><?php endif; ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>6. Saksi Kejadian (jika ada)</strong>
            <div class="line-space"><?= nl2br(Html::encode($model->witness ?: '-')) ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>7. Lampiran Foto Kejadian</strong>
            <div class="hint">Foto lampiran tercetak otomatis dari sistem.</div>
            <div>
                <?php if (!empty($imagePaths)): ?>
                    <?php foreach ($imagePaths as $imagePath): ?>
                        <div class="img-box">
                            <img src="<?= Html::encode($imagePath) ?>" alt="Lampiran">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="line-space">Tidak ada lampiran foto.</div>
                <?php endif; ?>
            </div>
            <?php if (!empty($nonImageFiles)): ?>
                <div><strong>Lampiran non-gambar:</strong> <?= Html::encode(implode(', ', $nonImageFiles)) ?></div>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>8. Catatan Lain-lain / Usulan Perbaikan</strong>
            <div class="line-space"><?= nl2br(Html::encode($model->additional_notes ?: '-')) ?></div>
        </td>
    </tr>
    <tr>
        <td class="label-cell">9. Nama Pelapor</td>
        <td>
            <?= Html::encode((int) $model->is_anonymous === 1 ? 'Anonim' : ($model->reporter ? $model->reporter->username : ($model->reporter_name ?: '-'))) ?>
        </td>
    </tr>
</table>

<div class="section-title">BAGIAN II &mdash; DIISI OLEH SEKRETARIS K3L (VERIFIKASI &amp; TINDAK LANJUT)</div>
<table>
    <tr>
        <td colspan="2">
            <strong>a. Jenis Kejadian</strong><br>
            <?php
                $jenisKejadian = '';
                if ($isPelaporanBahaya) {
                    $jenisKejadian = 'Pelaporan Bahaya';
                    if ($isKondisiTidakAman) {
                        $jenisKejadian .= ' - Kondisi Tidak Aman';
                    } elseif ($isPerilakuTidakAman) {
                        $jenisKejadian .= ' - Perilaku Tidak Aman';
                    }
                } elseif ($isSakitAkibatKerja) {
                    $jenisKejadian = 'Sakit Akibat Kerja';
                    if ($isPAK) {
                        $jenisKejadian .= ' - Penyakit Akibat Kerja (PAK)';
                    } elseif ($isGawatMedis) {
                        $jenisKejadian .= ' - Kegawatdaruratan Medis';
                    }
                } elseif ($isInsidenK3) {
                    $jenisKejadian = 'Insiden K3';
                    if ($isNearMiss) {
                        $jenisKejadian .= ' - Hampir Celaka (Nearmiss)';
                    } elseif ($isKecelakaanKerja) {
                        $jenisKejadian .= ' - Kecelakaan Kerja';
                    }
                }
            ?>
            <?= Html::encode($jenisKejadian ?: '-') ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>b. Penyebab Kejadian</strong><br>
            <?php
                $penyebabKejadian = '';
                if ($isCauseDirect) {
                    $penyebabKejadian = 'Penyebab Langsung';
                    if ($isCauseUnsafeCondition) {
                        $penyebabKejadian .= ' - Kondisi Tidak Aman';
                    } elseif ($isCauseUnsafeBehavior) {
                        $penyebabKejadian .= ' - Perilaku Tidak Aman';
                    }
                } elseif ($isCauseIndirect) {
                    $penyebabKejadian = 'Penyebab Tidak Langsung';
                    if ($isCausePersonal) {
                        $penyebabKejadian .= ' - Personal / Pribadi';
                    } elseif ($isCauseWork) {
                        $penyebabKejadian .= ' - Pekerjaan';
                    }
                }
            ?>
            <?= Html::encode($penyebabKejadian ?: '-') ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>c. Rekomendasi Perbaikan</strong>
            <div class="line-space-lg"><?= nl2br(Html::encode($model->recommendation ?: '-')) ?></div>
        </td>
    </tr>
    <tr>
        <td class="label-cell">d. Verifikasi Laporan</td>
        <td>
            <span class="checkbox-line"><?= $box($isValidVerification) ?> Valid</span>
            <span class="checkbox-line"><?= $box(!$isValidVerification) ?> Tidak Valid</span>
        </td>
    </tr>
    <tr>
        <td class="label-cell">e. PIC Tindak Lanjut</td>
        <td><?= Html::encode($model->getPicDisplayLabel()) ?></td>
    </tr>
</table>

<table class="signature" style="margin-top: 12px;">
    <tr>
        <td width="33%">Pelapor,<br><br><br><br><?= Html::encode((int) $model->is_anonymous === 1 ? 'Anonim' : ($model->reporter ? $model->reporter->username : ($model->reporter_name ?: '-'))) ?></td>
        <td width="34%">Diverifikasi oleh,<br><em>Sekretaris Tim K3L</em><br><br><br><?= $model->secretary_finalized_at ? date('d-m-Y H:i:s', $model->secretary_finalized_at) : '(________________________)' ?></td>
        <td width="33%">Disetujui oleh,<br><em>Ketua Tim K3L</em><br><br><br><?= $model->team_lead_approved_at ? date('d-m-Y H:i:s', $model->team_lead_approved_at) : '(________________________)' ?></td>
    </tr>
</table>