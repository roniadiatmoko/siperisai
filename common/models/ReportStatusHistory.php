<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use common\models\Report;

class ReportStatusHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%report_status_history}}';
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
            [['report_id', 'status_to'], 'required'],
            [['report_id', 'created_by'], 'integer'],
            [['note'], 'string'],
            [['status_from', 'status_to'], 'string', 'max' => 32],
        ];
    }

    public function getReport()
    {
        return $this->hasOne(Report::class, ['id' => 'report_id']);
    }
}