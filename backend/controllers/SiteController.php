<?php

namespace backend\controllers;

use common\models\Location;
use common\models\Report;
use common\models\UserTelegramAccount;
use common\models\LoginForm;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['test-telegram'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'test-telegram' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index', [
            'locationCount' => (int) Location::find()->count(),
            'reportCount' => (int) Report::find()->count(),
            'telegramTestCount' => (int) UserTelegramAccount::find()->where(['is_enabled' => 1])->count(),
            'pdfTestCount' => (int) Report::find()->count(),
        ]);
    }

    public function actionTestTelegram()
    {
        $account = UserTelegramAccount::findOne([
            'user_id' => Yii::$app->user->id,
            'is_enabled' => 1,
        ]);

        if ($account === null) {
            Yii::$app->session->setFlash('warning', 'Akun Telegram belum diatur untuk user ini.');
            return $this->redirect(['index']);
        }

        $message = "Tes Telegram Si-PERISAI K3\n"
            . "User: " . Yii::$app->user->identity->username . "\n"
            . "Waktu: " . date('d-m-Y H:i:s');

        if (Yii::$app->telegram->sendMessage($account->telegram_chat_id, $message)) {
            Yii::$app->session->setFlash('success', 'Pesan tes Telegram berhasil dikirim.');
        } else {
            Yii::$app->session->setFlash('error', 'Pesan tes Telegram gagal dikirim. Periksa token, chat id, dan apakah bot sudah di-start.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
