<?php

use yii\db\Migration;

class m260618_000007_add_missing_data_note_to_report extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%report}}', 'missing_data_note', $this->text()->null()->after('recommendation'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%report}}', 'missing_data_note');
    }
}
