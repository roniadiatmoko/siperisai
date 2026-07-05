<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use common\models\Report;

class ReportAttachment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%report_attachment}}';
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
            [['report_id', 'file_path', 'original_name'], 'required'],
            [['report_id', 'file_size'], 'integer'],
            [['file_path', 'original_name'], 'string', 'max' => 255],
            [['mime_type'], 'string', 'max' => 128],
        ];
    }

    public function getReport()
    {
        return $this->hasOne(Report::class, ['id' => 'report_id']);
    }
}