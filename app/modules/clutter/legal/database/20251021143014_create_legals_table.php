
<?php

use Phinx\Migration\AbstractMigration;

final class CreateLegalsTable extends AbstractMigration
{
    protected $tableName = "legals"; //表名称
    protected $comment = '用户隐私协议或其大文本存储表'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'InnoDB', 'id' => false, 'primary_key' => 'id', 'comment' => $this->comment]);

            $table
                ->addColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('name', 'string', ['limit' => 90, 'comment' => '名称', 'default' => ''])
                ->addColumn('title', 'string', ['limit' => 255])
                ->addColumn('slug', 'string', ['limit' => 32, 'comment' => '唯一标识用户协议user-agreement,隐私政策privacy-policy', 'default' => ''])
                ->addColumn('locale', 'string', ['limit' => 16, 'comment' => '多语言', 'default' => 'en-US'])
                ->addColumn('content', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'comment' => '内容', 'default' => null, 'null' => true])
                ->addIndex(['slug', 'locale'], ['unique' => true])
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
