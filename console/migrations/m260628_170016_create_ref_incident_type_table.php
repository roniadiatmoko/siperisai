<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%ref_incident_type}}`.
 */
class m260628_170016_create_ref_incident_type_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ref_incident_type}}', [
            'id' => $this->primaryKey(),
            'incident_category_id' => $this->integer()->notNull(),
            'name' => $this->string(150)->notNull(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
        ]);

        $this->addForeignKey(
            'fk_ref_incident_type_category',
            '{{%ref_incident_type}}',
            'incident_category_id',
            '{{%ref_incident_category}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->batchInsert('{{%ref_incident_type}}',
            ['incident_category_id', 'name', 'status'],
            [
                [1, 'Kondisi Tidak Aman', 1],
                [1, 'Perilaku Tidak Aman', 1],

                [2, 'Penyakit Akibat Kerja', 1],
                [2, 'Kegawatdaruratan Medis', 1],

                [3, 'Hampir Celaka (Near Miss)', 1],
                [3, 'Kecelakaan Kerja', 1],
            ]
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(
            'fk_ref_incident_type_category',
            '{{%ref_incident_type}}'
        );

        $this->dropTable('{{%ref_incident_type}}');
    }
}