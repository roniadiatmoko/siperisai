<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Location $model */

$this->title = 'Ubah Lokasi';
$this->params['breadcrumbs'][] = ['label' => 'Lokasi Kerja', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="location-update">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model]) ?>
        </div>
    </div>
</div>