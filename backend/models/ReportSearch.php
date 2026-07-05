<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Report;

/**
 * ReportSearch represents the model behind the search form of `common\models\Report`.
 */
class ReportSearch extends Report
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'location_id', 'reporter_id', 'incident_time', 'pic_user_id', 'secretary_id', 'secretary_finalized_at', 'team_lead_id', 'team_lead_approved_at', 'coordinator_id', 'coordinator_follow_up_at', 'created_at', 'updated_at', 'has_victim', 'has_property_damage', 'is_anonymous'], 'integer'],
            [['report_number', 'status', 'description', 'incident_type', 'recommendation', 'missing_data_note', 'coordinator_follow_up_note', 'victim_name', 'victim_condition', 'victim_condition_detail', 'property_damage_detail', 'witness', 'additional_notes', 'reporter_name'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param string|null $queue Queue name to filter the reports.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $queue = null)
    {
        $query = Report::find()
            ->with(['location', 'reporter'])
            ->orderBy([
                'created_at' => SORT_DESC
            ]);

        switch ($queue) {

            case 'secretary':
                $query->andWhere([
                    'status' => [
                        Report::STATUS_SUBMITTED,
                        Report::STATUS_SECRETARY_REVIEW,
                    ]
                ]);
                break;

            case 'rejected':
                $query->andWhere([
                    'status' => Report::STATUS_NOT_APPROVED
                ]);
                break;

            case 'tindakan':
                $query->andWhere([
                    'status' => [
                        Report::STATUS_SECRETARY_FINALIZED,
                        Report::STATUS_COORDINATOR_FOLLOW_UP,
                    ]
                ]);
                break;

            case 'teamLead':
                $query->andWhere([
                    'status' => Report::STATUS_TEAM_APPROVED
                ]);
                break;

            case 'coordinator':
                $query->andWhere([
                    'status' => Report::STATUS_COORDINATOR_FOLLOW_UP
                ]);
                break;
        }

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        // if (!$this->validate()) {
        //     // uncomment the following line if you do not want to return any records when validation fails
        //     // $query->where('0=1');
        //     return $dataProvider;
        // }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'location_id' => $this->location_id,
            'reporter_id' => $this->reporter_id,
            'incident_time' => $this->incident_time,
            'pic_user_id' => $this->pic_user_id,
            'secretary_id' => $this->secretary_id,
            'secretary_finalized_at' => $this->secretary_finalized_at,
            'team_lead_id' => $this->team_lead_id,
            'team_lead_approved_at' => $this->team_lead_approved_at,
            'coordinator_id' => $this->coordinator_id,
            'coordinator_follow_up_at' => $this->coordinator_follow_up_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'has_victim' => $this->has_victim,
            'has_property_damage' => $this->has_property_damage,
            'is_anonymous' => $this->is_anonymous,
        ]);

        $query->andFilterWhere(['like', 'report_number', $this->report_number])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'incident_type', $this->incident_type])
            ->andFilterWhere(['like', 'recommendation', $this->recommendation])
            ->andFilterWhere(['like', 'missing_data_note', $this->missing_data_note])
            ->andFilterWhere(['like', 'coordinator_follow_up_note', $this->coordinator_follow_up_note])
            ->andFilterWhere(['like', 'victim_name', $this->victim_name])
            ->andFilterWhere(['like', 'victim_condition', $this->victim_condition])
            ->andFilterWhere(['like', 'victim_condition_detail', $this->victim_condition_detail])
            ->andFilterWhere(['like', 'property_damage_detail', $this->property_damage_detail])
            ->andFilterWhere(['like', 'witness', $this->witness])
            ->andFilterWhere(['like', 'additional_notes', $this->additional_notes])
            ->andFilterWhere(['like', 'reporter_name', $this->reporter_name]);

        return $dataProvider;
    }
}
