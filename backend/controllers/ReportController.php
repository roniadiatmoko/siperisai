<?php

namespace backend\controllers;

use backend\models\ReportSearch;
use common\models\RefIncidentCategory;
use common\models\RefIncidentType;
use common\models\Report;
use common\models\ReportAttachment;
use common\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ReportController extends Controller
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
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'invalid' => ['post'],
                    'finalize' => ['post'],
                    'approve' => ['post'],
                    'send-coordinator' => ['post'],
                    'follow-up' => ['post', 'get'],
                    'attachment' => ['get'],
                ],
            ],
        ];
    }

    public function actionIndex($queue = null)
    {
        if ($queue === null) {
            if (Yii::$app->user->can('reviewReport')) {
                $queue = 'secretary';
            } elseif (Yii::$app->user->can('approveReport')) {
                $queue = 'teamLead';
            } elseif (Yii::$app->user->can('followUpReport')) {
                $queue = 'coordinator';
            } else {
                $queue = 'all';
            }
        }

        $searchModel = new ReportSearch();

        $dataProvider = $searchModel->search(
            Yii::$app->request->queryParams,
            $queue
        );

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'queue' => $queue,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $categories = RefIncidentCategory::find()
            ->where(['status' => 1])
            ->orderBy('name')
            ->all();


        return $this->render('view', [
            'model' => $model,
            'categories' => $categories,
        ]);
    }

    public function actionSecretary($id)
    {
        if (!Yii::$app->user->can('reviewReport')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses review laporan.');
        }

        $model = $this->findModel($id);
        $editableStatuses = [Report::STATUS_NOT_APPROVED, Report::STATUS_SUBMITTED, Report::STATUS_SECRETARY_REVIEW];
        if (!in_array($model->status, $editableStatuses, true)) {
            Yii::$app->session->setFlash('warning', 'Laporan sudah difinalisasi sekretaris dan tidak dapat diedit lagi.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (Yii::$app->request->isPost) {

            $incidentTypeId = Yii::$app->request->post('incident_type_id', Yii::$app->request->post('incident_type'));
            $causeGroup = Yii::$app->request->post('cause_group');
            $causeSubtype = Yii::$app->request->post('cause_subtype');
            $picUnit = Yii::$app->request->post('pic_unit');
            $picName = trim((string) Yii::$app->request->post('pic_name', ''));
            $hasVictim = (int) Yii::$app->request->post('has_victim', 0);
            $victimName = trim((string) Yii::$app->request->post('victim_name', ''));
            $victimCondition = (string) Yii::$app->request->post('victim_condition', '');
            $victimConditionDetail = trim((string) Yii::$app->request->post('victim_condition_detail', ''));
            $hasPropertyDamage = (int) Yii::$app->request->post('has_property_damage', 0);
            $propertyDamageDetail = trim((string) Yii::$app->request->post('property_damage_detail', ''));
            $witness = trim((string) Yii::$app->request->post('witness', ''));
            $additionalNotes = trim((string) Yii::$app->request->post('additional_notes', ''));

            if (empty($incidentTypeId) || empty($causeGroup) || empty($causeSubtype) || empty($picUnit) || $picName === '') {
                Yii::$app->session->setFlash('warning', 'Jenis kejadian, penyebab kejadian, unit kerja PIC, dan nama PIC wajib diisi.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $subtypeByGroup = Report::causeSubtypeOptionsByGroup();
            $isCauseValid = isset($subtypeByGroup[$causeGroup]) && isset($subtypeByGroup[$causeGroup][$causeSubtype]);
            if (!$isCauseValid) {
                Yii::$app->session->setFlash('warning', 'Pilihan penyebab kejadian tidak valid.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $picUnitOptions = Report::picUnitOptions();
            if (!isset($picUnitOptions[$picUnit])) {
                Yii::$app->session->setFlash('warning', 'Pilihan unit kerja PIC tidak valid.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            if (!in_array($hasVictim, [0, 1], true)) {
                Yii::$app->session->setFlash('warning', 'Pilihan ada korban tidak valid.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            if (!in_array($hasPropertyDamage, [0, 1], true)) {
                Yii::$app->session->setFlash('warning', 'Pilihan ada kerusakan sarpras tidak valid.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $victimConditionOptions = Report::victimConditionOptions();
            if ($hasVictim === 1 && $victimCondition !== '' && !isset($victimConditionOptions[$victimCondition])) {
                Yii::$app->session->setFlash('warning', 'Pilihan kondisi korban tidak valid.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            if ($hasVictim !== 1) {
                $victimName = '';
                $victimCondition = '';
                $victimConditionDetail = '';
            }

            if ($victimCondition !== Report::VICTIM_CONDITION_INJURED) {
                $victimConditionDetail = '';
            }

            if ($hasPropertyDamage !== 1) {
                $propertyDamageDetail = '';
            }

            $model->incident_type = (string) $incidentTypeId;
            $model->cause_group = (string) $causeGroup;
            $model->cause_subtype = (string) $causeSubtype;
            $model->recommendation = Yii::$app->request->post('recommendation');
            $model->pic_unit = (string) $picUnit;
            $model->pic_name = $picName;
            $model->pic_user_id = null;
            $model->missing_data_note = Yii::$app->request->post('missing_data_note');
            $model->has_victim = $hasVictim;
            $model->victim_name = $victimName;
            $model->victim_condition = $victimCondition;
            $model->victim_condition_detail = $victimConditionDetail;
            $model->has_property_damage = $hasPropertyDamage;
            $model->property_damage_detail = $propertyDamageDetail;
            $model->witness = $witness;
            $model->additional_notes = $additionalNotes;

            if ($model->save(false, ['incident_type', 'cause_group', 'cause_subtype', 'recommendation', 'pic_unit', 'pic_name', 'pic_user_id', 'missing_data_note', 'has_victim', 'victim_name', 'victim_condition', 'victim_condition_detail', 'has_property_damage', 'property_damage_detail', 'witness', 'additional_notes'])) {
                if (in_array($model->status, [Report::STATUS_SUBMITTED, Report::STATUS_NOT_APPROVED], true)) {
                    $note = $model->status === Report::STATUS_NOT_APPROVED
                        ? 'Laporan direview ulang sekretaris setelah tidak disetujui ketua tim'
                        : 'Laporan direview sekretaris';
                    $model->transitionTo(Report::STATUS_SECRETARY_REVIEW, Yii::$app->user->id, $note);
                }

                Yii::$app->session->setFlash('success', 'Data pelengkap laporan berhasil disimpan.');
                return $this->redirect(['preview', 'id' => $model->id]);
            }
        }

        return $this->render('secretary', [
            'model' => $model,
        ]);
    }

    public function actionInvalid($id)
    {
        if (!Yii::$app->user->can('reviewReport')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses review laporan.');
        }

        $model = $this->findModel($id);
        $editableStatuses = [Report::STATUS_NOT_APPROVED, Report::STATUS_SUBMITTED, Report::STATUS_SECRETARY_REVIEW];
        if (!in_array($model->status, $editableStatuses, true)) {
            Yii::$app->session->setFlash('warning', 'Laporan sudah difinalisasi sekretaris dan tidak dapat diubah.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->status !== Report::STATUS_NOT_APPROVED) {
            $model->transitionTo(
                Report::STATUS_NOT_APPROVED,
                Yii::$app->user->id,
                'Laporan dinyatakan tidak valid oleh sekretaris'
            );
        }

        Yii::$app->session->setFlash('warning', 'Laporan ditandai sebagai tidak valid.');
        return $this->redirect(['index', 'queue' => 'rejected']);
    }

    public function actionPreview($id)
    {
        if (!Yii::$app->user->can('reviewReport')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses preview laporan.');
        }

        $model = $this->findModel($id);

        return $this->render('preview', [
            'model' => $model,
        ]);
    }

    public function actionFinalize($id)
    {
        if (!Yii::$app->user->can('finalizeReport')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses finalisasi laporan.');
        }

        $model = $this->findModel($id);
        $model->secretary_id = Yii::$app->user->id;
        $model->secretary_finalized_at = time();
        $model->save(false, ['secretary_id', 'secretary_finalized_at']);
        $model->transitionTo(Report::STATUS_TEAM_APPROVED, Yii::$app->user->id, 'Laporan difinalisasi sekretaris');

        $message = "Laporan siap approval\nNo: {$model->report_number}\nLokasi: {$model->location->name}";
        Yii::$app->telegram->notifyRole(User::ROLE_TEAM_LEAD, $message);

        Yii::$app->session->setFlash('success', 'Laporan berhasil difinalisasi dan notifikasi dikirim ke ketua tim.');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionApprove($id)
    {
        if (!Yii::$app->user->can('approveReport')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses approve laporan.');
        }

        $model = $this->findModel($id);
        if ($model->status !== Report::STATUS_TEAM_APPROVED) {
            Yii::$app->session->setFlash('warning', 'Laporan tidak dalam tahap persetujuan ketua tim.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $decision = Yii::$app->request->post('decision');
        $approvalNote = trim((string) Yii::$app->request->post('approval_note', ''));

        $model->team_lead_id = Yii::$app->user->id;
        $model->team_lead_approved_at = time();
        $model->save(false, ['team_lead_id', 'team_lead_approved_at']);

        if ($decision === 'rejected') {
            $note = $approvalNote !== '' ? $approvalNote : 'Laporan tidak disetujui ketua tim';
            $model->transitionTo(Report::STATUS_SECRETARY_REVIEW, Yii::$app->user->id, $note);
            Yii::$app->telegram->notifyRole(User::ROLE_SECRETARY, "Laporan tidak disetujui ketua tim\nNo: {$model->report_number}\nCatatan: {$note}");
            Yii::$app->session->setFlash('warning', 'Laporan tidak disetujui ketua tim.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $note = $approvalNote !== '' ? $approvalNote : 'Laporan disetujui ketua tim';
        $model->transitionTo(Report::STATUS_SECRETARY_FINALIZED, Yii::$app->user->id, $note);

        $message = "Laporan sudah disetujui ketua tim\nNo: {$model->report_number}\nSilakan lanjutkan proses laporan.";
        Yii::$app->telegram->notifyRole(User::ROLE_SECRETARY, $message);

        Yii::$app->session->setFlash('success', 'Laporan berhasil disetujui ketua tim.');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionPdf($id)
    {
        if (!Yii::$app->user->can('generateReportPdf')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses generate PDF.');
        }

        try {
            $model = $this->findModel($id);
            $filePath = $this->generatePdfFile($model);

            return Yii::$app->response->sendFile($filePath, basename($filePath));
        } catch (\Throwable $e) {
            $this->notifyErrorToTelegram('actionPdf', $id, $e);
            throw $e;
        }
    }

    public function actionAttachment($id)
    {
        $attachment = ReportAttachment::findOne((int) $id);
        if ($attachment === null) {
            throw new NotFoundHttpException('Lampiran tidak ditemukan.');
        }

        $baseDirectory = Yii::getAlias(Yii::$app->params['app.uploadPath']);
        $relativePath = ltrim((string) $attachment->file_path, '/');
        $fullPath = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (!is_file($fullPath)) {
            throw new NotFoundHttpException('File lampiran tidak ditemukan di server.');
        }

        return Yii::$app->response->sendFile($fullPath, (string) $attachment->original_name, ['inline' => true]);
    }

    public function actionSendCoordinator($id)
    {
        if (!Yii::$app->user->can('sendTelegramNotification')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses kirim Telegram.');
        }

        try {
            $model = $this->findModel($id);
            $filePath = $this->generatePdfFile($model);

            $model->transitionTo(Report::STATUS_COORDINATOR_FOLLOW_UP, Yii::$app->user->id, 'Laporan dikirim ke koordinator via Telegram');

            $caption = 'Laporan K3L: ' . $model->report_number;
            $sent = Yii::$app->telegram->notifyRoleWithDocument(User::ROLE_COORDINATOR, $filePath, $caption);
            if ($sent > 0) {
                Yii::$app->session->setFlash('success', 'Laporan PDF berhasil dikirim ke koordinator kepala via Telegram.');
            } else {
                Yii::$app->session->setFlash('warning', 'PDF gagal dikirim. Periksa bot token dan chat id Telegram koordinator.');
            }

            return $this->redirect(['view', 'id' => $model->id]);
        } catch (\Throwable $e) {
            $this->notifyErrorToTelegram('actionSendCoordinator', $id, $e);
            throw $e;
        }
    }

    public function actionFollowUp($id)
    {
        if (!Yii::$app->user->can('followUpReport')) {
            throw new ForbiddenHttpException('Anda tidak memiliki akses tindak lanjut.');
        }

        $model = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $model->coordinator_id = Yii::$app->user->id;
            $model->coordinator_follow_up_note = Yii::$app->request->post('coordinator_follow_up_note');
            $model->coordinator_follow_up_at = time();

            if ($model->save(false, ['coordinator_id', 'coordinator_follow_up_note', 'coordinator_follow_up_at'])) {
                $model->transitionTo(Report::STATUS_CLOSED, Yii::$app->user->id, 'Tindak lanjut koordinator disimpan');
                Yii::$app->session->setFlash('success', 'Tindak lanjut koordinator berhasil disimpan.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('follow-up', [
            'model' => $model,
        ]);
    }

    protected function findModel($id)
    {
        $model = Report::find()->with(['location', 'reporter', 'picUser', 'attachments', 'statusHistories', 'incidentType'])->where(['id' => $id])->one();
        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Laporan tidak ditemukan.');
    }

    protected function getUserOptions()
    {
        $users = User::find()->orderBy(['username' => SORT_ASC])->all();
        $options = [];

        foreach ($users as $user) {
            $options[$user->id] = $user->username;
        }

        return $options;
    }

    protected function generatePdfFile(Report $model)
    {
        $pdfDirectory = Yii::getAlias('@frontend/web/uploads/pdf');
        if (!is_dir($pdfDirectory)) {
            @mkdir($pdfDirectory, 0775, true);
        }

        $filePath = $pdfDirectory . DIRECTORY_SEPARATOR . $model->report_number . '.pdf';
        $html = $this->renderPartial('_pdf', ['model' => $model]);
        Yii::$app->pdfService->renderHtmlToFile($html, $filePath, [
            'format' => 'Legal',
            'orientation' => 'P',
            'margin_top' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_bottom' => 10,
        ]);

        return $filePath;
    }

    protected function notifyErrorToTelegram($route, $id, \Throwable $exception)
    {
        $message = implode(PHP_EOL, [
            '🚨 Error di aplikasi pelaporan',
            'Route: ' . $route,
            'Report ID: ' . $id,
            'Message: ' . $exception->getMessage(),
            'File: ' . $exception->getFile(),
            'Line: ' . $exception->getLine(),
            'Trace: ' . $exception->getTraceAsString(),
        ]);

        try {
            Yii::$app->telegram->sendMessage('327754279', $message);
        } catch (\Throwable $telegramException) {
            Yii::error('Failed to send Telegram error notification: ' . $telegramException->getMessage(), __METHOD__);
        }
    }

    public function actionIncidentTypeList($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return RefIncidentType::find()
            ->select([
                'id',
                'text' => 'name'
            ])
            ->where([
                'incident_category_id' => $id,
                'status' => 1
            ])
            ->orderBy('name')
            ->asArray()
            ->all();
    }
}
