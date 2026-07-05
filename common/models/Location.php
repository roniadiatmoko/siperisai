<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use common\models\Report;

class Location extends ActiveRecord
{
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
            [['is_active', 'created_by'], 'integer'],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 255],
            [['qr_token'], 'string', 'max' => 128],
            [['code', 'qr_token'], 'unique'],
            ['is_active', 'default', 'value' => 1],
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