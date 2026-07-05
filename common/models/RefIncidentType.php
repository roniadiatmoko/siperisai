<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ref_incident_type".
 *
 * @property int $id
 * @property int $incident_category_id
 * @property string $name
 * @property int $status
 *
 * @property RefIncidentCategory $incidentCategory
 */
class RefIncidentType extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ref_incident_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'default', 'value' => 1],
            [['incident_category_id', 'name'], 'required'],
            [['incident_category_id', 'status'], 'integer'],
            [['name'], 'string', 'max' => 150],
            [['incident_category_id'], 'exist', 'skipOnError' => true, 'targetClass' => RefIncidentCategory::class, 'targetAttribute' => ['incident_category_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'incident_category_id' => 'Incident Category ID',
            'name' => 'Name',
            'status' => 'Status',
        ];
    }

    /**
     * Gets query for [[IncidentCategory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIncidentCategory()
    {
        return $this->hasOne(RefIncidentCategory::class, ['id' => 'incident_category_id']);
    }

}
