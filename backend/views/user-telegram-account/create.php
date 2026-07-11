<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\UserTelegramAccount $model */

$this->title = 'Create User Telegram Account';
$this->params['breadcrumbs'][] = ['label' => 'User Telegram Accounts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-telegram-account-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
