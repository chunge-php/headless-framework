
<?php

use Phinx\Migration\AbstractMigration;

final class CreateSmsPricesTable extends AbstractMigration
{
    protected $tableName = "sms_prices"; //表名称
    protected $comment = '短信价格配置'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '名称', 'default' => ''])
                ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'comment' => '价格'])
                ->addColumn('type', 'integer', ['default' => 1, 'comment' => '类型0普通短信1彩信2邮件'])
                ->addColumn('remark', 'text', ['comment' => '备注'])
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
