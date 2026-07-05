<?php

/** @var \yii\web\View $this */
/** @var string $content */

use backend\assets\AdminLteAsset;
use backend\assets\AppAsset;
use common\models\Report;
use common\widgets\Alert;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;

AdminLteAsset::register($this);
AppAsset::register($this);

$incomingReportCount = 0;
$reportQueueUrl = ['/report/index'];
if (!Yii::$app->user->isGuest) {
    $query = Report::find();

    if (Yii::$app->user->can('reviewReport')) {
        $query->andWhere(['status' => [Report::STATUS_SUBMITTED, Report::STATUS_SECRETARY_REVIEW]]);
        $reportQueueUrl = ['/report/index', 'queue' => 'secretary'];
    } elseif (Yii::$app->user->can('approveReport')) {
        $query->andWhere(['status' => Report::STATUS_TEAM_APPROVED]);
        $reportQueueUrl = ['/report/index', 'queue' => 'teamLead'];
    } elseif (Yii::$app->user->can('followUpReport')) {
        $query->andWhere(['status' => [Report::STATUS_COORDINATOR_FOLLOW_UP]]);
        $reportQueueUrl = ['/report/index', 'queue' => 'coordinator'];
    }

    $incomingReportCount = (int) $query->count();
}
?>
<?php $this->beginPage() ?>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<?php $this->beginBody() ?>

<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="<?= Url::to(['/site/index']) ?>" class="nav-link">Dashboard</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (!Yii::$app->user->isGuest): ?>
                    <li class="nav-item">
                        <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) ?>
                        <?= Html::submitButton('Logout (' . Yii::$app->user->identity->username . ')', ['class' => 'btn btn-link nav-link']) ?>
                        <?= Html::endForm() ?>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="<?= Url::to(['/site/index']) ?>" class="brand-link">
                <span class="brand-text fw-light">Si-PERISAI K3</span>
            </a>
        </div>

        <div class="sidebar-wrapper">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <?php if (Yii::$app->user->isGuest): ?>
                        <span class="d-block text-white">Guest</span>
                    <?php else: ?>
                        <span class="d-block text-white"><?= Html::encode(Yii::$app->user->identity->username) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Main navigation" data-accordion="false" id="navigation">
                    <li class="nav-item">
                        <a href="<?= Url::to(['/site/index']) ?>" class="nav-link<?= Yii::$app->controller->id === 'site' ? ' active' : '' ?>">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <?php if (Yii::$app->user->can('manageLocations')): ?>
                        <li class="nav-item">
                            <a href="<?= Url::to(['/location/index']) ?>" class="nav-link<?= Yii::$app->controller->id === 'location' ? ' active' : '' ?>">
                                <i class="nav-icon bi bi-geo-alt-fill"></i>
                                <p>Lokasi &amp; QR</p>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!Yii::$app->user->isGuest): ?>
                        <li class="nav-item">
                            <a href="<?= Url::to($reportQueueUrl) ?>" class="nav-link<?= Yii::$app->controller->id === 'report' ? ' active' : '' ?>">
                                <i class="nav-icon bi bi-journal-text"></i>
                                <p>
                                    Laporan
                                    <span class="right badge text-bg-danger"><?= $incomingReportCount ?></span>
                                </p>
                            </a>
                        </li>
                        <?php if (Yii::$app->user->can('viewReportAnalytics')): ?>
                            <li class="nav-item">
                                <a href="<?= Url::to(['/report-analytics/index']) ?>" class="nav-link<?= Yii::$app->controller->id === 'report-analytics' ? ' active' : '' ?>">
                                    <i class="nav-icon bi bi-bar-chart-line-fill"></i>
                                    <p>Laporan Grafik</p>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h3 class="mb-0"><?= Html::encode($this->title) ?></h3>
                    </div>
                    <div class="col-sm-6">
                        <?= Breadcrumbs::widget([
                            'tag' => 'ol',
                            'options' => ['class' => 'breadcrumb float-sm-end'],
                            'itemTemplate' => '<li class="breadcrumb-item">{link}</li>',
                            'activeItemTemplate' => '<li class="breadcrumb-item active" aria-current="page">{link}</li>',
                            'links' => $this->params['breadcrumbs'] ?? [],
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <strong>&copy; <?= date('Y') ?> Si-PERISAI K3.</strong>
        <div class="float-end d-none d-sm-inline-block">AdminLTE Backend</div>
    </footer>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
