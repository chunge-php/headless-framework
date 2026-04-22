
<?php

use Phinx\Migration\AbstractMigration;

final class CreateGameListsTable extends AbstractMigration
{
    protected $tableName = "game_lists"; //表名称
    protected $comment = '游戏配置'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('options', 'json', ['comment' => '前端自定义配置'])
                ->addColumn('type', 'string', ['comment' => '类型前端自定义', 'limit' => 60, 'default' => ''])
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
