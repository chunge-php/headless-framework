
<?php

use Phinx\Migration\AbstractMigration;

final class CreateConfigItemsTable extends AbstractMigration
{
    protected $tableName = "config_items"; //表名称
    protected $comment = '单一配置'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '名称', 'default' => ''])
                ->addColumn('signs', 'string', ['limit' => 90, 'comment' => '标识', 'default' => ''])
                ->addColumn('values', 'json', ['comment' => '值'])
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
