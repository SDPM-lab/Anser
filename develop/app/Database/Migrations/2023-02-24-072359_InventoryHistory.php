<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InventoryHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ih_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => "inventory history primary key"
            ],
            'type'             => [
                'type'           => 'VARCHAR',
                'constraint'     => 255,
                'null'           => false,
                'comment'        => "service type"
            ],
            'p_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'comment'        => "Payment primary key",
                'null'           => false
            ],
            'amount'           => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'null'           => false
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
        $this->forge->addKey('ih_key', true);
        $this->forge->createTable('inventory_history');
    }

    public function down()
    {
        //
    }
}
