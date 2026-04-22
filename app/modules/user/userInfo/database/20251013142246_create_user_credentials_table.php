
<?php

use Phinx\Migration\AbstractMigration;

final class CreateUserCredentialsTable extends AbstractMigration
{
    protected $tableName = "user_credentials"; //表名称
    protected $comment = '凭证：密码'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('identity_id', 'biginteger', ['limit' => 20, 'comment' => '用户身份id'])
                ->addColumn('secret_hash', 'string', ['limit' => 255, 'comment' => '加密后的密码'])
                // 唯一：每个 identity 仅一个密码
                ->addIndex(['identity_id'], ['unique' => true])
                ->addForeignKey('identity_id', 'user_identities', 'id', [
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
