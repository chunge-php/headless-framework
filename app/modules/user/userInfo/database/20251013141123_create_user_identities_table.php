
<?php

use Phinx\Migration\AbstractMigration;

final class CreateUserIdentitiesTable extends AbstractMigration
{
    protected $tableName = "user_identities"; //表名称
    protected $comment = '用户身份映射'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('provider', 'string', ['limit' => 32, 'comment' => "账号类型:username,email,google,sms"])
                ->addColumn('account_number', 'string', ['limit' => 190, 'comment' => "账号 用户名/邮箱/Google sub/手机号等"])
                ->addColumn('user_type', 'string', ['limit' => 32, 'comment' => "身份类型:admin,user,dev", 'default' => 'user'])
                ->addColumn('status', 'integer', ['limit' => 1, 'comment' => '账号状态0停用1启用', 'default' => 1])
                ->addColumn('meta_json', 'json', ['comment' => "可存第三方头像、昵称、unionid 等"])
                ->addColumn('verified_at', 'timestamp', ['null' => true, 'comment' => '验证通过时间'])
                ->addColumn('linked_at', 'timestamp', ['null' => true, 'comment' => '最后登录时间'])
                ->addIndex(['account_number'], ['unique' => true])

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
