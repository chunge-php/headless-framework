
<?php

use Phinx\Migration\AbstractMigration;

final class CreateTemplatsTable extends AbstractMigration
{
    protected $tableName = "templats"; //表名称
    protected $comment = '模板管理'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '名称', 'default' => ''])
                ->addColumn('title', 'string', ['limit' => 190, 'comment' => '主题名称', 'default' => ''])
                ->addColumn('type', 'integer', [ 'comment' => '类型0普通短信1彩信2邮箱', 'default' =>0])
                ->addColumn('content', 'text', ['comment' => '内容'])
                ->addColumn('status', 'integer', [ 'comment' => '状态0禁用1启用', 'default' =>1])
                ->addColumn('sort', 'integer', [ 'comment' => '排序', 'default' =>0])
                ->addColumn('use_total', 'integer', [ 'comment' => '使用次数', 'default' =>0])
                ->addColumn('uid', 'biginteger', ['limit' => 20, 'comment' => '用户id'])
                ->addIndex(['uid', 'name','type'], ['unique' => true])
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
