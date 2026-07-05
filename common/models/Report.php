<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use common\models\ReportStatusHistory;

class Report extends ActiveRecord
{
    public const STATUS_NOT_APPROVED = '0';
    public const STATUS_SUBMITTED = '1';
    public const STATUS_SECRETARY_REVIEW = 'secretary_review';
    public const STATUS_SECRETARY_FINALIZED = '3';
    public const STATUS_TEAM_APPROVED = '2';
    public const STATUS_COORDINATOR_FOLLOW_UP = '4';
    public const STATUS_CLOSED = '5';

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

    public static function tableName()
    {
        return '{{%report}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['report_number', 'location_id', 'incident_time', 'description'], 'required'],
            [['location_id', 'reporter_id', 'incident_time', 'pic_user_id', 'secretary_id', 'secretary_finalized_at', 'team_lead_id', 'team_lead_approved_at', 'coordinator_id', 'coordinator_follow_up_at', 'has_victim', 'has_property_damage', 'is_anonymous'], 'integer'],
            [['description', 'recommendation', 'coordinator_follow_up_note', 'victim_condition_detail', 'property_damage_detail', 'witness', 'additional_notes', 'missing_data_note'], 'string'],
            [['status'], 'string', 'max' => 32],
            [['incident_type'], 'string', 'max' => 128],
            [['victim_condition'], 'in', 'range' => array_keys(self::victimConditionOptions())],
            [['victim_name', 'reporter_name'], 'string', 'max' => 255],
            [['report_number'], 'string', 'max' => 32],
            [['report_number'], 'unique'],
            [['status'], 'default', 'value' => self::STATUS_SUBMITTED],
            [['has_victim', 'has_property_damage', 'is_anonymous'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'has_victim' => 'Apakah ada korban?',
            'victim_name' => 'Nama Korban',
            'victim_condition' => 'Kondisi Korban',
            'victim_condition_detail' => 'Bagian yang cedera/luka/sakit',
            'has_property_damage' => 'Apakah ada kerusakan sarana/prasarana?',
            'property_damage_detail' => 'Detail kerusakan',
            'witness' => 'Saksi kejadian',
            'additional_notes' => 'Catatan lain-lain',
            'missing_data_note' => 'Data yang kurang',
            'is_anonymous' => 'Laporan anonim',
            'reporter_name' => 'Nama pelapor',
            'created_at' => 'Waktu Pelaporan',
        ];
    }

    public function beforeValidate()
    {
        if ($this->isNewRecord && empty($this->report_number)) {
            $this->report_number = 'RPT-' . date('Ymd') . '-' . strtoupper(\Yii::$app->security->generateRandomString(6));
        }

        if (empty($this->status)) {
            $this->status = self::STATUS_SUBMITTED;
        }

        return parent::beforeValidate();
    }

    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    public function getReporter()
    {
        return $this->hasOne(User::class, ['id' => 'reporter_id']);
    }

    public function getPicUser()
    {
        return $this->hasOne(User::class, ['id' => 'pic_user_id']);
    }

    public function getIncidentType()
    {
        return $this->hasOne(RefIncidentType::class, ['id' => 'incident_type']);
    }

    public function getSecretary()
    {
        return $this->hasOne(User::class, ['id' => 'secretary_id']);
    }

    public function getTeamLead()
    {
        return $this->hasOne(User::class, ['id' => 'team_lead_id']);
    }

    public function getCoordinator()
    {
        return $this->hasOne(User::class, ['id' => 'coordinator_id']);
    }

    public function getAttachments()
    {
        return $this->hasMany(ReportAttachment::class, ['report_id' => 'id']);
    }

    public function getStatusHistories()
    {
        return $this->hasMany(ReportStatusHistory::class, ['report_id' => 'id'])->orderBy(['created_at' => SORT_ASC]);
    }

    public function transitionTo($status, $userId = null, $note = null)
    {
        $previousStatus = $this->status;
        $this->status = $status;

        if (!$this->save(false, ['status'])) {
            return false;
        }

        $history = new ReportStatusHistory();
        $history->report_id = $this->id;
        $history->status_from = $previousStatus;
        $history->status_to = $status;
        $history->note = $note;
        $history->created_by = $userId;

        return $history->save(false);
    }
}