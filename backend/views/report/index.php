<?php

use common\models\RefIncidentType;
use common\models\Report;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $queue */

$this->title = 'Daftar Laporan';
$this->params['breadcrumbs'][] = $this->title;

$queueLabels = [
    'secretary' => 'Antrian Sekretaris',
    'rejected' => 'Antrian Ditolak',
    'tindakan' => 'Antrian Tindakan',
    'teamLead' => 'Antrian Ketua Tim',
    'coordinator' => 'Antrian Koordinator',
    'all' => 'Semua Laporan',
];

$statusLabels = Report::statusLabelOptions();
$queueButtonClass = static function (string $name, string $baseClass = 'btn-outline-primary') use ($queue): string {
    if ($queue === $name) {
        return str_replace('outline-', '', $baseClass);
    }

    return $baseClass;
};
?>

<div class="report-index">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <p class="text-muted mb-3">Mode: <?= Html::encode($queueLabels[$queue] ?? 'Semua Laporan') ?></p>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php if (Yii::$app->user->can('reviewReport')): ?>
                    <?= Html::a('Antrian Sekretaris', ['index', 'queue' => 'secretary'], ['class' => 'btn ' . $queueButtonClass('secretary') . ' btn-sm']) ?>
                    <?= Html::a('Antrian Ditolak', ['index', 'queue' => 'rejected'], ['class' => 'btn ' . $queueButtonClass('rejected', 'btn-outline-danger') . ' btn-sm']) ?>
                    <?= Html::a('Antrian Tindakan', ['index', 'queue' => 'tindakan'], ['class' => 'btn ' . $queueButtonClass('tindakan') . ' btn-sm']) ?>
                <?php endif; ?>
                <?php if (Yii::$app->user->can('approveReport')): ?>
                    <?= Html::a('Antrian Ketua Tim', ['index', 'queue' => 'teamLead'], ['class' => 'btn ' . $queueButtonClass('teamLead') . ' btn-sm']) ?>
                <?php endif; ?>
                <?php if (Yii::$app->user->can('followUpReport')): ?>
                    <?= Html::a('Antrian Koordinator', ['index', 'queue' => 'coordinator'], ['class' => 'btn ' . $queueButtonClass('coordinator') . ' btn-sm']) ?>
                <?php endif; ?>
            </div>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,

                'hover' => true,
                'striped' => true,
                'condensed' => false,
                'responsiveWrap' => false,

                'toolbar' => [
                    '{export}',
                    '{toggleData}',
                ],

                'export' => [
                    'fontAwesome' => true,
                    'label' => 'Export',
                ],

                'panel' => [
                    'type' => GridView::TYPE_PRIMARY,
                    'heading' => 'Daftar Laporan',
                ],

                'columns' => [

                    ['class' => 'kartik\grid\SerialColumn'],

                    [
                        'attribute' => 'report_number',
                    ],
                    
                    [
                        'attribute' => 'created_at',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return date('d-m-Y H:i', $model->created_at);
                        },

                        'filterType' => GridView::FILTER_DATE,
                    ],
                    [
                        'attribute' => 'incident_type',
                        'value' => function ($model) {
                            return $model->incidentType ? $model->incidentType->name : '-';
                        },
                        'filter' => ArrayHelper::map(
                            RefIncidentType::find()->all(),
                            'id',
                            'name'
                        ),
                    ],

                    [
                        'attribute' => 'incident_time',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return date('d-m-Y H:i', $model->incident_time);
                        },

                        'filterType' => GridView::FILTER_DATE,
                    ],

                    [
                        'attribute' => 'location_id',
                        'label' => 'Lokasi',
                        'value' => 'location.name',

                        'filter' => ArrayHelper::map(
                            \common\models\Location::find()->all(),
                            'id',
                            'name'
                        ),
                    ],                    

                    [
                        'attribute' => 'status',

                        'filter' => $statusLabels,

                        'value' => function ($model) use ($statusLabels) {
                            return $statusLabels[$model->status] ?? '-';
                        },
                    ],
                    //photo column
                    

                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'template' => '{view}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fa fa-eye"></i> Lihat', ['view', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-primary',
                                    'title' => 'Lihat Laporan',
                                ]);
                            },
                        ],
                    ],
                ]
            ]); ?>
        </div>
    </div>
</div>