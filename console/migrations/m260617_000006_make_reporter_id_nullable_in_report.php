<?php

use yii\db\Migration;

class m260617_000006_make_reporter_id_nullable_in_report extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%report}}', 'reporter_id', $this->integer()->null());
    }

    public function safeDown()
    {
        // Reverting this migration safely is not possible if anonymous rows already exist.
        return false;
    }
}
