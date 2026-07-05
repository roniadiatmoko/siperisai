<?php

use common\models\Report;
use yii\helpers\Html;
use kartik\select2\Select2;
use kartik\depdrop\DepDrop;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Report $model */
/** @var array $users */

$this->title = 'Laporan ' . $model->report_number;
$this->params['breadcrumbs'][] = ['label' => 'Daftar Laporan', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\JqueryAsset::register($this);

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

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
$isSecretaryEditable = in_array($model->status, [Report::STATUS_SUBMITTED, Report::STATUS_SECRETARY_REVIEW], true);
?>

<div class="report-view">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?= Html::a('Kembali', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                <?php if (Yii::$app->user->can('reviewReport')): ?>
                    <?php Html::a('Preview', ['preview', 'id' => $model->id], ['class' => 'btn btn-outline-primary btn-sm']) ?>
                <?php endif; ?>
                <?php if (Yii::$app->user->can('sendTelegramNotification') && in_array($model->status, [Report::STATUS_SECRETARY_FINALIZED], true)): ?>
                    <?= Html::beginForm(['send-coordinator', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
                    <?= Html::submitButton('Kirim ke Koordinator via Telegram', ['class' => 'btn btn-info btn-sm']) ?>
                    <?= Html::endForm() ?>
                <?php
                endif;
                // if status = 4 then show "notifikasi sudah dikirim ke koordinator"
                if (Yii::$app->user->can('sendTelegramNotification') && in_array($model->status, [Report::STATUS_COORDINATOR_FOLLOW_UP], true)): ?>
                    <span class="badge bg-success">Notifikasi sudah dikirim ke koordinator</span>
                <?php endif; ?>


                <?php if (Yii::$app->user->can('generateReportPdf') && in_array($model->status, [Report::STATUS_SECRETARY_FINALIZED, Report::STATUS_COORDINATOR_FOLLOW_UP, Report::STATUS_CLOSED], true)): ?>
                    <?= Html::a('Download PDF', ['pdf', 'id' => $model->id], ['class' => 'btn btn-warning btn-sm']) ?>
                <?php endif; ?>
                <?php if (Yii::$app->user->can('followUpReport') && in_array($model->status, [Report::STATUS_SECRETARY_FINALIZED, Report::STATUS_COORDINATOR_FOLLOW_UP], true)): ?>
                    <?= Html::a('Isi Tindak Lanjut', ['follow-up', 'id' => $model->id], ['class' => 'btn btn-dark btn-sm']) ?>
                <?php endif; ?>
            </div>

            <table class="table table-bordered align-middle">
                <tr>
                    <th>No Laporan</th>
                    <td><?= Html::encode($model->report_number) ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><?= Html::encode($statusLabels[$model->status] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Lokasi</th>
                    <td><?= Html::encode($model->location ? $model->location->name : '-') ?></td>
                </tr>
                <tr>
                    <th>Pelapor</th>
                    <td><?= Html::encode($model->reporter ? $model->reporter->username : ($model->reporter_name ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Laporan Anonim</th>
                    <td><?= $model->is_anonymous ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Waktu Kejadian</th>
                    <td><?= date('d-m-Y H:i', (int) $model->incident_time) ?></td>
                </tr>
                <tr>
                    <th>Waktu Pelaporan</th>
                    <td><?= date('d-m-Y H:i', (int) $model->created_at) ?></td>
                </tr>
                <tr>
                    <th>Deskripsi</th>
                    <td><?= nl2br(Html::encode($model->description)) ?></td>
                </tr>
                <tr>
                    <th>Ada Korban</th>
                    <td><?= $model->has_victim ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Nama Korban</th>
                    <td><?= Html::encode($model->victim_name ?: '-') ?></td>
                </tr>
                <tr>
                    <th>Kondisi Korban</th>
                    <td><?= Html::encode($victimConditionLabels[$model->victim_condition] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Detail Kondisi Korban</th>
                    <td><?= nl2br(Html::encode($model->victim_condition_detail ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Ada Kerusakan Sarpras</th>
                    <td><?= $model->has_property_damage ? 'Ya' : 'Tidak' ?></td>
                </tr>
                <tr>
                    <th>Detail Kerusakan</th>
                    <td><?= nl2br(Html::encode($model->property_damage_detail ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Saksi Kejadian</th>
                    <td><?= nl2br(Html::encode($model->witness ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Catatan Pelapor</th>
                    <td><?= nl2br(Html::encode($model->additional_notes ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Jenis Kejadian</th>
                    <td><?= Html::encode($model->incident_type ? $model->incidentType->incidentCategory->name . ' - ' . $model->incidentType->name : '-') ?></td>
                </tr>
                <tr>
                    <th>Rekomendasi</th>
                    <td><?= nl2br(Html::encode($model->recommendation ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>PIC</th>
                    <td><?= Html::encode($model->picUser ? $model->picUser->username : '-') ?></td>
                </tr>
                <tr>
                    <th>Data yang Kurang</th>
                    <td><?= nl2br(Html::encode($model->missing_data_note ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>Tindak Lanjut Koordinator</th>
                    <td><?= nl2br(Html::encode($model->coordinator_follow_up_note ?: '-')) ?></td>
                </tr>
            </table>

            <?php if (Yii::$app->user->can('reviewReport') && $isSecretaryEditable): ?>
                <div class="card card-outline card-success mb-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Hasil Telaah Laporan</h3>
                    </div>
                    <div class="card-body">
                        <?= Html::beginForm(['secretary', 'id' => $model->id], 'post') ?>
                        <div class="mb-3">
                            <label class="form-label">Kategori Pelaporan</label>

                            <select id="incident_category_id"
                                name="incident_category_id"
                                class="form-select"
                                required>
                                <option value="">Pilih kategori</option>

                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category->id ?>">
                                        <?= Html::encode($category->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Kejadian</label>

                            <select id="incident_type_id"
                                name="incident_type_id"
                                class="form-select"
                                required>
                                <option value="">Pilih jenis kejadian</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="recommendation">Rekomendasi perbaikan</label>
                            <textarea id="recommendation" class="form-control" name="recommendation" rows="4" required><?= Html::encode($model->recommendation) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pic_user_id">PIC tindak lanjut</label>
                            <?= Html::dropDownList('pic_user_id', $model->pic_user_id, $users, ['id' => 'pic_user_id', 'class' => 'form-select', 'prompt' => 'Pilih PIC', 'required' => true]) ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="missing_data_note">Data yang kurang (opsional)</label>
                            <textarea id="missing_data_note" class="form-control" name="missing_data_note" rows="3"><?= Html::encode($model->missing_data_note) ?></textarea>
                        </div>
                        <?= Html::submitButton('Preview', ['class' => 'btn btn-primary']) ?>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->user->can('approveReport') && $model->status === Report::STATUS_TEAM_APPROVED): ?>
                <div class="card card-outline card-warning mb-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Keputusan Ketua Tim K3L</h3>
                    </div>
                    <div class="card-body">
                        <?= Html::beginForm(['approve', 'id' => $model->id], 'post') ?>
                        <div class="mb-3">
                            <label class="form-label" for="approval_note">Catatan</label>
                            <textarea id="approval_note" class="form-control" name="approval_note" rows="3" placeholder="Tulis catatan persetujuan atau alasan tidak disetujui"></textarea>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <?= Html::submitButton('Setujui', ['class' => 'btn btn-success', 'name' => 'decision', 'value' => 'approved']) ?>
                            <?= Html::submitButton('Tidak Disetujui', ['class' => 'btn btn-outline-danger', 'name' => 'decision', 'value' => 'rejected']) ?>
                        </div>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-6">
                    <h5 class="mb-3">Lampiran</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($model->attachments as $attachment): ?>
                            <li class="list-group-item px-0">
                                <?= Html::a(
                                    Html::encode($attachment->original_name),
                                    Yii::$app->params['app.uploadUrl'] . '/' . ltrim($attachment->file_path, '/'),
                                    ['target' => '_blank']
                                ) ?>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($model->attachments)): ?>
                            <li class="list-group-item px-0">Tidak ada lampiran</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <h5 class="mb-3">Riwayat Status</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($model->statusHistories as $history): ?>
                            <li class="list-group-item px-0">
                                <?= date('d-m-Y H:i', (int) $history->created_at) ?>
                                - <?= Html::encode(($history->status_from ?: '-') . ' -> ' . $history->status_to) ?>
                                <?php if (!empty($history->note)): ?>
                                    (<?= Html::encode($history->note) ?>)
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($model->statusHistories)): ?>
                            <li class="list-group-item px-0">Belum ada histori status.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

$url = Url::to(['report/incident-type-list']);

$this->registerJs(<<<JS

const incidentUrl = '{$url}';

$(function () {

    $('#incident_category_id').select2({
        width: '100%',
        placeholder: 'Pilih kategori'
    });

    $('#incident_type_id').select2({
        width: '100%',
        placeholder: 'Pilih jenis kejadian'
    });

    $('#incident_category_id').on('change', function () {

        const categoryId = $(this).val();

        $('#incident_type_id')
            .empty()
            .append(new Option('Pilih jenis kejadian', ''));

        if (!categoryId) {
            $('#incident_type_id').trigger('change');
            return;
        }

        $.ajax({
            url: incidentUrl,
            type: 'GET',
            dataType: 'json',
            data: {
                id: categoryId
            },
            success: function (data) {

                $.each(data, function (i, item) {

                    $('#incident_type_id').append(
                        new Option(item.text, item.id)
                    );

                });

                $('#incident_type_id').trigger('change');
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data jenis kejadian.');
            }
        });

    });

});

JS);
?>