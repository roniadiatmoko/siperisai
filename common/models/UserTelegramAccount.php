<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class UserTelegramAccount extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%user_telegram_account}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['user_id', 'telegram_chat_id'], 'required'],
            [['user_id', 'is_enabled'], 'integer'],
            [['telegram_chat_id'], 'string', 'max' => 64],
            [['telegram_username'], 'string', 'max' => 64],
            [['user_id'], 'unique'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}