<?php

use yii\db\Migration;

class m260705_121000_add_view_report_analytics_permission extends Migration
{
    public function safeUp()
    {
        $permissionName = 'viewReportAnalytics';
        $now = time();

        $exists = (new \yii\db\Query())
            ->from('{{%auth_item}}')
            ->where(['name' => $permissionName])
            ->exists($this->db);

        if (!$exists) {
            $this->insert('{{%auth_item}}', [
                'name' => $permissionName,
                'type' => 2,
                'description' => 'Lihat laporan grafik backend',
                'rule_name' => null,
                'data' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roles = ['secretary_k3l', 'team_lead_k3l', 'coordinator_head'];
        foreach ($roles as $role) {
            $childExists = (new \yii\db\Query())
                ->from('{{%auth_item_child}}')
                ->where(['parent' => $role, 'child' => $permissionName])
                ->exists($this->db);

            if (!$childExists) {
                $this->insert('{{%auth_item_child}}', [
                    'parent' => $role,
                    'child' => $permissionName,
                ]);
            }
        }
    }

    public function safeDown()
    {
        $permissionName = 'viewReportAnalytics';

        $this->delete('{{%auth_item_child}}', ['child' => $permissionName]);
        $this->delete('{{%auth_item}}', ['name' => $permissionName]);
    }
}
