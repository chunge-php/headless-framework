
<?php

use Phinx\Migration\AbstractMigration;

final class CreateMessageLogsTable extends AbstractMigration
{
    protected $tableName = "message_logs"; //表名称
    protected $comment = '短信发送记录'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('sms_batch_lots_id', 'biginteger', ['limit' => 20, 'comment' => '批次id'])
                ->addColumn('account_number', 'string', ['limit' => 90, 'comment' => '账号', 'default' => ''])
                ->addColumn('type', 'integer', ['limit' => 1, 'default' => 0, 'comment' => '类型0sms1彩信2邮箱'])
                ->addColumn('status', 'integer', ['default' => 0, 'comment' => '执行状态0未开始1成功2执行中3失败4重新发送'])
                ->addColumn('total_money', 'decimal', ['precision' => 10, 'scale' => 2,  'comment' => '产生的总金额'])
                ->addColumn('consume_number', 'integer', ['default' => 0, 'comment' => '消费总条数'])
                ->addColumn('unusual_msg', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'comment' => '异常结果', 'default' => null, 'null' => true])
                ->addForeignKey('sms_batch_lots_id', 'sms_batch_lots', 'id', [
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
