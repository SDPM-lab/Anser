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
            'pm_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'comment'        => "Payment primary key"
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
