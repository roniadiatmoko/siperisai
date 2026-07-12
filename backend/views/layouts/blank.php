<?php

/** @var yii\web\View $this */
/** @var string $content */

use backend\assets\AppAsset;
use backend\assets\AdminLteAsset;
use yii\helpers\Html;

AdminLteAsset::register($this);
AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="login-page bg-body-secondary">
<?php $this->beginBody() ?>

<?= $content ?>

<footer class="text-center text-muted py-3">
    <small>&copy; <?= date('Y') ?> SI-PERISAI K3.</small>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
