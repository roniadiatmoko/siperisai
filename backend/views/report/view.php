<?php

use common\models\Report;
use yii\helpers\Html;
use kartik\select2\Select2;
use kartik\depdrop\DepDrop;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Report $model */

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

$statusLabels = Report::statusLabelOptions();

$victimConditionLabels = Report::victimConditionOptions();
$selectedIncidentTypeId = $model->incident_type ? (string) $model->incident_type : '';
$selectedCategoryId = ($model->incidentType && $model->incidentType->incidentCategory)
    ? (string) $model->incidentType->incidentCategory->id
    : '';
$causeGroupOptions = Report::causeGroupOptions();
$causeSubtypeOptionsByGroup = Report::causeSubtypeOptionsByGroup();
$picUnitOptions = Report::picUnitOptions();
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
                    <th>Detail Lokasi</th>
                    <td><?= nl2br(Html::encode($model->detail_lokasi ?: '-')) ?></td>
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
                    <th>Penyebab Kejadian</th>
                    <td><?= Html::encode($model->getCauseGroupLabel()) ?></td>
                </tr>
                <tr>
                    <th>Jenis Penyebab Kejadian</th>
                    <td><?= Html::encode($model->getCauseSubtypeLabel()) ?></td>
                </tr>
                <tr>
                    <th>Rekomendasi</th>
                    <td><?= nl2br(Html::encode($model->recommendation ?: '-')) ?></td>
                </tr>
                <tr>
                    <th>PIC</th>
                    <td><?= Html::encode($model->getPicDisplayLabel()) ?></td>
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
            <br/>
            <div class="row">
                <div class="col-lg-6">
                    <h5 class="mb-3">Lampiran</h5>
                    <?php $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp']; ?>
                    <div class="row g-3">
                        <?php foreach ($model->attachments as $attachment): ?>
                            <?php
                            $fileUrl = Url::to(['attachment', 'id' => $attachment->id]);
                            $extension = strtolower(pathinfo((string) $attachment->original_name, PATHINFO_EXTENSION));
                            $isImage = in_array($extension, $imageExtensions, true);
                            ?>
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <?php if ($isImage): ?>
                                        <div class="mb-2 text-center">
                                            <img src="<?= Html::encode($fileUrl) ?>"
                                                alt="<?= Html::encode($attachment->original_name) ?>"
                                                style="max-width:100%;max-height:180px;object-fit:contain;" />
                                        </div>
                                    <?php endif; ?>
                                    <div class="small mb-1"><strong><?= Html::encode($attachment->original_name) ?></strong></div>
                                    <div>
                                        <?= Html::a('Lihat Lampiran', $fileUrl, ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-primary']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($model->attachments)): ?>
                            <div class="col-12 text-muted">Tidak ada lampiran</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h5 class="mb-3">Riwayat Status</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($model->statusHistories as $history): ?>
                            <li class="list-group-item px-0">
                                <?= date('d-m-Y H:i', (int) $history->created_at) ?>
                                - <?= Html::encode(Report::statusLabel($history->status_from) . ' -> ' . Report::statusLabel($history->status_to)) ?>
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
            <hr/>
            <?php if (Yii::$app->user->can('reviewReport') && $isSecretaryEditable): ?>
                <div class="card card-outline card-info mb-4">
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
                            <label class="form-label">Penyebab Kejadian</label>
                            <select id="cause_group"
                                name="cause_group"
                                class="form-select"
                                required>
                                <option value="">Pilih penyebab kejadian</option>
                                <?php foreach ($causeGroupOptions as $groupValue => $groupLabel): ?>
                                    <option value="<?= Html::encode($groupValue) ?>" <?= $model->cause_group === $groupValue ? 'selected' : '' ?>>
                                        <?= Html::encode($groupLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Penyebab Kejadian</label>
                            <select id="cause_subtype"
                                name="cause_subtype"
                                class="form-select"
                                required>
                                <option value="">Pilih jenis penyebab kejadian</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="recommendation">Rekomendasi perbaikan</label>
                            <textarea id="recommendation" class="form-control" name="recommendation" rows="4" required><?= Html::encode($model->recommendation) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pic_unit">PIC Unit Kerja</label>
                            <?= Html::dropDownList('pic_unit', $model->pic_unit, $picUnitOptions, ['id' => 'pic_unit', 'class' => 'form-select', 'prompt' => 'Pilih unit kerja PIC', 'required' => true]) ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pic_name">Nama PIC</label>
                            <input type="text" id="pic_name" class="form-control" name="pic_name" value="<?= Html::encode($model->pic_name) ?>" placeholder="Tulis nama PIC" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="missing_data_note">Data yang kurang (opsional)</label>
                            <textarea id="missing_data_note" class="form-control" name="missing_data_note" rows="3"><?= Html::encode($model->missing_data_note) ?></textarea>
                        </div>
                        <hr>
                        <h5 class="mb-3">Perbaikan Inputan Pelapor</h5>
                        <div class="mb-3">
                            <label class="form-label" for="has_victim">Ada korban</label>
                            <?= Html::dropDownList('has_victim', (string) ((int) $model->has_victim), ['1' => 'Ya', '0' => 'Tidak'], ['id' => 'has_victim', 'class' => 'form-select', 'required' => true]) ?>
                        </div>
                        <div id="victim-fields">
                            <div class="mb-3">
                                <label class="form-label" for="victim_name">Nama korban</label>
                                <input type="text" id="victim_name" class="form-control" name="victim_name" value="<?= Html::encode($model->victim_name) ?>" placeholder="Tulis nama korban">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="victim_condition">Kondisi korban</label>
                                <?= Html::dropDownList('victim_condition', (string) ($model->victim_condition ?: ''), Report::victimConditionOptions(), ['id' => 'victim_condition', 'class' => 'form-select', 'prompt' => 'Pilih kondisi korban']) ?>
                            </div>
                            <div class="mb-3" id="victim-condition-detail-field">
                                <label class="form-label" for="victim_condition_detail">Detail kondisi korban</label>
                                <textarea id="victim_condition_detail" class="form-control" name="victim_condition_detail" rows="3"><?= Html::encode($model->victim_condition_detail) ?></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="has_property_damage">Ada kerusakan sarpras</label>
                            <?= Html::dropDownList('has_property_damage', (string) ((int) $model->has_property_damage), ['1' => 'Ya', '0' => 'Tidak'], ['id' => 'has_property_damage', 'class' => 'form-select', 'required' => true]) ?>
                        </div>
                        <div class="mb-3" id="property-damage-field">
                            <label class="form-label" for="property_damage_detail">Detail kerusakan</label>
                            <textarea id="property_damage_detail" class="form-control" name="property_damage_detail" rows="3"><?= Html::encode($model->property_damage_detail) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="witness">Saksi kejadian</label>
                            <textarea id="witness" class="form-control" name="witness" rows="3"><?= Html::encode($model->witness) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="additional_notes">Catatan pelapor</label>
                            <textarea id="additional_notes" class="form-control" name="additional_notes" rows="3"><?= Html::encode($model->additional_notes) ?></textarea>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <?= Html::submitButton('Preview', ['class' => 'btn btn-primary']) ?>
                        </div>
                        <?= Html::endForm() ?>

                        <div class="mt-2">
                            <?= Html::beginForm(['invalid', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
                            <?= Html::submitButton('Laporan tidak valid', [
                                'class' => 'btn btn-outline-danger',
                                'data-confirm' => 'Yakin menandai laporan ini sebagai tidak valid?',
                            ]) ?>
                            <?= Html::endForm() ?>
                        </div>
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

            
        </div>
    </div>
</div>

<?php

$url = Url::to(['report/incident-type-list']);
$selectedCategoryIdJs = \yii\helpers\Json::htmlEncode($selectedCategoryId);
$selectedIncidentTypeIdJs = \yii\helpers\Json::htmlEncode($selectedIncidentTypeId);
$causeSubtypeByGroupJs = \yii\helpers\Json::htmlEncode($causeSubtypeOptionsByGroup);
$selectedCauseGroupJs = \yii\helpers\Json::htmlEncode((string) ($model->cause_group ?: ''));
$selectedCauseSubtypeJs = \yii\helpers\Json::htmlEncode((string) ($model->cause_subtype ?: ''));

$this->registerJs(<<<JS

const incidentUrl = '{$url}';
const selectedCategoryId = {$selectedCategoryIdJs};
const selectedIncidentTypeId = {$selectedIncidentTypeIdJs};
const causeSubtypeByGroup = {$causeSubtypeByGroupJs};
const selectedCauseGroup = {$selectedCauseGroupJs};
const selectedCauseSubtype = {$selectedCauseSubtypeJs};

$(function () {

    let isInitialIncidentLoad = true;

    function refillCauseSubtype() {
        const causeGroup = $('#cause_group').val();
        const subtypeOptions = causeSubtypeByGroup[causeGroup] || {};
        const target = $('#cause_subtype');

        target.empty().append(new Option('Pilih jenis penyebab kejadian', ''));

        $.each(subtypeOptions, function (value, label) {
            target.append(new Option(label, value));
        });

        if (causeGroup === selectedCauseGroup && selectedCauseSubtype) {
            target.val(selectedCauseSubtype);
        }

        target.trigger('change');
    }

    $('#incident_category_id').select2({
        width: '100%',
        placeholder: 'Pilih kategori'
    });

    $('#incident_type_id').select2({
        width: '100%',
        placeholder: 'Pilih jenis kejadian'
    });

    $('#cause_group').select2({
        width: '100%',
        placeholder: 'Pilih penyebab kejadian'
    });

    $('#cause_subtype').select2({
        width: '100%',
        placeholder: 'Pilih jenis penyebab kejadian'
    });

    $('#pic_unit').select2({
        width: '100%',
        placeholder: 'Pilih unit kerja PIC'
    });

    $('#has_victim').select2({
        width: '100%'
    });

    $('#victim_condition').select2({
        width: '100%',
        placeholder: 'Pilih kondisi korban'
    });

    $('#has_property_damage').select2({
        width: '100%'
    });

    function toggleVictimConditionDetail() {
        const hasVictim = $('#has_victim').val() === '1';
        const isInjured = $('#victim_condition').val() === 'injured_or_sick';
        const showDetail = hasVictim && isInjured;

        $('#victim-condition-detail-field').toggle(showDetail);
        if (!showDetail) {
            $('#victim_condition_detail').val('');
        }
    }

    function toggleVictimFields() {
        const hasVictim = $('#has_victim').val() === '1';
        $('#victim-fields').toggle(hasVictim);

        if (!hasVictim) {
            $('#victim_name').val('');
            $('#victim_condition').val('').trigger('change.select2');
            $('#victim_condition_detail').val('');
        }

        toggleVictimConditionDetail();
    }

    function togglePropertyDamageField() {
        const hasPropertyDamage = $('#has_property_damage').val() === '1';
        $('#property-damage-field').toggle(hasPropertyDamage);

        if (!hasPropertyDamage) {
            $('#property_damage_detail').val('');
        }
    }

    $('#cause_group').on('change', function () {
        refillCauseSubtype();
    });

    $('#has_victim').on('change', function () {
        toggleVictimFields();
    });

    $('#victim_condition').on('change', function () {
        toggleVictimConditionDetail();
    });

    $('#has_property_damage').on('change', function () {
        togglePropertyDamageField();
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

                if (isInitialIncidentLoad && selectedIncidentTypeId) {
                    $('#incident_type_id').val(selectedIncidentTypeId);
                }

                isInitialIncidentLoad = false;

                $('#incident_type_id').trigger('change');
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data jenis kejadian.');
            }
        });

    });

    if (selectedCategoryId) {
        $('#incident_category_id').val(selectedCategoryId).trigger('change');
    }

    if (selectedCauseGroup) {
        $('#cause_group').val(selectedCauseGroup).trigger('change');
    } else {
        refillCauseSubtype();
    }

    toggleVictimFields();
    togglePropertyDamageField();

});

JS);
?>