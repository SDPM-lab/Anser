<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Order extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'o_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unique'         => true,
                'auto_increment' => true,
                'comment'        => "order primary key"
            ],
            'u_key'         => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'comment'        => "user primary key"
            ],
            'p_key'         => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'comment'        => "product primary key"
            ],
            'amount'           => [
                'type'           => 'INT',
                'constraint'     => 200,
                'unsigned'       => true,
                'comment'        => "order amount"
            ],
            'price'           => [
                'type'           => 'INT',
                'constraint'     => 200,
                'unsigned'       => true,
                'comment'        => "order price"
            ],
            'status'           => [
                'type'           => 'VARCHAR',
                'constraint'     => 30,
                'comment'        => "order status"
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
        $this->forge->addKey('o_key', true);
        $this->forge->addForeignKey('p_key', 'product', 'p_key', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('order');
    }

    public function down()
    {
        //
    }
}
