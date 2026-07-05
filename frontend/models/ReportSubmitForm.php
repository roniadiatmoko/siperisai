<?php

namespace frontend\models;

use common\models\Location;
use common\models\Report;
use common\models\ReportAttachment;
use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class ReportSubmitForm extends Model
{
    public $location_id;
    public $detail_lokasi;
    public $incident_time_input;
    public $description;
    public $has_victim = 0;
    public $victim_name;
    public $victim_condition;
    public $victim_condition_detail;
    public $has_property_damage = 0;
    public $property_damage_detail;
    public $witness;
    public $additional_notes;
    public $is_anonymous = 0;
    public $reporter_name;
    public $attachmentFiles;

    public const VICTIM_CONDITION_CONSCIOUS = 'conscious';
    public const VICTIM_CONDITION_UNCONSCIOUS = 'unconscious';
    public const VICTIM_CONDITION_INJURED = 'injured_or_sick';
    public const VICTIM_CONDITION_NOT_INJURED = 'not_injured';

    public static function victimConditionOptions()
    {
        return [
            self::VICTIM_CONDITION_CONSCIOUS => 'Sadar',
            self::VICTIM_CONDITION_UNCONSCIOUS => 'Tidak sadar',
            self::VICTIM_CONDITION_INJURED => 'Cedera/luka/sakit',
            self::VICTIM_CONDITION_NOT_INJURED => 'Tidak cedera',
        ];
    }

    public function rules()
    {
        return [
            [['location_id', 'incident_time_input', 'description'], 'required'],
            [['location_id'], 'integer'],
            [['location_id'], 'exist', 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
            [['description', 'detail_lokasi', 'victim_condition_detail', 'property_damage_detail', 'witness', 'additional_notes'], 'string'],
            [['has_victim', 'has_property_damage', 'is_anonymous'], 'boolean'],
            [['has_victim', 'has_property_damage', 'is_anonymous'], 'default', 'value' => 0],
            [['victim_name', 'reporter_name'], 'string', 'max' => 255],
            [['victim_condition'], 'in', 'range' => array_keys(self::victimConditionOptions())],
            [['victim_name', 'victim_condition'], 'required', 'when' => function (self $model) {
                return (bool) $model->has_victim;
            }, 'whenClient' => "function () { return $('input[name=\"ReportSubmitForm[has_victim]\"]:checked').val() === '1'; }"],
            [['victim_condition_detail'], 'required', 'when' => function (self $model) {
                return (bool) $model->has_victim && $model->victim_condition === self::VICTIM_CONDITION_INJURED;
            }, 'whenClient' => "function () { return $('input[name=\"ReportSubmitForm[has_victim]\"]:checked').val() === '1' && $('#reportsubmitform-victim_condition').val() === '" . self::VICTIM_CONDITION_INJURED . "'; }"],
            [['property_damage_detail'], 'required', 'when' => function (self $model) {
                return (bool) $model->has_property_damage;
            }, 'whenClient' => "function () { return $('input[name=\"ReportSubmitForm[has_property_damage]\"]:checked').val() === '1'; }"],
            [['reporter_name'], 'required', 'when' => function (self $model) {
                return !(bool) $model->is_anonymous;
            }, 'whenClient' => "function () { return !$('#reportsubmitform-is_anonymous').is(':checked'); }"],
            [['detail_lokasi'], 'required', 'when' => function (self $model) {
                return $model->isDetailLokasiRequired();
            }, 'whenClient' => "function () { return !!window.requiresDetailLokasiForSelectedLocation && window.requiresDetailLokasiForSelectedLocation(); }"],
            [['incident_time_input'], 'string', 'max' => 32],
            [['attachmentFiles'], 'file', 'skipOnEmpty' => true, 'maxFiles' => 5, 'extensions' => ['jpg', 'jpeg', 'png', 'webp']],
        ];
    }

    private function isDetailLokasiRequired()
    {
        $locationId = (int) $this->location_id;
        if ($locationId <= 0) {
            return false;
        }

        $location = Location::findOne($locationId);
        if ($location === null) {
            return false;
        }

        return true;
    }

    public function attributeLabels()
    {
        return [
            'location_id' => 'Lokasi',
            'detail_lokasi' => 'Detail Lokasi',
            'incident_time_input' => 'Waktu Kejadian',
            'description' => 'Deskripsi Kejadian',
            'has_victim' => 'Apakah ada korban?',
            'victim_name' => 'Nama Korban',
            'victim_condition' => 'Kondisi Korban',
            'victim_condition_detail' => 'Bagian yang cedera/luka/sakit',
            'has_property_damage' => 'Apakah ada kerusakan sarana/prasarana?',
            'property_damage_detail' => 'Detail kerusakan',
            'witness' => 'Saksi kejadian (jika ada)',
            'additional_notes' => 'Catatan lain-lain (usulan perbaikan)',
            'is_anonymous' => 'Laporkan sebagai anonim',
            'reporter_name' => 'Nama Pelapor',
            'attachmentFiles' => 'Upload Foto Kejadian',
        ];
    }

    public function save($reporterId)
    {
        if (!$this->validate()) {
            return false;
        }

        $report = new Report();
        $report->location_id = $this->location_id;
        $report->detail_lokasi = $this->detail_lokasi;
        $report->reporter_id = $reporterId;
        $report->status = Report::STATUS_SUBMITTED;
        $report->incident_time = strtotime($this->incident_time_input);
        $report->description = $this->description;
        $report->has_victim = (int) ((bool) $this->has_victim);
        $report->victim_name = $report->has_victim ? $this->victim_name : null;
        $report->victim_condition = $report->has_victim ? $this->victim_condition : null;
        $report->victim_condition_detail = ($report->has_victim && $this->victim_condition === self::VICTIM_CONDITION_INJURED)
            ? $this->victim_condition_detail
            : null;
        $report->has_property_damage = (int) ((bool) $this->has_property_damage);
        $report->property_damage_detail = $report->has_property_damage ? $this->property_damage_detail : null;
        $report->witness = $this->witness;
        $report->additional_notes = $this->additional_notes;
        $report->is_anonymous = (int) ((bool) $this->is_anonymous);
        $report->reporter_name = $report->is_anonymous ? 'Anonim' : $this->reporter_name;

        if (!$report->save()) {
            $this->addErrors($report->getErrors());
            return false;
        }

        $files = UploadedFile::getInstances($this, 'attachmentFiles');
        foreach ($files as $file) {
            $this->saveAttachment($report->id, $file);
        }

        return $report;
    }

    protected function saveAttachment($reportId, UploadedFile $file)
    {
        $relativeDirectory = 'reports/' . date('Y/m');
        $baseDirectory = Yii::getAlias(Yii::$app->params['app.uploadPath']);
        $fullDirectory = $baseDirectory . DIRECTORY_SEPARATOR . $relativeDirectory;
        FileHelper::createDirectory($fullDirectory, 0775, true);

        $safeName = Yii::$app->security->generateRandomString(24) . '.' . $file->extension;
        $relativePath = $relativeDirectory . '/' . $safeName;
        $fullPath = $baseDirectory . DIRECTORY_SEPARATOR . $relativePath;

        if (!$file->saveAs($fullPath)) {
            return false;
        }

        $attachment = new ReportAttachment();
        $attachment->report_id = $reportId;
        $attachment->file_path = $relativePath;
        $attachment->original_name = $file->baseName . '.' . $file->extension;
        $attachment->mime_type = $file->type;
        $attachment->file_size = $file->size;

        return $attachment->save(false);
    }
}