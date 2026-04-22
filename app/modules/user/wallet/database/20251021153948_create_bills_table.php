
<?php

use Phinx\Migration\AbstractMigration;

final class CreateBillsTable extends AbstractMigration
{
    protected $tableName = "bills"; //表名称
    protected $comment = '充值和消费账单记录'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('code', 'string', ['limit' => 32,  'comment' => '编号'])
                ->addColumn('batch_id', 'biginteger', ['limit' => 20, 'comment' => '短信批次id/充值批次id(2个不同表)'])
                ->addColumn('total_money', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '产生的总金额'])
                ->addColumn('balance', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '当前余额'])
                ->addColumn('send_type', 'integer', ['default' => 0, 'comment' => '发送类型0普通短信1彩信2邮件'])
                ->addColumn('bill_type', 'integer', ['default' => 0, 'comment' => '账单类型0消费1充值'])
                ->addColumn('years', 'string', ['limit' => 10,  'comment' => '年'])
                ->addColumn('months', 'string', ['limit' => 10, 'comment' => '月'])
                ->addColumn('days', 'string', ['limit' => 10, 'comment' => '日'])
                ->addIndex(['code', 'uid'], ['unique' => true])
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
