
<?php

use Phinx\Migration\AbstractMigration;

final class CreateAutoTopupRulesTable extends AbstractMigration
{
    protected $tableName = "auto_topup_rules"; //表名称
    protected $comment = '自动充值规则'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('wallets_id', 'biginteger', ['limit' => 20, 'comment' => '钱包Id'])
                ->addColumn('enabled', 'integer', ['limit' => 1, 'comment' => '状态1启用 0停用'])
                ->addColumn('threshold_cents', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '低于此余额触发', 'default' => 0])
                ->addColumn('topup_amount_cents', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '固定充值金额', 'default' => 0])
                ->addColumn('reminder_price', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '低于N元发送提醒邮件', 'default' => 0])
                ->addColumn('reminder_status', 'integer', ['limit' => 1, 'comment' => '提醒状态0未提醒1已提醒', 'default' => 0])
                ->addColumn('cooldown_sec', 'integer', ['comment' => '冷却时间单位毫秒', 'default' => 180])
                ->addColumn('today_count', 'integer', ['comment' => '今日已触发数', 'default' => 0])
                ->addColumn('last_run_at', 'timestamp', ['comment' => '上次触发时间'])
                ->addIndex(['uid', 'wallets_id'], ['unique' => true])
                ->addForeignKey('wallets_id', 'wallets', 'id', [
                    'delete' => 'CASCADE', // 当删除 users 中的记录时，删除关联的 posts 记录
                    'update' => 'NO_ACTION' // 当更新 users 中的记录时，不影响 posts 中的记录
                ])
                ->addForeignKey('uid', 'users', 'id', [
                    'delete' => 'CASCADE', // 当删除 users 中的记录时，删除关联的 posts 记录
                    'update' => 'NO_ACTION' // 当更新 users 中的记录时，不影响 posts 中的记录
                ])
                // 添加自动填充时间戳
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'comment' => '创建时间'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP', // 每次更新自动更新时间戳
                    'comment' => '更新时间'
                ])
                ->create();
        } else {
            // $table = $this->table($this->tableName);
            //$columns = [
            //   'device_id' => setTableKey('integer', '设备id', 0, 10, false),
            // ];
            //setTableForm($table, $columns);
        }
    }
}
