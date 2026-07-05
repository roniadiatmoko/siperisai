<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Report $model */
/** @var array $users */

$this->title = 'Pelengkapan Laporan oleh Sekretaris';
$this->params['breadcrumbs'][] = ['label' => 'Daftar Laporan', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="report-secretary">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="mb-3">
                <label class="form-label">No Laporan</label>
                <input type="text" class="form-control" value="<?= Html::encode($model->report_number) ?>" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label">Jenis Kejadian</label>
                <input type="text" class="form-control" name="incident_type" value="<?= Html::encode($model->incident_type) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Rekomendasi Perbaikan</label>
                <textarea class="form-control" name="recommendation" rows="5" required><?= Html::encode($model->recommendation) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">PIC Tindak Lanjut</label>
                <?= Html::dropDownList('pic_user_id', $model->pic_user_id, $users, ['class' => 'form-select', 'prompt' => 'Pilih PIC', 'required' => true]) ?>
            </div>

            <div class="d-flex gap-2">
                <?= Html::submitButton('Simpan & Lanjut Preview', ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>