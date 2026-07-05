<?php

use common\models\Report;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var Report[] $reports */

$this->title = 'Mulai Pelaporan';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="report-start">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
        <span class="badge bg-primary">Pelapor</span>
    </div>

    <p class="text-muted">Berikut riwayat laporan yang sudah Anda kirim. Klik tombol di bawah untuk menambahkan laporan baru.</p>

    <p class="mb-3">
        <?= Html::a('Tambah Laporan Baru', ['create'], ['class' => 'btn btn-primary']) ?>
    </p>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>No Laporan</th>
                        <th>Lokasi</th>
                        <th>Waktu Kejadian</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($reports)): ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?= Html::encode($report->report_number) ?></td>
                                <td><?= Html::encode($report->location ? $report->location->name : '-') ?></td>
                                <td><?= date('d-m-Y H:i', (int) $report->incident_time) ?></td>
                                <td><span class="badge bg-secondary"><?= Html::encode($report->status) ?></span></td>
                                <td><?= date('d-m-Y H:i', (int) $report->created_at) ?></td>
                                <td>
                                    <?= Html::a('Detail', ['view', 'id' => $report->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada laporan. Klik "Tambah Laporan Baru" untuk membuat laporan pertama Anda.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>