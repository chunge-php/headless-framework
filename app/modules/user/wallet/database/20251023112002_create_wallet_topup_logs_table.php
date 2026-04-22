
<?php

use Phinx\Migration\AbstractMigration;

final class CreateWalletTopupLogsTable extends AbstractMigration
{
    protected $tableName = "wallet_topup_logs"; //表名称
    protected $comment = '充值流水'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('code', 'string', ['limit' => 32,  'comment' => '编号'])
                ->addColumn('years', 'string', ['limit' => 10,  'comment' => '年'])
                ->addColumn('months', 'string', ['limit' => 10, 'comment' => '月'])
                ->addColumn('days', 'string', ['limit' => 10, 'comment' => '日'])
                ->addColumn('cards_id', 'biginteger', ['limit' => 20, 'comment' => '信用卡扣款id'])
                ->addColumn('amount_cents', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '本次充值金额', 'default' => 0])
                ->addColumn('before_balance_cents', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '之前余额', 'default' => 0])
                ->addColumn('after_balance_cents', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '之后余额', 'default' => 0])
                ->addColumn('status', 'integer', ['limit' => 1, 'comment' => '状态0无效1成功2失败'])
                ->addColumn('response_data', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM,'comment' => '响应结果'])
                ->addIndex(['code', 'uid'], ['unique' => true])
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
