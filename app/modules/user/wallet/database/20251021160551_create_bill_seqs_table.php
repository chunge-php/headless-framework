
<?php

use Phinx\Migration\AbstractMigration;

final class CreateBillSeqsTable extends AbstractMigration
{
    protected $tableName = "bill_seqs"; //表名称
    protected $comment = '账单编号生成记录'; //描述
    public function change()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName,  [
                'engine'       => 'InnoDB',
                'id'           => false,                    // 没有自增id
                'primary_key'  => ['biz', 'ymd'],           // 复合主键：业务+日期
                'comment'      => $this->comment,
                'encoding'     => 'utf8mb4',
                'collation'    => 'utf8mb4_unicode_ci',
            ]);
            $table
                ->addColumn('biz', 'string', [
                    'limit'  => 32,
                    'null'   => false,
                    'comment' => '业务标识：BILL/ORDER等',
                ])
                ->addColumn('ymd', 'char', [
                    'limit'  => 8,
                    'null'   => false,
                    'comment' => 'YYYYMMDD（按 America/New_York 计算）',
                ])
                ->addColumn('val', 'biginteger', [
                    'null'    => false,
                    'default' => 0,
                    'signed'  => false,
                    'comment' => '当日已使用的序号（原子自增）',
                ])
                ->addColumn('updated_at', 'datetime', [
                    'null'    => true,
                    'comment' => '更新时间（应用层维护）',
                ])
                // 可选：如果想要自动填充创建时间（MySQL 5.7 允许）
                ->addColumn('created_at', 'datetime', [
                    'null'    => true,
                    'default' => null,
                    'comment' => '创建时间（可在首次插入时填）',
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
