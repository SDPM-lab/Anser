<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Payment extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'pm_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => "Payment primary key"
            ],
            'u_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'comment'        => "user primary key"
            ],
            'o_key'           => [
                'type'           => 'VARCHAR',
                'constraint'     => 200,
                'comment'        => "order primary key"
            ],
            'total'           => [
                'type'           => 'INT',
                'constraint'     => 200,
                'unsigned'       => true,
                'comment'        => "total price"
            ],
            'status'           => [
                'type'           => 'VARCHAR',
                'constraint'     => 30,
                'comment'        => "payment status"
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
        $this->forge->addKey('pm_key', true);
        $this->forge->createTable('payment');
    }

    public function down()
    {
        //
    }
}
