<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $draft */
/** @var common\models\Location|null $selectedLocation */
/** @var array $victimConditionOptions */

$this->title = 'Preview Laporan';
$form = isset($draft['form']) && is_array($draft['form']) ? $draft['form'] : [];
$attachments = isset($draft['attachments']) && is_array($draft['attachments']) ? $draft['attachments'] : [];
$uploadBaseUrl = rtrim((string) Yii::$app->params['app.uploadUrl'], '/');
?>

<div class="report-preview">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Kembali Edit', ['create', 'restore' => 1], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="alert alert-info">
        Periksa kembali semua data. Laporan akan disimpan ke database setelah Anda menekan tombol FINALISASI LAPORAN.
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Ringkasan Laporan</strong></div>
        <div class="card-body">
            <table class="table table-sm table-bordered mb-0">
                <tr>
                    <th width="280">Lokasi</th>
                    <td><?= Html::encode($selectedLocation ? $selectedLocation->name : '-') ?></td>
                </tr>
                <tr>
                    <th>Detail Lokasi</th>
                    <td><?= nl2br(Html::encode((string) (($form['detail_lokasi'] ?? '') !== '' ? $form['detail_lokasi'] : '-'))) ?></td>
                </tr>
                <tr>
                    <th>Waktu Kejadian</th>
                    <td><?= Html::encode((string) ($form['incident_time_input'] ?? '-')) ?></td>
                </tr>
                <tr>
                    <th>Deskripsi Kejadian</th>
                    <td><?= nl2br(Html::encode((string) ($form['description'] ?? '-'))) ?></td>
                </tr>
                <tr>
                    <th>Ada Korban</th>
                    <td><?= ((int) ($form['has_victim'] ?? 0) === 1) ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Nama Korban</th>
                    <td><?= Html::encode((string) (($form['victim_name'] ?? '') !== '' ? $form['victim_name'] : '-')) ?></td>
                </tr>
                <tr>
                    <th>Kondisi Korban</th>
                    <td>
                        <?php
                        $victimCondition = (string) ($form['victim_condition'] ?? '');
                        echo Html::encode($victimCondition !== '' && isset($victimConditionOptions[$victimCondition])
                            ? $victimConditionOptions[$victimCondition]
                            : '-');
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Detail Kondisi Korban</th>
                    <td><?= nl2br(Html::encode((string) (($form['victim_condition_detail'] ?? '') !== '' ? $form['victim_condition_detail'] : '-'))) ?></td>
                </tr>
                <tr>
                    <th>Ada Kerusakan Sarana/Prasarana</th>
                    <td><?= ((int) ($form['has_property_damage'] ?? 0) === 1) ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Detail Kerusakan</th>
                    <td><?= nl2br(Html::encode((string) (($form['property_damage_detail'] ?? '') !== '' ? $form['property_damage_detail'] : '-'))) ?></td>
                </tr>
                <tr>
                    <th>Saksi</th>
                    <td><?= nl2br(Html::encode((string) (($form['witness'] ?? '') !== '' ? $form['witness'] : '-'))) ?></td>
                </tr>
                <tr>
                    <th>Catatan Lain-Lain</th>
                    <td><?= nl2br(Html::encode((string) (($form['additional_notes'] ?? '') !== '' ? $form['additional_notes'] : '-'))) ?></td>
                </tr>
                <tr>
                    <th>Laporan Anonim</th>
                    <td><?= ((int) ($form['is_anonymous'] ?? 0) === 1) ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Nama Pelapor</th>
                    <td><?= Html::encode((string) (($form['reporter_name'] ?? '') !== '' ? $form['reporter_name'] : 'Anonim')) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Lampiran Foto</strong></div>
        <div class="card-body">
            <?php if (!empty($attachments)): ?>
                <div class="row g-3">
                    <?php foreach ($attachments as $attachment): ?>
                        <?php
                        $tmpPath = (string) ($attachment['tmp_path'] ?? '');
                        $fileUrl = $uploadBaseUrl . '/' . ltrim($tmpPath, '/');
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded p-2 h-100">
                                <div class="mb-2 text-center">
                                    <img src="<?= Html::encode($fileUrl) ?>" alt="<?= Html::encode((string) ($attachment['original_name'] ?? 'Lampiran')) ?>" style="max-width:100%;max-height:220px;object-fit:contain;" />
                                </div>
                                <div class="small"><strong><?= Html::encode((string) ($attachment['original_name'] ?? '-')) ?></strong></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Tidak ada lampiran foto.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <?= Html::a('Kembali Edit', ['create', 'restore' => 1], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::beginForm(['finalize'], 'post') ?>
        <?= Html::submitButton('FINALISASI LAPORAN', ['class' => 'btn btn-success']) ?>
        <?= Html::endForm() ?>
    </div>
</div>
