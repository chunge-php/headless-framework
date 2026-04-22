
<?php

use Phinx\Migration\AbstractMigration;

final class CreateSmsBatchSourcsTable extends AbstractMigration
{
    protected $tableName = "sms_batch_sourcs"; //表名称
    protected $comment = '联系人来源'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('account_number', 'string', ['limit' => 90, 'comment' => '账号', 'null' => true])
                ->addColumn('source_id', 'biginteger', ['limit' => 20, 'comment' => '溯源id'])
                ->addColumn('source_type', 'integer', ['limit' => 1, 'comment' => '来源类型0无1联系人标签id 2用户分组id'])
                ->addColumn('sms_batch_lots_id', 'biginteger', ['limit' => 20, 'comment' => '批次id'])
                ->addForeignKey('sms_batch_lots_id', 'sms_batch_lots', 'id', [
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
