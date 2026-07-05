<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%ref_incident_category}}`.
 */
class m260628_165920_create_ref_incident_category_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ref_incident_category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(150)->notNull(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
        ]);

        $this->batchInsert('{{%ref_incident_category}}',
            ['name', 'status'],
            [
                ['Pelaporan Bahaya', 1],
                ['Pelaporan Sakit Akibat Kerja', 1],
                ['Pelaporan Insiden K3', 1],
            ]
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%ref_incident_category}}');
    }
}