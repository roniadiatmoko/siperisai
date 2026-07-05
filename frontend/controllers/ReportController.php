<?php

namespace frontend\controllers;

use common\models\Location;
use common\models\Report;
use common\models\ReportAttachment;
use common\models\User;
use frontend\models\ReportSubmitForm;
use Yii;
use yii\helpers\FileHelper;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class ReportController extends Controller
{
    private const REPORT_DRAFT_SESSION_KEY = 'reportSubmissionDraft';
    private const LAST_GUEST_REPORT_ID_SESSION_KEY = 'lastGuestReportId';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['scan', 'start', 'create', 'preview', 'finalize', 'success', 'view', 'track'],
                'rules' => [
                    [
                        'actions' => ['start', 'view'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['scan', 'create', 'preview', 'finalize', 'success', 'track'],
                        'allow' => true,
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'scan' => ['get'],
                    'preview' => ['post'],
                    'finalize' => ['post'],
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
        if (Yii::$app->request->get('restore') !== '1') {
            $existingDraft = $this->getDraft();
            if (is_array($existingDraft) && !empty($existingDraft['draftId'])) {
                $this->cleanupDraftTempDirectory((string) $existingDraft['draftId']);
            }
            Yii::$app->session->remove(self::REPORT_DRAFT_SESSION_KEY);
        }

        $model = $this->buildSubmitFormModel($locationId);

        $draft = $this->getDraft();
        if (is_array($draft) && Yii::$app->request->get('restore') === '1') {
            $this->fillModelFromDraft($model, $draft['form'] ?? []);
        }

        $selectedLocation = null;
        if (!empty($model->location_id)) {
            $selectedLocation = $this->findLocation($model->location_id);
        }

        return $this->render('create', [
            'model' => $model,
            'selectedLocation' => $selectedLocation,
            'locationItems' => $this->getLocationItems(),
            'locationDetailRules' => $this->getLocationDetailRules(),
            'isGuest' => Yii::$app->user->isGuest,
        ]);
    }

    public function actionPreview()
    {
        $locationId = Yii::$app->session->get('pendingReportLocationId');
        $model = $this->buildSubmitFormModel($locationId);

        if (!$model->load(Yii::$app->request->post())) {
            return $this->redirect(['create']);
        }

        if (Yii::$app->user->isGuest) {
            $model->is_anonymous = 1;
            $model->reporter_name = null;
        }

        $model->attachmentFiles = UploadedFile::getInstances($model, 'attachmentFiles');
        if (!$model->validate()) {
            $selectedLocation = null;
            if (!empty($model->location_id)) {
                $selectedLocation = $this->findLocation($model->location_id);
            }

            return $this->render('create', [
                'model' => $model,
                'selectedLocation' => $selectedLocation,
                'locationItems' => $this->getLocationItems(),
                'locationDetailRules' => $this->getLocationDetailRules(),
                'isGuest' => Yii::$app->user->isGuest,
            ]);
        }

        $draft = $this->getDraft();
        $draftId = is_array($draft) && !empty($draft['draftId'])
            ? (string) $draft['draftId']
            : Yii::$app->security->generateRandomString(16);

        $attachments = is_array($draft) && isset($draft['attachments']) && is_array($draft['attachments'])
            ? $draft['attachments']
            : [];

        if (!empty($model->attachmentFiles)) {
            $attachments = array_merge($attachments, $this->saveTemporaryAttachments($model->attachmentFiles, $draftId));
        }

        $draftData = [
            'draftId' => $draftId,
            'form' => $this->extractDraftFormData($model),
            'attachments' => $attachments,
            'reporterId' => Yii::$app->user->isGuest ? null : Yii::$app->user->id,
            'createdAt' => time(),
        ];

        Yii::$app->session->set(self::REPORT_DRAFT_SESSION_KEY, $draftData);

        $selectedLocation = null;
        if (!empty($model->location_id)) {
            $selectedLocation = $this->findLocation($model->location_id);
        }

        return $this->render('preview', [
            'draft' => $draftData,
            'selectedLocation' => $selectedLocation,
            'victimConditionOptions' => ReportSubmitForm::victimConditionOptions(),
        ]);
    }

    public function actionFinalize()
    {
        $draft = $this->getDraft();
        if (!is_array($draft) || empty($draft['form'])) {
            Yii::$app->session->setFlash('warning', 'Draft laporan tidak ditemukan. Silakan isi ulang laporan.');
            return $this->redirect(['create']);
        }

        $form = $draft['form'];
        $report = new Report();
        $report->location_id = (int) ($form['location_id'] ?? 0);
        $detailLokasi = trim((string) ($form['detail_lokasi'] ?? ''));
        $report->detail_lokasi = $this->isDetailLokasiRequiredForId($report->location_id)
            ? ($detailLokasi !== '' ? $detailLokasi : null)
            : null;
        $report->reporter_id = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $report->status = Report::STATUS_SUBMITTED;
        $report->incident_time = strtotime((string) ($form['incident_time_input'] ?? ''));
        $report->description = (string) ($form['description'] ?? '');
        $report->has_victim = (int) ((bool) ($form['has_victim'] ?? 0));
        $report->victim_name = $report->has_victim ? (($form['victim_name'] ?? null) ?: null) : null;
        $report->victim_condition = $report->has_victim ? (($form['victim_condition'] ?? null) ?: null) : null;
        $report->victim_condition_detail = ($report->has_victim && ($form['victim_condition'] ?? '') === ReportSubmitForm::VICTIM_CONDITION_INJURED)
            ? (($form['victim_condition_detail'] ?? null) ?: null)
            : null;
        $report->has_property_damage = (int) ((bool) ($form['has_property_damage'] ?? 0));
        $report->property_damage_detail = $report->has_property_damage ? (($form['property_damage_detail'] ?? null) ?: null) : null;
        $report->witness = (($form['witness'] ?? null) ?: null);
        $report->additional_notes = (($form['additional_notes'] ?? null) ?: null);
        $report->is_anonymous = (int) ((bool) ($form['is_anonymous'] ?? 0));
        $report->reporter_name = $report->is_anonymous === 1 ? 'Anonim' : (string) ($form['reporter_name'] ?? '');

        if (!$report->save()) {
            Yii::$app->session->setFlash('error', 'Finalisasi gagal. Silakan kembali ke form dan coba lagi.');
            return $this->redirect(['create', 'restore' => 1]);
        }

        $attachments = isset($draft['attachments']) && is_array($draft['attachments']) ? $draft['attachments'] : [];
        $this->moveTemporaryAttachmentsToReport($report->id, $attachments);

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
            . "Detail lokasi: " . ($report->detail_lokasi ?: '-') . "\n"
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

        if (!empty($draft['draftId'])) {
            $this->cleanupDraftTempDirectory((string) $draft['draftId']);
        }

        Yii::$app->session->remove(self::REPORT_DRAFT_SESSION_KEY);
        Yii::$app->session->remove('pendingReportLocationId');

        if (Yii::$app->user->isGuest) {
            Yii::$app->session->set(self::LAST_GUEST_REPORT_ID_SESSION_KEY, (int) $report->id);
            return $this->redirect(['success']);
        }

        return $this->redirect(['success', 'id' => $report->id]);
    }

    public function actionSuccess($id = null)
    {
        if (Yii::$app->user->isGuest) {
            $guestReportId = Yii::$app->session->get(self::LAST_GUEST_REPORT_ID_SESSION_KEY);
            if (empty($guestReportId)) {
                throw new NotFoundHttpException('Laporan tidak ditemukan.');
            }

            $report = Report::findOne((int) $guestReportId);
        } else {
            if ($id === null) {
                throw new NotFoundHttpException('Laporan tidak ditemukan.');
            }

            $report = Report::find()
                ->where(['id' => $id, 'reporter_id' => Yii::$app->user->id])
                ->one();
        }

        if ($report === null) {
            throw new NotFoundHttpException('Laporan tidak ditemukan.');
        }

        return $this->render('success', [
            'report' => $report,
        ]);
    }

    public function actionTrack()
    {
        $reportNumber = trim((string) Yii::$app->request->post('report_number', Yii::$app->request->get('report_number', '')));
        $reportNumber = strtoupper($reportNumber);
        $report = null;
        $searched = false;

        if ($reportNumber !== '') {
            $searched = true;
            $report = Report::find()
                ->with(['location', 'statusHistories'])
                ->where(['report_number' => $reportNumber])
                ->one();
        }

        return $this->render('track', [
            'reportNumber' => $reportNumber,
            'report' => $report,
            'searched' => $searched,
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

    private function buildSubmitFormModel($locationId = null)
    {
        $model = new ReportSubmitForm();
        if (!empty($locationId)) {
            $model->location_id = (int) $locationId;
        }
        $model->incident_time_input = date('Y-m-d\\TH:i');

        if (Yii::$app->user->isGuest) {
            $model->is_anonymous = 1;
            $model->reporter_name = null;
        } elseif (Yii::$app->user->identity !== null) {
            $model->reporter_name = Yii::$app->user->identity->username;
        }

        return $model;
    }

    private function getLocationItems()
    {
        $codes = Location::reportReferenceCodes();
        $locations = Location::find()
            ->where(['is_active' => 1])
            ->andWhere(['code' => $codes])
            ->indexBy('code')
            ->all();

        $locationItems = [
            'Internal Instansi' => [],
            'Eksternal/Luar Instansi' => [],
        ];

        $internalMap = [
            Location::CODE_INTERNAL_LANTAI_1 => '1) Gedung Utama Lantai 1',
            Location::CODE_INTERNAL_LANTAI_2 => '2) Gedung Utama Lantai 2',
            Location::CODE_INTERNAL_LANTAI_3 => '3) Gedung Utama Lantai 3',
            Location::CODE_INTERNAL_ROOFTOP => '4) Rooftop',
            Location::CODE_INTERNAL_GEDUNG_LAMA => '5) Gedung Lama (Pelatihan)',
            Location::CODE_INTERNAL_HALAMAN_APEL => '6) Halaman apel',
            Location::CODE_INTERNAL_LAINNYA => '7) Lainnya',
        ];

        foreach ($internalMap as $code => $label) {
            if (isset($locations[$code])) {
                $locationItems['Internal Instansi'][$locations[$code]->id] = $label;
            }
        }

        if (isset($locations[Location::CODE_EKSTERNAL])) {
            $locationItems['Eksternal/Luar Instansi'][$locations[Location::CODE_EKSTERNAL]->id] = 'Eksternal/Luar Instansi';
        }

        if (empty($locationItems['Internal Instansi'])) {
            unset($locationItems['Internal Instansi']);
        }

        if (empty($locationItems['Eksternal/Luar Instansi'])) {
            unset($locationItems['Eksternal/Luar Instansi']);
        }

        return $locationItems;
    }

    private function getLocationDetailRules()
    {
        $rules = [];
        $locations = Location::find()
            ->where(['is_active' => 1])
            ->andWhere(['code' => Location::reportReferenceCodes()])
            ->all();

        foreach ($locations as $location) {
            $requiresDetail = (int) $location->jenis_lokasi === Location::JENIS_LOKASI_EKSTERNAL
                || (string) $location->code === Location::CODE_INTERNAL_LAINNYA;

            $placeholder = (int) $location->jenis_lokasi === Location::JENIS_LOKASI_EKSTERNAL
                ? 'Isikan detail lokasi (misal di jalan/perusahaan)'
                : 'Isi detail lokasi';

            $rules[(string) $location->id] = [
                'requires_detail' => $requiresDetail,
                'placeholder' => $placeholder,
            ];
        }

        return $rules;
    }

    private function extractDraftFormData(ReportSubmitForm $model)
    {
        return [
            'location_id' => (int) $model->location_id,
            'detail_lokasi' => (string) $model->detail_lokasi,
            'incident_time_input' => (string) $model->incident_time_input,
            'description' => (string) $model->description,
            'has_victim' => (int) ((bool) $model->has_victim),
            'victim_name' => $model->victim_name,
            'victim_condition' => $model->victim_condition,
            'victim_condition_detail' => $model->victim_condition_detail,
            'has_property_damage' => (int) ((bool) $model->has_property_damage),
            'property_damage_detail' => $model->property_damage_detail,
            'witness' => $model->witness,
            'additional_notes' => $model->additional_notes,
            'is_anonymous' => (int) ((bool) $model->is_anonymous),
            'reporter_name' => $model->reporter_name,
        ];
    }

    private function fillModelFromDraft(ReportSubmitForm $model, array $form)
    {
        $model->location_id = (int) ($form['location_id'] ?? $model->location_id);
        $model->detail_lokasi = (string) ($form['detail_lokasi'] ?? '');
        $model->incident_time_input = (string) ($form['incident_time_input'] ?? $model->incident_time_input);
        $model->description = (string) ($form['description'] ?? '');
        $model->has_victim = (int) ((bool) ($form['has_victim'] ?? 0));
        $model->victim_name = $form['victim_name'] ?? null;
        $model->victim_condition = $form['victim_condition'] ?? null;
        $model->victim_condition_detail = $form['victim_condition_detail'] ?? null;
        $model->has_property_damage = (int) ((bool) ($form['has_property_damage'] ?? 0));
        $model->property_damage_detail = $form['property_damage_detail'] ?? null;
        $model->witness = $form['witness'] ?? null;
        $model->additional_notes = $form['additional_notes'] ?? null;
        $model->is_anonymous = (int) ((bool) ($form['is_anonymous'] ?? 0));
        $model->reporter_name = $form['reporter_name'] ?? null;
    }

    private function isDetailLokasiRequiredForId($locationId)
    {
        $location = Location::findOne((int) $locationId);
        if ($location === null) {
            return false;
        }

        return (int) $location->jenis_lokasi === Location::JENIS_LOKASI_EKSTERNAL
            || (string) $location->code === Location::CODE_INTERNAL_LAINNYA;
    }

    private function getDraft()
    {
        $draft = Yii::$app->session->get(self::REPORT_DRAFT_SESSION_KEY);
        return is_array($draft) ? $draft : null;
    }

    private function saveTemporaryAttachments(array $files, $draftId)
    {
        $saved = [];
        $relativeDirectory = 'temp-report-drafts/' . $draftId;
        $baseDirectory = Yii::getAlias(Yii::$app->params['app.uploadPath']);
        $targetDirectory = $baseDirectory . DIRECTORY_SEPARATOR . $relativeDirectory;
        FileHelper::createDirectory($targetDirectory, 0775, true);

        foreach ($files as $file) {
            $safeName = Yii::$app->security->generateRandomString(24) . '.' . $file->extension;
            $relativePath = $relativeDirectory . '/' . $safeName;
            $fullPath = $baseDirectory . DIRECTORY_SEPARATOR . $relativePath;

            if (!$file->saveAs($fullPath)) {
                continue;
            }

            $saved[] = [
                'tmp_path' => $relativePath,
                'original_name' => $file->baseName . '.' . $file->extension,
                'mime_type' => $file->type,
                'file_size' => $file->size,
            ];
        }

        return $saved;
    }

    private function moveTemporaryAttachmentsToReport($reportId, array $attachments)
    {
        if (empty($attachments)) {
            return;
        }

        $baseDirectory = Yii::getAlias(Yii::$app->params['app.uploadPath']);
        $relativeDirectory = 'reports/' . date('Y/m');
        $targetDirectory = $baseDirectory . DIRECTORY_SEPARATOR . $relativeDirectory;
        FileHelper::createDirectory($targetDirectory, 0775, true);

        foreach ($attachments as $attachment) {
            $tmpPath = (string) ($attachment['tmp_path'] ?? '');
            if ($tmpPath === '') {
                continue;
            }

            $source = $baseDirectory . DIRECTORY_SEPARATOR . $tmpPath;
            if (!is_file($source)) {
                continue;
            }

            $extension = strtolower(pathinfo((string) ($attachment['original_name'] ?? ''), PATHINFO_EXTENSION));
            if ($extension === '') {
                $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
            }

            $safeName = Yii::$app->security->generateRandomString(24) . ($extension !== '' ? '.' . $extension : '');
            $relativePath = $relativeDirectory . '/' . $safeName;
            $destination = $baseDirectory . DIRECTORY_SEPARATOR . $relativePath;

            if (!@rename($source, $destination)) {
                if (!@copy($source, $destination)) {
                    continue;
                }
                @unlink($source);
            }

            $reportAttachment = new ReportAttachment();
            $reportAttachment->report_id = (int) $reportId;
            $reportAttachment->file_path = $relativePath;
            $reportAttachment->original_name = (string) ($attachment['original_name'] ?? basename($relativePath));
            $reportAttachment->mime_type = (string) ($attachment['mime_type'] ?? '');
            $reportAttachment->file_size = (int) ($attachment['file_size'] ?? filesize($destination));
            $reportAttachment->save(false);
        }
    }

    private function cleanupDraftTempDirectory($draftId)
    {
        $baseDirectory = Yii::getAlias(Yii::$app->params['app.uploadPath']);
        $draftDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'temp-report-drafts' . DIRECTORY_SEPARATOR . $draftId;
        if (is_dir($draftDirectory)) {
            FileHelper::removeDirectory($draftDirectory);
        }
    }
}