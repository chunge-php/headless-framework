
<?php

use Phinx\Migration\AbstractMigration;

final class CreateSmsChannelLogsTable extends AbstractMigration
{
    protected $tableName = "sms_channel_logs"; //表名称
    protected $comment = '渠道来源发送记录'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('to', 'string', ['limit' => 20, 'comment' => '手机号'])
                ->addColumn('channel_type', 'integer', ['default' => 1, 'comment' => '来源渠道0平台1QuickPay2餐饮3轮盘游戏'])
                ->addColumn('uid', 'biginteger', ['comment' => '用户id', 'default' => 0])
                ->addIndex(['to', 'channel_type','uid'], ['unique' => true])
                // 添加自动填充时间戳
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'comment' => '创建时间'])
                ->create();
        } else {
            $table = $this->table($this->tableName);
            $columns = [
              'uid' => setTableKey('biginteger', '用户id', 0, 20, false),
            ];
            setTableForm($table, $columns);
        }
    }
}
