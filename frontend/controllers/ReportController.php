<?php

namespace frontend\controllers;

use common\models\Location;
use common\models\Report;
use common\models\User;
use frontend\models\ReportSubmitForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ReportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['scan', 'start', 'create', 'success', 'view'],
                'rules' => [
                    [
                        'actions' => ['start', 'success', 'view'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['scan', 'create'],
                        'allow' => true,
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'scan' => ['get'],
                ],
            ],
        ];
    }

    public function actionStart()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $reports = Report::find()
            ->with(['location'])
            ->where(['reporter_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('start', [
            'reports' => $reports,
        ]);
    }

    public function actionScan($locationId)
    {
        $location = $this->findLocation($locationId);
        Yii::$app->session->set('pendingReportLocationId', $location->id);

        return $this->redirect(['create', 'locationId' => $location->id]);
    }

    public function actionCreate($locationId = null)
    {
        $locationId = $locationId ?: Yii::$app->session->get('pendingReportLocationId');
        $selectedLocation = null;
        if (!empty($locationId)) {
            $selectedLocation = $this->findLocation($locationId);
        }

        $locationItems = [];
        $locations = Location::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
        foreach ($locations as $location) {
            $locationItems[$location->id] = $location->code . ' - ' . $location->name;
        }

        $model = new ReportSubmitForm();
        if ($selectedLocation !== null) {
            $model->location_id = $selectedLocation->id;
        }
        $model->incident_time_input = date('Y-m-d\TH:i');
        if (Yii::$app->user->isGuest) {
            $model->is_anonymous = 1;
            $model->reporter_name = null;
        }
        if (!Yii::$app->request->isPost && Yii::$app->user->identity !== null) {
            $model->reporter_name = Yii::$app->user->identity->username;
        }

        if ($model->load(Yii::$app->request->post())) {
            if (Yii::$app->user->isGuest) {
                $model->is_anonymous = 1;
                $model->reporter_name = null;
            }
            $model->attachmentFiles = \yii\web\UploadedFile::getInstances($model, 'attachmentFiles');
            $reporterId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
            $report = $model->save($reporterId);

            if ($report instanceof Report) {
                $locationName = $report->location ? $report->location->name : '-';
                $victimConditionMap = [
                    'conscious' => 'Sadar',
                    'unconscious' => 'Tidak sadar',
                    'injured_or_sick' => 'Cedera/luka/sakit',
                    'not_injured' => 'Tidak cedera',
                ];
                $victimCondition = $victimConditionMap[$report->victim_condition] ?? '-';
                $hasVictim = (int) $report->has_victim === 1 ? 'Ya' : 'Tidak';
                $hasPropertyDamage = (int) $report->has_property_damage === 1 ? 'Ya' : 'Tidak';
                $reporterName = !empty($report->reporter_name)
                    ? $report->reporter_name
                    : ((int) $report->is_anonymous === 1 ? 'Anonim' : '-');

                $message = "Laporan baru masuk\n"
                    . "No: {$report->report_number}\n"
                    . "Lokasi: {$locationName}\n"
                    . "Keterangan: {$report->description}\n"
                    . "Waktu: " . date('d-m-Y H:i', (int) $report->incident_time) . "\n"
                    . "Ada korban: {$hasVictim}\n"
                    . "Nama korban: " . ($report->victim_name ?: '-') . "\n"
                    . "Kondisi korban: {$victimCondition}\n"
                    . "Detail kondisi korban: " . ($report->victim_condition_detail ?: '-') . "\n"
                    . "Ada kerusakan sarana/prasarana: {$hasPropertyDamage}\n"
                    . "Detail kerusakan: " . ($report->property_damage_detail ?: '-') . "\n"
                    . "Saksi: " . ($report->witness ?: '-') . "\n"
                    . "Catatan lain-lain: " . ($report->additional_notes ?: '-') . "\n"
                    . "Nama pelapor: {$reporterName}";

                Yii::$app->telegram->notifyRole(User::ROLE_SECRETARY, $message);
                Yii::$app->session->remove('pendingReportLocationId');

                if (Yii::$app->user->isGuest) {
                    return $this->render('success', [
                        'report' => $report,
                    ]);
                }

                return $this->redirect(['success', 'id' => $report->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'selectedLocation' => $selectedLocation,
            'locationItems' => $locationItems,
            'isGuest' => Yii::$app->user->isGuest,
        ]);
    }

    public function actionSuccess($id)
    {
        $report = Report::findOne($id);

        if ($report === null) {
            throw new NotFoundHttpException('Laporan tidak ditemukan.');
        }

        return $this->render('success', [
            'report' => $report,
        ]);
    }

    public function actionView($id)
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $report = Report::find()
            ->with(['location', 'reporter', 'attachments', 'statusHistories'])
            ->where(['id' => $id, 'reporter_id' => Yii::$app->user->id])
            ->one();

        if ($report === null) {
            throw new NotFoundHttpException('Laporan tidak ditemukan.');
        }

        return $this->render('view', [
            'report' => $report,
        ]);
    }

    protected function findLocation($id)
    {
        if (($location = Location::findOne($id)) !== null) {
            return $location;
        }

        throw new NotFoundHttpException('Lokasi tidak ditemukan.');
    }
}