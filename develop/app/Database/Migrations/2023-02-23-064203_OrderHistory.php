<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OrderHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'oh_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'path'           => [
                'type'           => 'varchar',
                'constraint'     => 255,
                'null'           => false
            ],
            'method'           => [
                'type'           => 'varchar',
                'constraint'     => 255,
                'null'           => false
            ],
            'status'           => [
                'type'           => 'varchar',
                'constraint'     => 255,
                'null'           => false
            ],
            'o_key'           => [
                'type'           => 'VARCHAR',
                'constraint'     => 200,
                'comment'        => "order primary key",
                'null'           => true
            ],
            'orch_key'         => [
                'type'           => 'varchar',
                'constraint'     => 255,
                'null'           => false
            ],
            "created_at"    => [
                'type'           => 'datetime'
            ],
            "updated_at"    => [
                'type'           => 'datetime'
            ],
            "deleted_at"    => [
                'type'           => 'datetime',
                'null'           => true
            ]
        ]);
        $this->forge->addKey('oh_key', true);
        $this->forge->createTable('order_history');
    }

    public function down()
    {
        //
    }
}
