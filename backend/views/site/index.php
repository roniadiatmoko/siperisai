<?php

/** @var yii\web\View $this */
/** @var int $locationCount */
/** @var int $reportCount */
/** @var int $telegramTestCount */
/** @var int $pdfTestCount */

$this->title = 'Dashboard Backend';
?>
<div class="site-index">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3><?= $locationCount ?></h3>
                    <p>Lokasi Terdaftar</p>
                </div>
                <i class="small-box-icon bi bi-geo-alt-fill"></i>
                <a href="<?= \yii\helpers\Url::to(['/location/index']) ?>" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                    Buka <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3><?= $reportCount ?></h3>
                    <p>Laporan Masuk</p>
                </div>
                <i class="small-box-icon bi bi-journal-text"></i>
                <a href="<?= \yii\helpers\Url::to(['/report/index']) ?>" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                    Buka <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3><?= $telegramTestCount ?></h3>
                    <p>Notifikasi workflow</p>
                </div>
                <i class="small-box-icon bi bi-send-fill"></i>
                <div class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                    <?= \yii\helpers\Html::beginForm(['/site/test-telegram'], 'post', ['class' => 'd-inline']) ?>
                    <?= \yii\helpers\Html::submitButton('Tes Telegram <i class="bi bi-arrow-right-circle"></i>', ['class' => 'btn btn-link text-dark text-decoration-none p-0 border-0 w-100', 'encode' => false]) ?>
                    <?= \yii\helpers\Html::endForm() ?>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3><?= $pdfTestCount ?></h3>
                    <p>PDF Ready</p>
                </div>
                <i class="small-box-icon bi bi-filetype-pdf"></i>
                <a href="<?= \yii\helpers\Url::to(['/report/index']) ?>" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                    Buka <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
    </div>
</div>
