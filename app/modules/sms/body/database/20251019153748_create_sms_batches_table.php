
<?php

use Phinx\Migration\AbstractMigration;

final class CreateSmsBatchesTable extends AbstractMigration
{
    protected $tableName = "sms_batch_lots"; //表名称
    protected $comment = '短信发送记录批次'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('code', 'string', ['limit' => 32, 'null' => true, 'comment' => '编号'])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('scope_id', 'biginteger', ['limit' => 20, 'comment' => '来源id比如定时任务的id'])
                ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '单价元'])
                ->addColumn('price_id', 'biginteger', ['limit' => 20, 'comment' => '价格id'])
                ->addColumn('total_money', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '产生的总金额'])
                ->addColumn('send_type', 'integer', ['default' => 0, 'comment' => '发送类型0普通短信1彩信2邮件'])
                ->addColumn('subscribe_type', 'integer', ['default' => 0, 'comment' => '类型0立即发送1生日任务2计划任务'])
                ->addColumn('status', 'integer', ['default' => 0, 'comment' => '执行状态0未开始1成功2执行中3失败4警告'])
                ->addColumn('mms_url', 'string', ['limit' => 255, 'null' => true, 'comment' => '彩信图片地址'])
                ->addColumn('years', 'string', ['limit' => 10,  'comment' => '年'])
                ->addColumn('months', 'string', ['limit' => 10, 'comment' => '月'])
                ->addColumn('days', 'string', ['limit' => 10, 'comment' => '日'])
                ->addColumn('consume_number', 'integer', ['default' => 0, 'comment' => '消费总条数'])
                ->addColumn('executed_count', 'integer', ['default' => 0, 'comment' => '执行总次数'])
                ->addColumn('success_total', 'integer', ['default' => 0, 'comment' => '成功总数'])
                ->addColumn('error_total', 'integer', ['default' => 0, 'comment' => '失败总数'])
                ->addColumn('subject', 'string', ['limit' => 255, 'null' => true, 'comment' => '邮件标题'])
                ->addColumn('content', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'comment' => '发送内容', 'default' => null, 'null' => true])
                ->addColumn('unusual_msg', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'comment' => '异常结果', 'default' => null, 'null' => true])
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
            // $columns = [
            //     'channel_type' => setTableKey('integer', '来源渠道0平台1餐饮2QuickPay3轮盘游戏', 0, 10, false),
            // ];
            // setTableForm($table, $columns);
        }
    }
}
