<?php

use yii\db\Migration;

class m260705_100000_add_pic_unit_and_pic_name_to_report_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%report}}', 'pic_unit', $this->string(128)->null()->after('pic_user_id'));
        $this->addColumn('{{%report}}', 'pic_name', $this->string(255)->null()->after('pic_unit'));

        $this->createIndex('idx-report-pic_unit', '{{%report}}', 'pic_unit');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-report-pic_unit', '{{%report}}');

        $this->dropColumn('{{%report}}', 'pic_name');
        $this->dropColumn('{{%report}}', 'pic_unit');
    }
}
