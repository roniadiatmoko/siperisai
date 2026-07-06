<?php

use yii\db\Migration;

class m260706_063055_seed_user_telegram_accounts extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('{{%user_telegram_account}}', [
            'id',
            'user_id',
            'telegram_chat_id',
            'telegram_username',
            'is_enabled',
            'created_at',
            'updated_at'
        ], [
            [2, 2, '327754279', 'roniadiatmoko', 1, 1, 1],
            [1, 1, '327754279', 'roniadiatmoko', 1, 1, 1],
            [4, 4, '327754279', 'roniadiatmoko', 1, 1, 1],
            [3, 3, '327754279', 'roniadiatmoko', 1, 1, 1],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%user_telegram_account}}', ['id' => [1, 2, 3, 4]]);
    }
}
