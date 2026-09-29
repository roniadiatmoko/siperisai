<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\ReportStatusHistory $model */

$this->title = 'Ubah Riwayat Status #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Riwayat Status Laporan', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Riwayat Status #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Ubah';
?>
<div class="report-status-history-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
