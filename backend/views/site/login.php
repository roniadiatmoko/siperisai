<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Login Backend';
?>
<div class="login-box" style="width: 420px;">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="#" class="h3"><b>Backend</b> K3L</a>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Masuk untuk melanjutkan</p>

            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

            <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'placeholder' => 'Username']) ?>
            <?= $form->field($model, 'password')->passwordInput(['placeholder' => 'Password']) ?>
            <?= $form->field($model, 'rememberMe')->checkbox() ?>

            <div class="form-group mb-0">
                <?= Html::submitButton('Login', ['class' => 'btn btn-primary w-100', 'name' => 'login-button']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
