<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\ReportStatusHistory $model */

$this->title = 'Tambah Riwayat Status';
$this->params['breadcrumbs'][] = ['label' => 'Riwayat Status Laporan', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="report-status-history-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
