<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Product extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'p_key'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => "product primary key"
            ],
            'name'         => [
                'type'           => 'varchar',
                'constraint'     => 255,
                'null'           => false,
                'comment'        => "product name"
            ],
            'price'           => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'comment'        => "product price"
            ],
            'amount'           => [
                'type'           => 'INT',
                'constraint'     => 200,
                'unsigned'       => true,
                'comment'        => "product amount"
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
        $this->forge->addKey('p_key', true);
        $this->forge->createTable('product');
    }

    public function down()
    {
        //
    }
}
