<?php

use yii\db\Migration;

class m260612_000001_create_safety_reporting_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%location}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(64)->notNull()->unique(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'qr_token' => $this->string(128)->notNull()->unique(),
            'is_active' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%user_telegram_account}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull()->unique(),
            'telegram_chat_id' => $this->string(64)->notNull(),
            'telegram_username' => $this->string(64)->null(),
            'is_enabled' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%report}}', [
            'id' => $this->primaryKey(),
            'report_number' => $this->string(32)->notNull()->unique(),
            'location_id' => $this->integer()->notNull(),
            'reporter_id' => $this->integer()->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('submitted'),
            'incident_time' => $this->integer()->notNull(),
            'description' => $this->text()->notNull(),
            'incident_type' => $this->string(128)->null(),
            'recommendation' => $this->text()->null(),
            'pic_user_id' => $this->integer()->null(),
            'secretary_id' => $this->integer()->null(),
            'secretary_finalized_at' => $this->integer()->null(),
            'team_lead_id' => $this->integer()->null(),
            'team_lead_approved_at' => $this->integer()->null(),
            'coordinator_id' => $this->integer()->null(),
            'coordinator_follow_up_at' => $this->integer()->null(),
            'coordinator_follow_up_note' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%report_attachment}}', [
            'id' => $this->primaryKey(),
            'report_id' => $this->integer()->notNull(),
            'file_path' => $this->string(255)->notNull(),
            'original_name' => $this->string(255)->notNull(),
            'mime_type' => $this->string(128)->null(),
            'file_size' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%report_status_history}}', [
            'id' => $this->primaryKey(),
            'report_id' => $this->integer()->notNull(),
            'status_from' => $this->string(32)->null(),
            'status_to' => $this->string(32)->notNull(),
            'note' => $this->text()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-location-created_by-user', '{{%location}}', 'created_by', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-user_telegram_account-user_id-user', '{{%user_telegram_account}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-report-location_id-location', '{{%report}}', 'location_id', '{{%location}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-report-reporter_id-user', '{{%report}}', 'reporter_id', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-report-pic_user_id-user', '{{%report}}', 'pic_user_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-report-secretary_id-user', '{{%report}}', 'secretary_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-report-team_lead_id-user', '{{%report}}', 'team_lead_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-report-coordinator_id-user', '{{%report}}', 'coordinator_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-report_attachment-report_id-report', '{{%report_attachment}}', 'report_id', '{{%report}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-report_status_history-report_id-report', '{{%report_status_history}}', 'report_id', '{{%report}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-report_status_history-created_by-user', '{{%report_status_history}}', 'created_by', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-report_status_history-created_by-user', '{{%report_status_history}}');
        $this->dropForeignKey('fk-report_status_history-report_id-report', '{{%report_status_history}}');
        $this->dropForeignKey('fk-report_attachment-report_id-report', '{{%report_attachment}}');
        $this->dropForeignKey('fk-report-coordinator_id-user', '{{%report}}');
        $this->dropForeignKey('fk-report-team_lead_id-user', '{{%report}}');
        $this->dropForeignKey('fk-report-secretary_id-user', '{{%report}}');
        $this->dropForeignKey('fk-report-pic_user_id-user', '{{%report}}');
        $this->dropForeignKey('fk-report-reporter_id-user', '{{%report}}');
        $this->dropForeignKey('fk-report-location_id-location', '{{%report}}');
        $this->dropForeignKey('fk-user_telegram_account-user_id-user', '{{%user_telegram_account}}');
        $this->dropForeignKey('fk-location-created_by-user', '{{%location}}');

        $this->dropTable('{{%report_status_history}}');
        $this->dropTable('{{%report_attachment}}');
        $this->dropTable('{{%report}}');
        $this->dropTable('{{%user_telegram_account}}');
        $this->dropTable('{{%location}}');
    }
}