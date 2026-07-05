<?php

use yii\db\Migration;

class m260617_000005_add_create_report_detail_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%report}}', 'has_victim', $this->smallInteger()->notNull()->defaultValue(0));
        $this->addColumn('{{%report}}', 'victim_name', $this->string(255)->null());
        $this->addColumn('{{%report}}', 'victim_condition', $this->string(64)->null());
        $this->addColumn('{{%report}}', 'victim_condition_detail', $this->text()->null());
        $this->addColumn('{{%report}}', 'has_property_damage', $this->smallInteger()->notNull()->defaultValue(0));
        $this->addColumn('{{%report}}', 'property_damage_detail', $this->text()->null());
        $this->addColumn('{{%report}}', 'witness', $this->text()->null());
        $this->addColumn('{{%report}}', 'additional_notes', $this->text()->null());
        $this->addColumn('{{%report}}', 'is_anonymous', $this->smallInteger()->notNull()->defaultValue(0));
        $this->addColumn('{{%report}}', 'reporter_name', $this->string(255)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%report}}', 'reporter_name');
        $this->dropColumn('{{%report}}', 'is_anonymous');
        $this->dropColumn('{{%report}}', 'additional_notes');
        $this->dropColumn('{{%report}}', 'witness');
        $this->dropColumn('{{%report}}', 'property_damage_detail');
        $this->dropColumn('{{%report}}', 'has_property_damage');
        $this->dropColumn('{{%report}}', 'victim_condition_detail');
        $this->dropColumn('{{%report}}', 'victim_condition');
        $this->dropColumn('{{%report}}', 'victim_name');
        $this->dropColumn('{{%report}}', 'has_victim');
    }
}
