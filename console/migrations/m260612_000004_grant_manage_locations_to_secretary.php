<?php

use yii\db\Migration;

class m260612_000004_grant_manage_locations_to_secretary extends Migration
{
    public function safeUp()
    {
        $exists = (new \yii\db\Query())
            ->from('{{%auth_item_child}}')
            ->where(['parent' => 'secretary_k3l', 'child' => 'manageLocations'])
            ->exists();

        if (!$exists) {
            $this->insert('{{%auth_item_child}}', [
                'parent' => 'secretary_k3l',
                'child' => 'manageLocations',
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%auth_item_child}}', [
            'parent' => 'secretary_k3l',
            'child' => 'manageLocations',
        ]);
    }
}