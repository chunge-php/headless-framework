
<?php

use Phinx\Migration\AbstractMigration;

final class CreateTagsTable extends AbstractMigration
{
    protected $tableName = "tags"; //表名称
    protected $comment = '标签管理'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '标签名称'])
                ->addColumn('description', 'string', ['limit' => 255, 'comment' => '标签描述'])
                ->addColumn('colour', 'string', ['limit' => 255, 'default' => '', 'comment' => '颜色'])
                ->addColumn('target_type', 'string', ['limit' => 30, 'comment' => '目标所属类型数据库表名称'])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addIndex(['uid', 'name','target_type'], ['unique' => true])
                ->addForeignKey('uid', 'users', 'id', [
                    'delete' => 'CASCADE', // 当删除 users 中的记录时，删除关联的 posts 记录
                    'update' => 'NO_ACTION' // 当更新 users 中的记录时，不影响 posts 中的记录
                ])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'comment' => '创建时间'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP', // 每次更新自动更新时间戳
                    'comment' => '更新时间'
                ])
                ->create();
        } else {
            $table = $this->table($this->tableName);
            $columns = [
              'target_type' => setTableKey('string', '目标所属类型数据库表名称', '', 30, false),
            ];
            setTableForm($table, $columns);
        }
    }
}
