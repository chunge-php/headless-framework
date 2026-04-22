
<?php

use Phinx\Migration\AbstractMigration;

final class CreateSmsBillsTable extends AbstractMigration
{
    protected $tableName = "sms_bills"; //表名称
    protected $comment = '短信月统计'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('invoice_number', 'string', ['limit' => 32, 'comment' => '发票编号'])
                ->addColumn('years', 'string', ['limit' => 10,  'comment' => '年'])
                ->addColumn('months', 'string', ['limit' => 10, 'comment' => '月'])
                ->addColumn('total_price', 'decimal', ['precision' => 10, 'scale' => 2,'comment' => '总金额', 'default' => 0])
                ->addColumn('sms_price', 'decimal', ['precision' => 10, 'scale' => 2,'comment' => '短信总金额', 'default' => 0])
                ->addColumn('mms_price', 'decimal', ['precision' => 10, 'scale' => 2,'comment' => '彩信总金额', 'default' => 0])
                ->addColumn('email_price', 'decimal', ['precision' => 10, 'scale' => 2,'comment' => '邮件总金额', 'default' => 0])
                ->addColumn('total_sms', 'integer', ['comment' => '短信总条数', 'default' => 0])
                ->addColumn('total_mms', 'integer', ['comment' => '彩信总条数', 'default' => 0])
                ->addColumn('total_email', 'integer', ['comment' => '邮件总条数', 'default' => 0])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addIndex(['years','months', 'uid'], ['unique' => true])

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
