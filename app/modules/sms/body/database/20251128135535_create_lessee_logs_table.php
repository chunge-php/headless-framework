
<?php

use Phinx\Migration\AbstractMigration;

final class CreateLesseeLogsTable extends AbstractMigration
{
    protected $tableName = "lessee_logs"; //表名称
    protected $comment = '第三方租户信息'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '租户名称第三方提供', 'default' => ''])
                ->addColumn('code', 'string', ['limit' => 90, 'comment' => '租户编号第三方提供', 'default' => ''])
                ->addColumn('sms_batch_lots_id', 'biginteger', ['comment' => '批次id'])
                ->addForeignKey('sms_batch_lots_id', 'sms_batch_lots', 'id', [
                    'delete' => 'CASCADE', // 当删除 users 中的记录时，删除关联的 posts 记录
                    'update' => 'NO_ACTION' // 当更新 users 中的记录时，不影响 posts 中的记录
                ])
                // 添加自动填充时间戳
          
                ->create();
        } else {
            // $table = $this->table($this->tableName);
            // $columns = [
            //     'channel_type' => setTableKey('integer', '来源渠道0餐饮1QuickPay2轮盘游戏', 0, 10, false),
            // ];
            // setTableForm($table, $columns);
        }
    }
}
