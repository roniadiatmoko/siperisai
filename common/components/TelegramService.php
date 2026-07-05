<?php

namespace common\components;

use common\models\User;
use common\models\UserTelegramAccount;
use Yii;
use yii\base\BaseObject;

class TelegramService extends BaseObject
{
    public $botToken;
    public $apiBaseUrl = 'https://api.telegram.org';

    public function sendMessage($chatId, $text, array $options = [])
    {
        if (empty($this->botToken) || empty($chatId) || $text === '') {
            Yii::warning('Telegram sendMessage skipped because botToken/chatId/text is missing.', __METHOD__);
            return false;
        }

        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $options);

        $url = rtrim($this->apiBaseUrl, '/') . '/bot' . $this->botToken . '/sendMessage';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            Yii::error('Telegram sendMessage failed for chat_id ' . $chatId . ' using URL ' . $url, __METHOD__);
            return false;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            Yii::error('Telegram sendMessage API rejected message for chat_id ' . $chatId . '. Response: ' . $response, __METHOD__);
            return false;
        }

        return true;
    }

    public function notifyUser(User $user, $message, array $options = [])
    {
        $account = UserTelegramAccount::findOne(['user_id' => $user->id, 'is_enabled' => 1]);

        if ($account === null) {
            return false;
        }

        return $this->sendMessage($account->telegram_chat_id, $message, $options);
    }

    public function notifyRole($roleName, $message, array $options = [])
    {
        $users = User::find()
            ->alias('u')
            ->innerJoin('{{%auth_assignment}} aa', 'aa.user_id = CAST(u.id AS CHAR)')
            ->where(['aa.item_name' => $roleName, 'u.status' => User::STATUS_ACTIVE])
            ->orderBy(['u.username' => SORT_ASC])
            ->all();
        $successCount = 0;

        foreach ($users as $user) {
            if ($this->notifyUser($user, $message, $options)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    public function sendDocument($chatId, $filePath, $caption = '')
    {
        if (empty($this->botToken) || empty($chatId) || !is_file($filePath) || !function_exists('curl_init')) {
            return false;
        }

        $url = rtrim($this->apiBaseUrl, '/') . '/bot' . $this->botToken . '/sendDocument';
        $payload = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'document' => new \CURLFile($filePath),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            Yii::error('Telegram sendDocument failed for chat_id ' . $chatId . ' with HTTP code ' . $httpCode, __METHOD__);
            return false;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            Yii::error('Telegram sendDocument API rejected document for chat_id ' . $chatId . '. Response: ' . $response, __METHOD__);
            return false;
        }

        return true;
    }

    public function notifyRoleWithDocument($roleName, $filePath, $caption = '')
    {
        $users = User::find()
            ->alias('u')
            ->innerJoin('{{%auth_assignment}} aa', 'aa.user_id = CAST(u.id AS CHAR)')
            ->where(['aa.item_name' => $roleName, 'u.status' => User::STATUS_ACTIVE])
            ->orderBy(['u.username' => SORT_ASC])
            ->all();
        $successCount = 0;

        foreach ($users as $user) {
            $account = UserTelegramAccount::findOne(['user_id' => $user->id, 'is_enabled' => 1]);
            if ($account !== null && $this->sendDocument($account->telegram_chat_id, $filePath, $caption)) {
                $successCount++;
            }
        }

        return $successCount;
    }
}