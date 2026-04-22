
<?php

use Phinx\Migration\AbstractMigration;

final class CreateGameUsersTable extends AbstractMigration
{
    protected $tableName = "game_users"; //表名称
    protected $comment = '用户选择的游戏'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('code', 'string', ['comment' => '游戏编号', 'limit' => 10])
                ->addColumn('type', 'string', ['comment' => '类型前端自定义', 'limit' => 60, 'default' => ''])
                ->addColumn('body', 'string', ['comment' => '短信发送内容', 'limit' => 255, 'default' => ''])
                ->addColumn('options', 'json', ['comment' => '前端自定义配置'])
                ->addColumn('switch', 'json', ['comment' => '中奖后是否自动发送配置'])
                ->addColumn('pre', 'json', ['comment' => '中奖率和奖品配置'])

                ->addIndex(['code'], ['unique' => true])
                ->addForeignKey('uid', 'users', 'id', [
                    'delete' => 'CASCADE', // 当删除 users 中的记录时，删除关联的 posts 记录
                    'update' => 'NO_ACTION' // 当更新 users 中的记录时，不影响 posts 中的记录
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
