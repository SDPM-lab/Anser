<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Wallet extends Seeder
{
    public function run()
    {
        $now   = date("Y-m-d H:i:s");

        $data = [
            [
                'u_key'      => 1,
                'balance'    => 50000000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'u_key'      => 2,
                'balance'    => 50000000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'u_key'      => 3,
                'balance'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('wallet')->insertBatch($data);
    }
}
