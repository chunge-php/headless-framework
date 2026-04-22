
<?php

use Phinx\Migration\AbstractMigration;

final class CreateJobDefinitionsTable extends AbstractMigration
{
    protected $tableName = "job_definitions"; //表名称
    protected $comment = '任务定义'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '名称', 'default' => ''])
                ->addColumn('kind', 'string', ['limit' => 32, 'comment' => 'birthday/appointment', 'default' => ''])
                ->addColumn('price_id', 'biginteger', ['limit' => 20, 'comment' => '短信价格id'])
                ->addColumn('status', 'integer', ['limit' => 1, 'comment' => '状态0禁用1启用', 'default' => 0])
                ->addColumn('run_status', 'integer', ['limit' => 1, 'comment' => '状态0已结束1完成2执行中3失败4部分', 'default' => 0])
                ->addColumn('run_total', 'integer', ['limit' => 10, 'comment' => '执行总数', 'default' => 0])
                ->addColumn('meta_json', 'json', ['comment' => "扩展字段"])
                ->addColumn('next_days', 'string', ['limit' => 10, 'comment' => "下次执行日期"])
                ->addColumn('next_time', 'string', ['limit' => 8, 'comment' => "下次执行时间"])
                ->addColumn('content', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'comment' => '发送内容', 'default' => null, 'null' => true])
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
