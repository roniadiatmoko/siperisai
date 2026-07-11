<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var frontend\models\ReportSubmitForm $model */
/** @var common\models\Location|null $selectedLocation */
/** @var array $locationItems */
/** @var array $locationDetailRules */
/** @var bool $isGuest */

$this->title = 'Buat Laporan';

$this->registerCssFile('/vendor/select2/css/select2.min.css');
$this->registerJsFile('/vendor/select2/js/select2.min.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>

<div class="report-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <section class="report-wizard-guide" aria-label="Panduan pengisian laporan">
        <h2 class="h6 mb-3">Panduan Pengisian</h2>
        <div class="report-wizard-steps">
            <div class="report-wizard-step">
                <span class="report-wizard-step__number">1</span>
                <span class="report-wizard-step__title">Isi Informasi Kejadian</span>
                <span class="report-wizard-step__text">Lengkapi data &amp; detail kejadian</span>
            </div>
            <div class="report-wizard-step">
                <span class="report-wizard-step__number">2</span>
                <span class="report-wizard-step__title">Upload Foto</span>
                <span class="report-wizard-step__text">Lampirkan foto dokumentasi</span>
            </div>
            <div class="report-wizard-step">
                <span class="report-wizard-step__number">3</span>
                <span class="report-wizard-step__title">Preview &amp; Finalisasi</span>
                <span class="report-wizard-step__text">Periksa data sebelum laporan disimpan</span>
            </div>
        </div>
    </section>

    <?php if ($selectedLocation !== null): ?>
        <p class="text-muted">Lokasi terdeteksi dari QR: <strong><?= Html::encode($selectedLocation->name) ?></strong>. Anda tetap bisa mengganti lokasi secara manual.</p>
    <?php else: ?>
        <p class="text-muted">Silakan pilih lokasi kerja</p>
    <?php endif; ?>

    <?php $form = ActiveForm::begin([
        'action' => ['preview'],
        'options' => ['enctype' => 'multipart/form-data'],
    ]); ?>

    <?= $form->field($model, 'location_id')->dropDownList($locationItems, ['prompt' => 'Pilih lokasi kerja']) ?>
    <div id="detail-lokasi-field" style="display:none;">
        <?= $form->field($model, 'detail_lokasi')->textarea(['rows' => 2, 'placeholder' => 'Isi detail lokasi']) ?>
    </div>
    <?= $form->field($model, 'incident_time_input')->input('datetime-local') ?>
    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'has_victim')->radioList([1 => 'Ya', 0 => 'Tidak'], ['itemOptions' => ['class' => 'form-check-inline']]) ?>

    <div id="victim-fields">
        <?= $form->field($model, 'victim_name')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'victim_condition')->dropDownList($model::victimConditionOptions(), ['prompt' => 'Pilih kondisi korban']) ?>
        <div id="victim-condition-detail-field">
            <?= $form->field($model, 'victim_condition_detail')->textarea(['rows' => 2]) ?>
        </div>
    </div>

    <?= $form->field($model, 'has_property_damage')->radioList([1 => 'Ya', 0 => 'Tidak'], ['itemOptions' => ['class' => 'form-check-inline']]) ?>

    <div id="property-damage-field">
        <?= $form->field($model, 'property_damage_detail')->textarea(['rows' => 2]) ?>
    </div>

    <?= $form->field($model, 'witness')->textarea(['rows' => 2]) ?>
    <?= $form->field($model, 'additional_notes')->textarea(['rows' => 3]) ?>


    <div id="reporter-name-field">
        <?= $form->field($model, 'reporter_name')->textInput(['maxlength' => true]) ?>
    </div>

    <?= $form->field($model, 'is_anonymous')->checkbox([
        'checked' => false,
    ]) ?>

    <div class="form-group">
        <label class="control-label">Foto Kejadian</label>
        <?= Html::radioList('attachment_source', 'camera', [
            'camera' => 'Ambil foto dari kamera',
            'file' => 'Unggah file',
        ], [
            'itemOptions' => ['class' => 'form-check-inline'],
        ]) ?>
        <div class="help-block">Pilih kamera untuk mengambil foto langsung, atau unggah file gambar dari perangkat Anda.</div>
        <div id="attachment-source-message" class="help-block text-info"></div>
    </div>

    <?= $form->field($model, 'attachmentFiles')->fileInput([
        'multiple' => true,
        'accept' => 'image/*',
        'capture' => 'environment',
    ]) ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Lihat Preview', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$locationDetailRulesJs = \yii\helpers\Json::htmlEncode($locationDetailRules ?? []);
$js = <<<'JS'
(function () {
    var locationDetailRules = LOCATION_DETAIL_RULES;
    window.requiresDetailLokasiForSelectedLocation = function () {
        return false;
    };

    var $locationSelect = $('#reportsubmitform-location_id');
    var $detailLokasiField = $('#detail-lokasi-field');
    var $detailLokasiInput = $('#reportsubmitform-detail_lokasi');
    var $attachmentSource = $('input[name="attachment_source"]');
    var $attachmentInput = $('#reportsubmitform-attachmentfiles');
    var $attachmentMessage = $('#attachment-source-message');

    function toggleAttachmentInput() {
        if ($attachmentInput.length === 0 || $attachmentSource.length === 0) {
            return;
        }

        var useCamera = $attachmentSource.filter(':checked').val() === 'camera';
        if (useCamera) {
            $attachmentInput.attr('capture', 'environment');
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                $attachmentMessage.text('Browser Anda belum mendukung pemotretan langsung. Silakan pilih unggah file.');
            } else {
                $attachmentMessage.text('Browser akan meminta izin akses kamera saat Anda memilih file dari kamera.');
            }
        } else {
            $attachmentInput.removeAttr('capture');
            $attachmentMessage.text('Anda dapat mengunggah file gambar dari perangkat Anda.');
        }
        $attachmentInput.attr('accept', 'image/*');
    }

    if ($locationSelect.length > 0 && typeof $locationSelect.select2 === 'function') {
        $locationSelect.select2({
            width: '100%',
            placeholder: 'Pilih lokasi kerja',
            allowClear: true,
            language: {
                noResults: function () {
                    return 'Lokasi tidak ditemukan';
                }
            }
        });
    }

    function getSelectedLocationRule() {
        var selectedId = String($locationSelect.val() || '');
        if (!selectedId || !locationDetailRules[selectedId]) {
            return null;
        }

        return locationDetailRules[selectedId];
    }

    function toggleDetailLokasiField() {
        var rule = getSelectedLocationRule();
        var show = !!(rule && rule.requires_detail);

        $detailLokasiField.toggle(show);

        if (!show) {
            $detailLokasiInput.val('');
            return;
        }

        $detailLokasiInput.attr('placeholder', rule.placeholder || 'Isi detail lokasi');
    }

    window.requiresDetailLokasiForSelectedLocation = function () {
        var rule = getSelectedLocationRule();
        return !!(rule && rule.requires_detail);
    };

    var $hasVictimRadios = $('input[name="ReportSubmitForm[has_victim]"]');
    var $victimFields = $('#victim-fields');
    var $victimCondition = $('#reportsubmitform-victim_condition');
    var $victimConditionDetailField = $('#victim-condition-detail-field');
    var $victimName = $('#reportsubmitform-victim_name');
    var $victimConditionDetail = $('#reportsubmitform-victim_condition_detail');

    var $hasDamageRadios = $('input[name="ReportSubmitForm[has_property_damage]"]');
    var $damageField = $('#property-damage-field');
    var $damageDetail = $('#reportsubmitform-property_damage_detail');

    var $anonymous = $('#reportsubmitform-is_anonymous');
    var $reporterNameField = $('#reporter-name-field');
    var $reporterName = $('#reportsubmitform-reporter_name');

    function toggleVictimConditionDetail() {
        var hasVictim = $hasVictimRadios.filter(':checked').val() === '1';
        var isInjured = $victimCondition.val() === 'injured_or_sick';
        var showDetail = hasVictim && isInjured;

        $victimConditionDetailField.toggle(showDetail);
        if (!showDetail) {
            $victimConditionDetail.val('');
        }
    }

    function toggleVictimFields() {
        var show = $hasVictimRadios.filter(':checked').val() === '1';
        $victimFields.toggle(show);

        if (!show) {
            $victimName.val('');
            $victimCondition.val('');
            $victimConditionDetail.val('');
        }

        toggleVictimConditionDetail();
    }

    function toggleDamageField() {
        var show = $hasDamageRadios.filter(':checked').val() === '1';
        $damageField.toggle(show);
        if (!show) {
            $damageDetail.val('');
        }
    }

    function toggleReporterField() {
        if ($anonymous.length === 0 || $reporterNameField.length === 0) {
            return;
        }

        var isAnonymous = $anonymous.is(':checked');

        if (isAnonymous) {
            $reporterName.val('ANONIM');
            $reporterNameField.hide();
        } else {
            $reporterName.val('');
            $reporterNameField.show();
        }
    }

    $hasVictimRadios.on('change', toggleVictimFields);
    $victimCondition.on('change', toggleVictimConditionDetail);
    $hasDamageRadios.on('change', toggleDamageField);
    if ($anonymous.length > 0) {
        $anonymous.on('change', toggleReporterField);
    }
    $attachmentSource.on('change', toggleAttachmentInput);
    if ($locationSelect.length > 0) {
        $locationSelect.on('change', toggleDetailLokasiField);
    }

    toggleAttachmentInput();
    toggleDetailLokasiField();
    toggleVictimFields();
    toggleDamageField();
    toggleReporterField();
})();
JS;

$this->registerJs(str_replace('LOCATION_DETAIL_RULES', $locationDetailRulesJs, $js));
?>