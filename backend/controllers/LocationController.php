<?php

namespace backend\controllers;

use common\components\QrCodeService;
use common\models\Location;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class LocationController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static function () {
                            return Yii::$app->user->can('manageLocations');
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $this->ensurePermission();

        $dataProvider = new ActiveDataProvider([
            'query' => Location::find()->orderBy(['name' => SORT_ASC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $this->ensurePermission();

        $model = new Location();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['qrcode', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $this->ensurePermission();

        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->ensurePermission();

        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    public function actionQrcode($id)
    {
        $this->ensurePermission();

        $model = $this->findModel($id);
        $service = new QrCodeService([
            'basePath' => Yii::$app->params['app.qrPath'],
            'baseUrl' => Yii::$app->params['app.qrUrl'],
        ]);

        $result = $service->generateLocationQr($model);

        return $this->render('qrcode', [
            'model' => $model,
            'qr' => $result,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Location::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested location does not exist.');
    }

    protected function ensurePermission()
    {
        if (!Yii::$app->user->can('manageLocations')) {
            throw new \yii\web\ForbiddenHttpException('Anda tidak memiliki akses manajemen lokasi.');
        }
    }
}