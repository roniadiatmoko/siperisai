<?php

use yii\db\Migration;

class m260705_090000_add_cause_fields_to_report_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%report}}', 'cause_group', $this->string(32)->null()->after('incident_type'));
        $this->addColumn('{{%report}}', 'cause_subtype', $this->string(64)->null()->after('cause_group'));

        $this->createIndex('idx-report-cause_group', '{{%report}}', 'cause_group');
        $this->createIndex('idx-report-cause_subtype', '{{%report}}', 'cause_subtype');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-report-cause_subtype', '{{%report}}');
        $this->dropIndex('idx-report-cause_group', '{{%report}}');

        $this->dropColumn('{{%report}}', 'cause_subtype');
        $this->dropColumn('{{%report}}', 'cause_group');
    }
}
