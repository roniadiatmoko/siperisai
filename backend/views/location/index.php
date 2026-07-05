<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Lokasi Kerja';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="location-index">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-end align-items-center flex-wrap gap-2">
            <?= Html::a('Tambah Lokasi', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
        <div class="card-body">
            <?php Pjax::begin(); ?>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
                'columns' => [
                    'id',
                    'code',
                    'name',
                    [
                        'attribute' => 'is_active',
                        'value' => static function ($model) {
                            return $model->is_active ? 'Aktif' : 'Nonaktif';
                        },
                    ],
                    [
                        'class' => \yii\grid\ActionColumn::class,
                        'template' => '{qrcode} {update} {delete}',
                        'buttons' => [
                            'qrcode' => static function ($url) {
                                return Html::a('QR', $url, ['class' => 'btn btn-outline-secondary btn-sm me-1']);
                            },
                            'update' => static function ($url) {
                                return Html::a('Ubah', $url, ['class' => 'btn btn-outline-primary btn-sm me-1']);
                            },
                            'delete' => static function ($url) {
                                return Html::a('Hapus', $url, [
                                    'class' => 'btn btn-outline-danger btn-sm',
                                    'data-method' => 'post',
                                    'data-confirm' => 'Yakin ingin menghapus lokasi ini?',
                                ]);
                            },
                        ],
                    ],
                ],
            ]) ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
</div>