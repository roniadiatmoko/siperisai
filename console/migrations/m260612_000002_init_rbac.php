<?php

use yii\db\Migration;

class m260612_000002_init_rbac extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%auth_rule}}', [
            'name' => $this->string(64)->notNull(),
            'data' => $this->binary()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);
        $this->addPrimaryKey('pk-auth_rule', '{{%auth_rule}}', 'name');

        $this->createTable('{{%auth_item}}', [
            'name' => $this->string(64)->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'description' => $this->text()->null(),
            'rule_name' => $this->string(64)->null(),
            'data' => $this->binary()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);
        $this->addPrimaryKey('pk-auth_item', '{{%auth_item}}', 'name');

        $this->createIndex('idx-auth_item-type', '{{%auth_item}}', 'type');
        $this->addForeignKey('fk-auth_item-rule_name-auth_rule', '{{%auth_item}}', 'rule_name', '{{%auth_rule}}', 'name', 'SET NULL', 'CASCADE');

        $this->createTable('{{%auth_item_child}}', [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
        ], $tableOptions);
        $this->addPrimaryKey('pk-auth_item_child', '{{%auth_item_child}}', ['parent', 'child']);

        $this->addForeignKey('fk-auth_item_child-parent-auth_item', '{{%auth_item_child}}', 'parent', '{{%auth_item}}', 'name', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-auth_item_child-child-auth_item', '{{%auth_item_child}}', 'child', '{{%auth_item}}', 'name', 'CASCADE', 'CASCADE');

        $this->createTable('{{%auth_assignment}}', [
            'item_name' => $this->string(64)->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'created_at' => $this->integer()->null(),
        ], $tableOptions);
        $this->addPrimaryKey('pk-auth_assignment', '{{%auth_assignment}}', ['item_name', 'user_id']);

        $this->addForeignKey('fk-auth_assignment-item_name-auth_item', '{{%auth_assignment}}', 'item_name', '{{%auth_item}}', 'name', 'CASCADE', 'CASCADE');

        $now = time();
        $roles = [
            'reporter' => 'Pelapor',
            'secretary_k3l' => 'Sekretaris K3L',
            'team_lead_k3l' => 'Ketua Tim K3L',
            'coordinator_head' => 'Koordinator Kepala',
        ];

        foreach ($roles as $name => $description) {
            $this->insert('{{%auth_item}}', [
                'name' => $name,
                'type' => 1,
                'description' => $description,
                'rule_name' => null,
                'data' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissions = [
            'manageLocations' => 'Kelola lokasi kerja',
            'submitReport' => 'Kirim laporan keselamatan',
            'reviewReport' => 'Review laporan masuk',
            'finalizeReport' => 'Finalisasi laporan',
            'approveReport' => 'Approve laporan',
            'sendTelegramNotification' => 'Kirim notifikasi Telegram',
            'generateReportPdf' => 'Generate PDF laporan',
            'followUpReport' => 'Input tindak lanjut',
        ];

        foreach ($permissions as $name => $description) {
            $this->insert('{{%auth_item}}', [
                'name' => $name,
                'type' => 2,
                'description' => $description,
                'rule_name' => null,
                'data' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleChildren = [
            'reporter' => ['submitReport'],
            'secretary_k3l' => ['reviewReport', 'finalizeReport', 'sendTelegramNotification', 'generateReportPdf'],
            'team_lead_k3l' => ['approveReport', 'sendTelegramNotification', 'generateReportPdf'],
            'coordinator_head' => ['followUpReport', 'sendTelegramNotification'],
        ];

        foreach ($roleChildren as $role => $children) {
            foreach ($children as $child) {
                $this->insert('{{%auth_item_child}}', [
                    'parent' => $role,
                    'child' => $child,
                ]);
            }
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-auth_assignment-item_name-auth_item', '{{%auth_assignment}}');
        $this->dropForeignKey('fk-auth_item_child-child-auth_item', '{{%auth_item_child}}');
        $this->dropForeignKey('fk-auth_item_child-parent-auth_item', '{{%auth_item_child}}');
        $this->dropForeignKey('fk-auth_item-rule_name-auth_rule', '{{%auth_item}}');

        $this->dropTable('{{%auth_assignment}}');
        $this->dropTable('{{%auth_item_child}}');
        $this->dropTable('{{%auth_item}}');
        $this->dropTable('{{%auth_rule}}');
    }
}