<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Location $model */
/** @var array $qr */

$this->title = 'QR Lokasi: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Lokasi Kerja', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="location-qrcode">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-end align-items-center flex-wrap gap-2">
            <?= Html::a('Kembali', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
        </div>
        <div class="card-body">
            <p class="mb-2"><strong>Kode:</strong> <?= Html::encode($model->code) ?></p>
            <p class="mb-2"><strong>Payload:</strong> <?= Html::encode($qr['payload']) ?></p>
            <p class="mb-3"><strong>File:</strong> <?= Html::a('Download QR', $qr['url'], ['target' => '_blank']) ?></p>
            <img src="<?= Html::encode($qr['url']) ?>" alt="QR Lokasi" class="img-fluid" style="max-width: 320px;" />
        </div>
    </div>
</div>