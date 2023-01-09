<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class Wallet extends Seeder
{
    public function run()
    {
        $faker = Factory::create();
        $now   = date("Y-m-d H:i:s");

        for ($i = 1; $i < 5; $i++) {
            $data = [
                'u_key'      => $i,
                'balance'    => $faker->numberBetween(500, 100000),
                'created_at' => $faker->dateTimeBetween('-2 month', '-1 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now,
            ];
            $this->db->table('wallet')->insert($data);
        }
    }
}
