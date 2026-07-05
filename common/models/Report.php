<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use common\models\ReportStatusHistory;

class Report extends ActiveRecord
{
    public const STATUS_SUBMITTED = '1'; //secara default laporan dianggap valid
    public const STATUS_NOT_APPROVED = '0';
    public const STATUS_SECRETARY_REVIEW = '6'; //JIKA DITOLAK KETUA TIM K3L, STATUSNYA AKAN KEMBALI KE SEKRETARIS UNTUK DITINDAKLANJUTI
    
    public const STATUS_SECRETARY_FINALIZED = '3';
    public const STATUS_TEAM_APPROVED = '2'; 
    public const STATUS_COORDINATOR_FOLLOW_UP = '4';
    public const STATUS_CLOSED = '5';

    public const VICTIM_CONDITION_CONSCIOUS = 'conscious';
    public const VICTIM_CONDITION_UNCONSCIOUS = 'unconscious';
    public const VICTIM_CONDITION_INJURED = 'injured_or_sick';
    public const VICTIM_CONDITION_NOT_INJURED = 'not_injured';

    public const CAUSE_GROUP_DIRECT = 'direct';
    public const CAUSE_GROUP_INDIRECT = 'indirect';

    public const CAUSE_SUBTYPE_UNSAFE_BEHAVIOR = 'unsafe_behavior';
    public const CAUSE_SUBTYPE_UNSAFE_CONDITION = 'unsafe_condition';
    public const CAUSE_SUBTYPE_PERSONAL = 'personal';
    public const CAUSE_SUBTYPE_WORK = 'work';

    public const PIC_UNIT_HEAD = 'kepala_balai';
    public const PIC_UNIT_SUBADMIN = 'kasubbag_tata_usaha';
    public const PIC_UNIT_K3L_CHAIR = 'ketua_tim_k3l';
    public const PIC_UNIT_K3L_SECRETARY = 'sekretaris_tim_k3l';
    public const PIC_UNIT_OCCUPATIONAL_HEALTH = 'bidang_kesehatan_kerja';
    public const PIC_UNIT_OCCUPATIONAL_SAFETY = 'bidang_keselamatan_kerja';
    public const PIC_UNIT_WORK_ENVIRONMENT = 'bidang_lingkungan_kerja';
    public const PIC_UNIT_HYGIENE = 'bidang_higiene_sanitasi_lingkungan';
    public const PIC_UNIT_HRD_K3 = 'bidang_pengembangan_sdm_k3';

    public static function statusLabelOptions()
    {
        return [
            self::STATUS_NOT_APPROVED => 'Tidak Disetujui Sekretaris',
            self::STATUS_SUBMITTED => 'Dikirimkan ke Sekretaris',
            self::STATUS_TEAM_APPROVED => 'Dikirimkan ke Ketua Tim K3L',
            self::STATUS_SECRETARY_FINALIZED => 'Finalisasi Tindakan Sekretaris',
            self::STATUS_COORDINATOR_FOLLOW_UP => 'Tindak Lanjut Koordinator Bidang',
            self::STATUS_SECRETARY_REVIEW => 'Review Ulang',
            self::STATUS_CLOSED => 'Tindak Lanjut Koordinator Bidang',
        ];
    }

    public static function statusLabel($status)
    {
        $labels = self::statusLabelOptions();
        $key = (string) $status;

        return $labels[$key] ?? '-';
    }

    public static function victimConditionOptions()
    {
        return [
            self::VICTIM_CONDITION_CONSCIOUS => 'Sadar',
            self::VICTIM_CONDITION_UNCONSCIOUS => 'Tidak sadar',
            self::VICTIM_CONDITION_INJURED => 'Cedera/luka/sakit',
            self::VICTIM_CONDITION_NOT_INJURED => 'Tidak cedera',
        ];
    }

    public static function causeGroupOptions()
    {
        return [
            self::CAUSE_GROUP_DIRECT => 'Penyebab langsung',
            self::CAUSE_GROUP_INDIRECT => 'Penyebab tidak langsung',
        ];
    }

    public static function causeSubtypeOptionsByGroup()
    {
        return [
            self::CAUSE_GROUP_DIRECT => [
                self::CAUSE_SUBTYPE_UNSAFE_BEHAVIOR => 'Perilaku tidak aman',
                self::CAUSE_SUBTYPE_UNSAFE_CONDITION => 'Kondisi tidak aman',
            ],
            self::CAUSE_GROUP_INDIRECT => [
                self::CAUSE_SUBTYPE_PERSONAL => 'Personal',
                self::CAUSE_SUBTYPE_WORK => 'Pekerjaan',
            ],
        ];
    }

    public static function causeSubtypeOptions()
    {
        return array_merge(
            self::causeSubtypeOptionsByGroup()[self::CAUSE_GROUP_DIRECT],
            self::causeSubtypeOptionsByGroup()[self::CAUSE_GROUP_INDIRECT]
        );
    }

    public function getCauseGroupLabel()
    {
        $options = self::causeGroupOptions();
        return $options[$this->cause_group] ?? '-';
    }

    public function getCauseSubtypeLabel()
    {
        $optionsByGroup = self::causeSubtypeOptionsByGroup();
        if (isset($optionsByGroup[$this->cause_group][$this->cause_subtype])) {
            return $optionsByGroup[$this->cause_group][$this->cause_subtype];
        }

        $flatOptions = self::causeSubtypeOptions();
        return $flatOptions[$this->cause_subtype] ?? '-';
    }

    public static function picUnitOptions()
    {
        return [
            self::PIC_UNIT_HEAD => 'Kepala Balai',
            self::PIC_UNIT_SUBADMIN => 'Kasubbag Tata Usaha',
            self::PIC_UNIT_K3L_CHAIR => 'Ketua Tim K3L',
            self::PIC_UNIT_K3L_SECRETARY => 'Sekretaris Tim K3L',
            self::PIC_UNIT_OCCUPATIONAL_HEALTH => 'Bidang Kesehatan Kerja',
            self::PIC_UNIT_OCCUPATIONAL_SAFETY => 'Bidang Keselamatan Kerja',
            self::PIC_UNIT_WORK_ENVIRONMENT => 'Bidang Lingkungan Kerja',
            self::PIC_UNIT_HYGIENE => 'Bidang Higiene dan Sanitasi Lingkungan',
            self::PIC_UNIT_HRD_K3 => 'Bidang Pengembangan SDM K3',
        ];
    }

    public function getPicDisplayLabel()
    {
        $unitOptions = self::picUnitOptions();
        $unitLabel = $unitOptions[$this->pic_unit] ?? '-';
        $picName = trim((string) $this->pic_name);

        if ($unitLabel === '-' && $picName === '' && $this->picUser) {
            return (string) $this->picUser->username;
        }

        if ($picName === '') {
            return $unitLabel;
        }

        if ($unitLabel === '-') {
            return $picName;
        }

        return $unitLabel . ' - ' . $picName;
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
            [['description', 'recommendation', 'coordinator_follow_up_note', 'victim_condition_detail', 'property_damage_detail', 'witness', 'additional_notes', 'missing_data_note', 'detail_lokasi'], 'string'],
            [['status'], 'string', 'max' => 32],
            [['incident_type'], 'string', 'max' => 128],
            [['cause_group'], 'string', 'max' => 32],
            [['cause_subtype'], 'string', 'max' => 64],
            [['pic_unit'], 'string', 'max' => 128],
            [['pic_name'], 'string', 'max' => 255],
            [['victim_condition'], 'in', 'range' => array_keys(self::victimConditionOptions())],
            [['cause_group'], 'in', 'range' => array_keys(self::causeGroupOptions())],
            [['cause_subtype'], 'in', 'range' => array_keys(self::causeSubtypeOptions())],
            [['pic_unit'], 'in', 'range' => array_keys(self::picUnitOptions())],
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
            'detail_lokasi' => 'Detail lokasi',
            'missing_data_note' => 'Data yang kurang',
            'cause_group' => 'Penyebab kejadian',
            'cause_subtype' => 'Jenis penyebab kejadian',
            'pic_unit' => 'PIC unit kerja',
            'pic_name' => 'Nama PIC',
            'is_anonymous' => 'Laporan anonim',
            'reporter_name' => 'Nama pelapor',
            'created_at' => 'Waktu Pelaporan',
        ];
    }

    public function beforeValidate()
    {
        if ($this->isNewRecord && empty($this->report_number)) {
            $this->report_number = $this->generateReportNumber();
        }

        if (empty($this->status)) {
            $this->status = self::STATUS_SUBMITTED;
        }

        return parent::beforeValidate();
    }

    private function generateReportNumber()
    {
        $datePart = date('Ymd');
        $prefix = 'K3L/' . $datePart . '-';

        $lastNumber = static::find()
            ->select('report_number')
            ->where(['like', 'report_number', $prefix . '%', false])
            ->orderBy(['report_number' => SORT_DESC])
            ->scalar();

        $lastSequence = 0;
        if (is_string($lastNumber) && str_starts_with($lastNumber, $prefix)) {
            $candidate = substr($lastNumber, -5);
            if (ctype_digit($candidate)) {
                $lastSequence = (int) $candidate;
            }
        }

        return $prefix . str_pad((string) ($lastSequence + 1), 5, '0', STR_PAD_LEFT);
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