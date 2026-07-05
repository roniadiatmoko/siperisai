<?php

use yii\db\Migration;

class m260612_000003_seed_default_users_and_roles extends Migration
{
    public function safeUp()
    {
        $now = time();

        $users = [
            [
                'username' => 'pelapor',
                'email' => 'pelapor@example.com',
                'role' => 'reporter',
            ],
            [
                'username' => 'sekretaris',
                'email' => 'sekretaris@example.com',
                'role' => 'secretary_k3l',
            ],
            [
                'username' => 'ketua',
                'email' => 'ketua@example.com',
                'role' => 'team_lead_k3l',
            ],
            [
                'username' => 'koordinator',
                'email' => 'koordinator@example.com',
                'role' => 'coordinator_head',
            ],
        ];

        foreach ($users as $item) {
            $user = (new \yii\db\Query())
                ->from('{{%user}}')
                ->where(['username' => $item['username']])
                ->one();

            if ($user === false || $user === null) {
                $passwordHash = \Yii::$app->security->generatePasswordHash('password123');
                $authKey = \Yii::$app->security->generateRandomString();

                $this->insert('{{%user}}', [
                    'username' => $item['username'],
                    'auth_key' => $authKey,
                    'password_hash' => $passwordHash,
                    'password_reset_token' => null,
                    'verification_token' => null,
                    'email' => $item['email'],
                    'status' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $userId = (int) \Yii::$app->db->getLastInsertID();
            } else {
                $userId = (int) $user['id'];
            }

            $assignment = (new \yii\db\Query())
                ->from('{{%auth_assignment}}')
                ->where(['item_name' => $item['role'], 'user_id' => (string) $userId])
                ->one();

            if ($assignment === false || $assignment === null) {
                $this->insert('{{%auth_assignment}}', [
                    'item_name' => $item['role'],
                    'user_id' => (string) $userId,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function safeDown()
    {
        $usernames = ['pelapor', 'sekretaris', 'ketua', 'koordinator'];
        $users = (new \yii\db\Query())->from('{{%user}}')->where(['username' => $usernames])->all();

        foreach ($users as $user) {
            $this->delete('{{%auth_assignment}}', ['user_id' => (string) $user['id']]);
        }

        $this->delete('{{%user}}', ['username' => $usernames]);
    }
}