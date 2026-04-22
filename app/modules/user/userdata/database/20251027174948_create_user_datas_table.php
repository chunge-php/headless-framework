
<?php

use Phinx\Migration\AbstractMigration;

final class CreateUserDatasTable extends AbstractMigration
{
    protected $tableName = "user_datas"; //表名称
    protected $comment = '用户资料'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('company_name', 'string', ['limit' => 255, 'comment' => '公司名称', 'default' => ''])
                ->addColumn('last_name', 'string', ['limit' => 90, 'comment' => '姓', 'default' => ''])
                ->addColumn('first_name', 'string', ['limit' => 90, 'comment' => '名', 'default' => ''])
                ->addColumn('country', 'string', ['limit' => 190, 'comment' => '国家', 'default' => ''])
                ->addColumn('address1', 'string', ['limit' => 255, 'comment' => '地址1', 'default' => ''])
                ->addColumn('address2', 'string', ['limit' => 255, 'comment' => '地址2', 'default' => ''])
                ->addColumn('city', 'string', ['limit' => 255, 'comment' => '城市', 'default' => ''])
                ->addColumn('state', 'string', ['limit' => 255, 'comment' => '州', 'default' => ''])
                ->addColumn('zip_code', 'string', ['limit' => 90, 'comment' => '邮编', 'default' => ''])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
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
