<?php

use common\models\Report;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $reportNumber */
/** @var Report|null $report */
/** @var bool $searched */

$this->title = 'Cek Progres Laporan';

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

$normalizedStatus = $report ? (string) ($statusAliases[$report->status] ?? $report->status) : '';
$statusToStage = [];
foreach ($timelineSteps as $stageNumber => $step) {
    $statusToStage[(string) $step['status']] = $stageNumber;
}
$currentStage = $statusToStage[$normalizedStatus] ?? 1;
?>

<div class="report-track">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Masukkan nomor laporan untuk melihat progres status laporan Anda.</p>

    <?= Html::beginForm(['track'], 'post', ['class' => 'mb-4']) ?>
    <div class="input-group">
        <input
            type="text"
            class="form-control"
            name="report_number"
            value="<?= Html::encode($reportNumber) ?>"
            placeholder="Contoh: K3L/20260718-00001"
            required
        >
        <button class="btn btn-primary" type="submit">Cek Progres</button>
    </div>
    <?= Html::endForm() ?>

    <?php if ($searched && $report === null): ?>
        <div class="alert alert-warning">Nomor laporan tidak ditemukan.</div>
    <?php endif; ?>

    <?php if ($report !== null): ?>
        <div class="card mb-3">
            <div class="card-header"><strong>Informasi Laporan</strong></div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <tr>
                        <th width="240">Nomor Laporan</th>
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
                        <th>Dibuat</th>
                        <td><?= date('d-m-Y H:i', (int) $report->created_at) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Timeline Status</strong></div>
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
    <?php endif; ?>
</div>
