
<?php

use Phinx\Migration\AbstractMigration;

final class CreateGroupUsersTable extends AbstractMigration
{
    protected $tableName = "group_users"; //表名称
    protected $comment = '分组用户管理'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '名称', 'default' => ''])
                ->addColumn('total', 'biginteger', ['limit' => 20, 'comment' => '总人数'])
                ->addColumn('channel_type', 'integer', ['default' => 1, 'comment' => '来源渠道0平台1QuickPay2餐饮3轮盘游戏'])
                ->addColumn('file_url', 'string', ['limit' => 255, 'comment' => '文件地址', 'default' => ''])
                ->addColumn('description', 'string', ['limit' => 255, 'comment' => '描述'])
                ->addIndex(['uid', 'name'], ['unique' => true])
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
            $table = $this->table($this->tableName);
            $columns = [
                'channel_type' => setTableKey('integer', '来源渠道0平台1QuickPay2餐饮3轮盘游戏', 0, 10, false),
            ];
            setTableForm($table, $columns);
        }
    }
}
