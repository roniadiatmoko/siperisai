<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use common\models\Report;

class Location extends ActiveRecord
{
    public const JENIS_LOKASI_INTERNAL = 1;
    public const JENIS_LOKASI_EKSTERNAL = 2;

    public const CODE_INTERNAL_LANTAI_1 = 'INT-L1';
    public const CODE_INTERNAL_LANTAI_2 = 'INT-L2';
    public const CODE_INTERNAL_LANTAI_3 = 'INT-L3';
    public const CODE_INTERNAL_ROOFTOP = 'INT-L4';
    public const CODE_INTERNAL_GEDUNG_LAMA = 'INT-L5';
    public const CODE_INTERNAL_HALAMAN_APEL = 'INT-L6';
    public const CODE_INTERNAL_LAINNYA = 'INT-L7';
    public const CODE_EKSTERNAL = 'EXT-001';

    public static function reportReferenceCodes()
    {
        return [
            self::CODE_INTERNAL_LANTAI_1,
            self::CODE_INTERNAL_LANTAI_2,
            self::CODE_INTERNAL_LANTAI_3,
            self::CODE_INTERNAL_ROOFTOP,
            self::CODE_INTERNAL_GEDUNG_LAMA,
            self::CODE_INTERNAL_HALAMAN_APEL,
            self::CODE_INTERNAL_LAINNYA,
            self::CODE_EKSTERNAL,
        ];
    }

    public static function jenisLokasiOptions()
    {
        return [
            self::JENIS_LOKASI_INTERNAL => 'Internal Instansi',
            self::JENIS_LOKASI_EKSTERNAL => 'Eksternal/Luar Instansi',
        ];
    }

    public static function tableName()
    {
        return '{{%location}}';
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
            [['code', 'name'], 'required'],
            [['description'], 'string'],
            [['is_active', 'created_by', 'jenis_lokasi'], 'integer'],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 255],
            [['qr_token'], 'string', 'max' => 128],
            [['jenis_lokasi'], 'in', 'range' => array_keys(self::jenisLokasiOptions())],
            [['code', 'qr_token'], 'unique'],
            ['is_active', 'default', 'value' => 1],
            ['jenis_lokasi', 'default', 'value' => self::JENIS_LOKASI_INTERNAL],
        ];
    }

    public function beforeValidate()
    {
        if ($this->isNewRecord && empty($this->qr_token)) {
            $this->qr_token = \Yii::$app->security->generateRandomString(32);
        }

        return parent::beforeValidate();
    }

    public function getReports()
    {
        return $this->hasMany(Report::class, ['location_id' => 'id']);
    }
}