<?php

use common\models\Report;
use common\models\ReportStatusHistory;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var backend\models\ReportStatusHistorySearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Riwayat Status Laporan';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="report-status-history-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Tambah Riwayat Status', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'report_id',
                'value' => function (ReportStatusHistory $model) {
                    return $model->report
                        ? $model->report_id . ' (' . $model->report->report_number . ')'
                        : $model->report_id;
                },
            ],
            [
                'attribute' => 'status_from',
                'filter' => Report::statusLabelOptions(),
                'value' => function (ReportStatusHistory $model) {
                    return $model->status_from === null || $model->status_from === ''
                        ? '-'
                        : $model->status_from . ' - ' . Report::statusLabel($model->status_from);
                },
            ],
            [
                'attribute' => 'status_to',
                'filter' => Report::statusLabelOptions(),
                'value' => function (ReportStatusHistory $model) {
                    return $model->status_to . ' - ' . Report::statusLabel($model->status_to);
                },
            ],
            'note:ntext',
            'created_by',
            'created_at:datetime',
            [
                'class' => ActionColumn::class,
                'urlCreator' => function ($action, ReportStatusHistory $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                }
            ],
        ],
    ]); ?>

</div>
