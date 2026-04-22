
<?php

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    protected $tableName = "users"; //表名称
    protected $comment = '用户基础表'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('code', 'string', ['limit' => 20, 'comment' => '编号'])
                ->addColumn('last_name', 'string', ['limit' => 90, 'comment' => '姓', 'default' => ''])
                ->addColumn('first_name', 'string', ['limit' => 90, 'comment' => '名', 'default' => ''])
                ->addColumn('phone', 'string', ['limit' => 32, 'comment' => '手机号', 'default' => ''])
                ->addColumn('picture', 'string', ['limit' => 190, 'comment' => '头像', 'default' => ''])
                ->addColumn('state', 'integer', ['limit' => 1, 'comment' => '用户状态0禁用1正常', 'default' => 1])
                ->addColumn('tables', 'string', ['limit' => 20, 'comment' => '店铺唯一标识', 'default' => ''])
                ->addColumn('extend_json', 'json', ['null' => true, 'comment' => '扩展信息json'])
                ->addIndex(['code'], ['unique' => true])

                // 添加自动填充时间戳
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'comment' => '创建时间'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP', // 每次更新自动更新时间戳
                    'comment' => '更新时间'
                ])
                ->addColumn('deleted_at', 'timestamp', ['null' => true, 'comment' => '删除时间'])

                ->create();
        } else {
            $table = $this->table($this->tableName);
            $columns = [
                'extend_json' => setTableKey('json', '扩展信息json', null, 10, true),
                'tables' => setTableKey('string', '店铺唯一标识', null, 20, false),
            ];
            setTableForm($table, $columns);
        }
    }
}
