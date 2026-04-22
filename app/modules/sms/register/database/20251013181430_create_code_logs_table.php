<?php


use Phinx\Migration\AbstractMigration;

final class CreateCodeLogsTable extends AbstractMigration
{
    protected $tableName = "code_logs"; //表名称
    protected $comment = '短信验证码发送记录'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('account_number', 'string', ['limit' => 190, 'comment' => '账号'])
                ->addColumn('code', 'string', ['limit' => 6, 'comment' => '验证码'])
                ->addColumn('auto_type', 'integer', ['limit' => 1, 'default' => 1, 'comment' => '0邮箱1手机'])
                ->addColumn('msg', 'text', ['comment' => '发送结果'])
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
