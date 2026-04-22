
<?php

use Phinx\Migration\AbstractMigration;

final class CreateAddressBooksTable extends AbstractMigration
{
    protected $tableName = "address_books"; //表名称
    protected $comment = '通讯录'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('last_name', 'string', ['limit' => 90, 'comment' => '姓', 'default' => ''])
                ->addColumn('first_name', 'string', ['limit' => 90, 'comment' => '名', 'default' => ''])
                ->addColumn('phone', 'string', ['limit' => 20, 'comment' => '手机号', 'default' => ''])
                ->addColumn('email', 'string', ['limit' => 90, 'comment' => '邮箱', 'default' => ''])
                ->addColumn('birthday','string',['limit' => 10, 'comment' => '生日', 'default' => ''])
                ->addColumn('month_day','string',['limit' => 10, 'comment' => '月日', 'default' => ''])
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
