<?php

use common\models\Report;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\ReportStatusHistory $model */

$this->title = 'Riwayat Status #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Riwayat Status Laporan', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="report-status-history-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Ubah', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Hapus', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Yakin ingin menghapus riwayat status ini?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'attribute' => 'report_id',
                'value' => $model->report
                    ? $model->report_id . ' (' . $model->report->report_number . ')'
                    : $model->report_id,
            ],
            [
                'attribute' => 'status_from',
                'value' => $model->status_from === null || $model->status_from === ''
                    ? '-'
                    : $model->status_from . ' - ' . Report::statusLabel($model->status_from),
            ],
            [
                'attribute' => 'status_to',
                'value' => $model->status_to . ' - ' . Report::statusLabel($model->status_to),
            ],
            'note:ntext',
            'created_by',
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

</div>
