<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ref_incident_category".
 *
 * @property int $id
 * @property string $name
 * @property int $status
 *
 * @property RefIncidentType[] $refIncidentTypes
 */
class RefIncidentCategory extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ref_incident_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'default', 'value' => 1],
            [['name'], 'required'],
            [['status'], 'integer'],
            [['name'], 'string', 'max' => 150],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'status' => 'Status',
        ];
    }

    /**
     * Gets query for [[RefIncidentTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRefIncidentTypes()
    {
        return $this->hasMany(RefIncidentType::class, ['incident_category_id' => 'id']);
    }

}
