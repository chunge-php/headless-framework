
<?php

use Phinx\Migration\AbstractMigration;

final class CreateCardsTable extends AbstractMigration
{
    protected $tableName = "cards"; //表名称
    protected $comment = '信用卡管理'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 190, 'comment' => '用户全称', 'default' => ''])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('acctid', 'integer', ['limit' => 11, 'comment' => 'acctid'])
                ->addColumn('profileid', 'string', ['limit' => 190, 'comment' => '用户profileid', 'default' => ''])
                ->addColumn('accttype', 'string', ['limit' => 190, 'comment' => '信用卡类型', 'default' => ''])
                ->addColumn('expiry', 'string', ['limit' => 10, 'comment' => '到期日期', 'default' => ''])
                ->addColumn('token', 'string', ['limit' => 190, 'comment' => 'token'])
                ->addColumn('address', 'string', ['limit' => 190, 'comment' => '用户地址', 'default' => ''])
                ->addColumn('line2', 'string', ['limit' => 190, 'comment' => 'line2', 'default' => ''])
                ->addColumn('city', 'string', ['limit' => 190, 'comment' => '用户城市', 'default' => ''])
                ->addColumn('region', 'string', ['limit' => 190, 'comment' => '地区', 'default' => 'NY'])
                ->addColumn('country', 'string', ['limit' => 190, 'comment' => '国家', 'default' => 'US'])
                ->addColumn('postal', 'string', ['limit' => 90, 'comment' => '邮编', 'default' => ''])
                ->addColumn('company', 'string', ['limit' => 190, 'comment' => '公司名称', 'default' => ''])
                ->addColumn('later_number', 'string', ['limit' => 4, 'comment' => '卡号后4位', 'default' => ''])

                ->addColumn('cvv', 'string', ['limit' => 90, 'comment' => 'cvv', 'default' => ''])
                ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'comment' => '状态0禁用1正常'])
                ->addColumn('default_state', 'integer', ['limit' => 1, 'default' => 0, 'comment' => '默认扣款0否1是'])
                ->addColumn('response_data', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM,'comment' => '响应结果'])
                ->addIndex(['token', 'uid'], ['unique' => true])
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
