<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PaymentHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ph_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => "order history primary key"
            ],
            'type'             => [
                'type'           => 'VARCHAR',
                'constraint'     => 255,
                'null'           => false,
                'comment'        => "service type"
            ],
            'pm_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'comment'        => "Payment primary key",
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
        $this->forge->addKey('ph_key', true);
        $this->forge->createTable('payment_history');
    }

    public function down()
    {
        //
    }
}
