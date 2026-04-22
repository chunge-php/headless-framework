
<?php

use Phinx\Migration\AbstractMigration;

final class CreateGameLogsTable extends AbstractMigration
{
    protected $tableName = "game_logs"; //表名称
    protected $comment = '中奖记录'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);
            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('to', 'string', ['comment' => '手机号', 'limit' => 20, 'default' => ''])
                ->addColumn('type', 'string', ['comment' => '类型前端自定义', 'limit' => 60, 'default' => ''])
                ->addColumn('body', 'string', ['comment' => '短信发送内容', 'limit' => 255, 'default' => ''])
                ->addColumn('result', 'string', ['comment' => '奖品', 'limit' => 255, 'default' => ''])
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
