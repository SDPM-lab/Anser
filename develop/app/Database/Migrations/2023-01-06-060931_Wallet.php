<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Wallet extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'u_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true
            ],
            'balance'           => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true
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
        $this->forge->addKey('u_key', true);
        $this->forge->createTable('wallet');
    }

    public function down()
    {
        //
    }
}
