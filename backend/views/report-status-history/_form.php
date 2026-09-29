<?php

use common\models\Report;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\ReportStatusHistory $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="report-status-history-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'report_id')->textInput(['type' => 'number']) ?>

    <?= $form->field($model, 'status_from')->dropDownList(Report::statusLabelOptions(), ['prompt' => '- Tidak ada -']) ?>

    <?= $form->field($model, 'status_to')->dropDownList(Report::statusLabelOptions(), ['prompt' => '- Pilih status -']) ?>

    <?= $form->field($model, 'note')->textarea(['rows' => 4]) ?>

    <?= $form->field($model, 'created_by')->textInput(['type' => 'number']) ?>

    <div class="form-group">
        <?= Html::submitButton('Simpan', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
