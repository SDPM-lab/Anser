<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class WalletHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'wh_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => "wallet history primary key"
            ],
            'type'             => [
                'type'           => 'VARCHAR',
                'constraint'     => 255,
                'null'           => false,
                'comment'        => "service type",
            ],
            'u_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'comment'        => "Payment primary key",
                'null'           => false
            ],
            'balance'           => [
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
        $this->forge->addKey('wh_key', true);
        $this->forge->createTable('wallet_history');
    }

    public function down()
    {
        //
    }
}
