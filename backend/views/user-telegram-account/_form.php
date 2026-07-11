<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\UserTelegramAccount $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="user-telegram-account-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'user_id')->textInput() ?>

    <?= $form->field($model, 'telegram_chat_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'telegram_username')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'is_enabled')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
