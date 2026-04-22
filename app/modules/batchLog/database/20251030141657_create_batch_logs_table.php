
<?php

use Phinx\Migration\AbstractMigration;

final class CreateBatchLogsTable extends AbstractMigration
{
    protected $tableName = "batch_logs"; //表名称
    protected $comment = '批次任务记录'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '名称', 'default' => ''])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addColumn('scope_id', 'biginteger', ['limit' => 20, 'comment' => '作用域id'])
                ->addColumn('scope', 'string', ['limit' => 30, 'comment' => '应用领域', 'default' => ''])
                ->addColumn('status', 'integer', ['limit' => 1, 'comment' => '状态0未执行1完成2执行中3失败4部分成功', 'default' => 0])
                ->addColumn('total', 'integer', ['limit' => 10, 'comment' => '总数', 'default' => 0])
                ->addColumn('success', 'integer', ['limit' => 10, 'comment' => '成功数', 'default' => 0])
                ->addColumn('fail', 'integer', ['limit' => 10, 'comment' => '失败数', 'default' => 0])
                ->addColumn('exists', 'integer', [
                    'limit' => 10,
                    'comment' => '已存在的记录数',
                    'default' => 0
                ])
                ->addColumn('invalid_phones', 'json', [
                    'comment' => '无效手机号明细（JSON格式）',
                    'default' => json_encode([])
                ])
                // 新增：任务开始/结束时间
                ->addColumn('start_time', 'timestamp', ['comment' => '任务开始时间', 'null' => true])
                ->addColumn('end_time', 'timestamp', ['comment' => '任务结束时间', 'null' => true])
                // 新增：任务耗时（秒）
                ->addColumn('duration', 'integer', ['limit' => 11, 'comment' => '任务耗时（秒）', 'default' => 0])
                ->addColumn('describe', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'comment' => '错误描述', 'default' => null, 'null' => true])
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
                'start_time' => setTableKey('timestamp', '任务开始时间', null, null, true),
                'end_time' => setTableKey('timestamp', '任务结束时间', null, null, true),
                'duration' => setTableKey('integer', '任务耗时（秒）', 0, 11, true),
                'exists' => setTableKey('integer', '已存在的记录数', 0, 11, true),
                'invalid_phones' => setTableKey('json', '无效手机号明细（JSON格式）', null, null, true),
            ];
            setTableForm($table, $columns);
        }
    }
}
