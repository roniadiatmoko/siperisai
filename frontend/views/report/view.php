<?php

use common\models\Report;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var Report $report */

$this->title = 'Detail Laporan ' . $report->report_number;
$this->params['breadcrumbs'][] = ['label' => 'Mulai Pelaporan', 'url' => ['start']];
$this->params['breadcrumbs'][] = $this->title;

$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$victimConditionOptions = Report::victimConditionOptions();

$timelineSteps = [
    1 => ['status' => Report::STATUS_SUBMITTED, 'label' => 'Dikirimkan ke Sekretaris'],
    2 => ['status' => Report::STATUS_TEAM_APPROVED, 'label' => 'Dikirimkan ke Ketua Tim K3L'],
    3 => ['status' => Report::STATUS_SECRETARY_FINALIZED, 'label' => 'Finalisasi Tindakan Sekretaris'],
    4 => ['status' => Report::STATUS_COORDINATOR_FOLLOW_UP, 'label' => 'Tindak Lanjut Koordinator Bidang'],
];

$statusAliases = [
    Report::STATUS_SECRETARY_REVIEW => Report::STATUS_SUBMITTED,
    Report::STATUS_CLOSED => Report::STATUS_COORDINATOR_FOLLOW_UP,
];

$normalizedStatus = (string) ($statusAliases[$report->status] ?? $report->status);
$statusToStage = [];
foreach ($timelineSteps as $stageNumber => $step) {
    $statusToStage[(string) $step['status']] = $stageNumber;
}

$currentStage = $statusToStage[$normalizedStatus] ?? 1;
$statusLabel = $timelineSteps[$currentStage]['label'] ?? '-';

$reporterDisplay = ((int) $report->is_anonymous === 1)
    ? 'Anonim'
    : ($report->reporter_name ?: ($report->reporter ? $report->reporter->username : '-'));
?>

<div class="report-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Kembali ke Daftar', ['start'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Timeline Status Laporan</strong></div>
        <div class="card-body">
            <div class="report-status-timeline">
                <?php foreach ($timelineSteps as $stageNumber => $step): ?>
                    <?php
                    $isActive = $stageNumber <= $currentStage;
                    $isCurrent = (string) $step['status'] === $normalizedStatus;
                    $stepClass = 'report-status-step ' . ($isActive ? 'is-active' : 'is-pending') . ($isCurrent ? ' report-status-current' : '');
                    ?>
                    <div class="<?= Html::encode($stepClass) ?>">
                        <div class="report-status-step__dot"><?= $stageNumber ?></div>
                        <div class="report-status-step__label"><?= Html::encode($step['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Detail Laporan</strong></div>
        <div class="card-body">
            <table class="table table-sm table-bordered mb-0">
                <tr>
                    <th width="220">No Laporan</th>
                    <td><?= Html::encode($report->report_number) ?></td>
                </tr>
                <tr>
                    <th>Lokasi</th>
                    <td><?= Html::encode($report->location ? $report->location->name : '-') ?></td>
                </tr>
                <tr>
                    <th>Detail Lokasi</th>
                    <td><?= nl2br(Html::encode($report->detail_lokasi ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Waktu Kejadian</th>
                    <td><?= date('d-m-Y H:i', (int) $report->incident_time) ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="badge bg-secondary"><?= Html::encode($statusLabel) ?></span></td>
                </tr>
                <tr>
                    <th>Nama Pelapor</th>
                    <td><?= Html::encode($reporterDisplay) ?></td>
                </tr>
                <tr>
                    <th>Laporan Anonim</th>
                    <td><?= (int) $report->is_anonymous === 1 ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Deskripsi</th>
                    <td><?= nl2br(Html::encode($report->description)) ?></td>
                </tr>
                <tr>
                    <th>Jenis Kejadian</th>
                    <td><?= Html::encode($report->incident_type ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Rekomendasi</th>
                    <td><?= nl2br(Html::encode($report->recommendation ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Apakah Ada Korban?</th>
                    <td><?= (int) $report->has_victim === 1 ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Nama Korban</th>
                    <td><?= Html::encode($report->victim_name ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Kondisi Korban</th>
                    <td>
                        <?= Html::encode(!empty($report->victim_condition) && isset($victimConditionOptions[$report->victim_condition])
                            ? $victimConditionOptions[$report->victim_condition]
                            : '-') ?>
                    </td>
                </tr>
                <tr>
                    <th>Bagian Cedera/Luka/Sakit</th>
                    <td><?= nl2br(Html::encode($report->victim_condition_detail ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Apakah Ada Kerusakan Sarana/Prasarana?</th>
                    <td><?= (int) $report->has_property_damage === 1 ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Detail Kerusakan</th>
                    <td><?= nl2br(Html::encode($report->property_damage_detail ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Saksi Kejadian</th>
                    <td><?= nl2br(Html::encode($report->witness ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Catatan Lain-Lain</th>
                    <td><?= nl2br(Html::encode($report->additional_notes ?: '-')) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Foto / Dokumen Terunggah</strong></div>
        <div class="card-body">
            <?php if (!empty($report->attachments)): ?>
                <div class="row g-3">
                    <?php foreach ($report->attachments as $attachment): ?>
                        <?php
                        $fileUrl = Yii::$app->params['app.uploadUrl'] . '/' . ltrim($attachment->file_path, '/');
                        $extension = strtolower(pathinfo((string) $attachment->original_name, PATHINFO_EXTENSION));
                        $isImage = in_array($extension, $imageExtensions, true);
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded p-2 h-100">
                                <?php if ($isImage): ?>
                                    <div class="mb-2 text-center">
                                        <img src="<?= Html::encode($fileUrl) ?>" alt="<?= Html::encode($attachment->original_name) ?>" style="max-width:100%;max-height:220px;object-fit:contain;" />
                                    </div>
                                <?php else: ?>
                                    <div class="mb-2 text-center text-muted" style="font-size:40px;">DOC</div>
                                <?php endif; ?>
                                <div class="small mb-1"><strong><?= Html::encode($attachment->original_name) ?></strong></div>
                                <div class="small text-muted mb-2"><?= Html::encode($attachment->mime_type ?: '-') ?></div>
                                <?= Html::a('Lihat / Download', $fileUrl, ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Tidak ada lampiran yang diunggah.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Riwayat Status</strong></div>
        <div class="card-body">
            <?php if (!empty($report->statusHistories)): ?>
                <ul class="mb-0">
                    <?php foreach ($report->statusHistories as $history): ?>
                        <li>
                            <?= date('d-m-Y H:i', (int) $history->created_at) ?>
                            - <?= Html::encode(($history->status_from ?: '-') . ' -> ' . $history->status_to) ?>
                            <?php if (!empty($history->note)): ?>
                                (<?= Html::encode($history->note) ?>)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0">Belum ada riwayat status.</p>
            <?php endif; ?>
        </div>
    </div>
</div>