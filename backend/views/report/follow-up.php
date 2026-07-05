<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Report $model */

$this->title = 'Tindak Lanjut Koordinator';
$this->params['breadcrumbs'][] = ['label' => 'Daftar Laporan', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="report-follow-up">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="mb-3">
                <label class="form-label">No Laporan</label>
                <input type="text" class="form-control" value="<?= Html::encode($model->report_number) ?>" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label">Catatan Tindak Lanjut</label>
                <textarea class="form-control" name="coordinator_follow_up_note" rows="6" required><?= Html::encode($model->coordinator_follow_up_note) ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <?= Html::submitButton('Simpan Tindak Lanjut', ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>