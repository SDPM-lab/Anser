<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class Product extends Seeder
{
    public function run()
    {
        $faker = Factory::create();
        $now   = date("Y-m-d H:i:s");

        for ($i = 0; $i < 50; $i++) {
            $data = [
                'name'       => $faker->words(1, true),
                'price'      => $faker->numberBetween(500, 1000),
                'amount'     => $faker->numberBetween(10, 500),
                'created_at' => $faker->dateTimeBetween('-2 month', '-1 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now,
            ];
            $this->db->table('product')->insert($data);

            $orderData =[
                "u_key"  => random_int(1, 50),
                "p_key"  => $this->db->insertID(),
                "amount" => random_int(1, 700),
                "price"  => $data["price"],
                "status" => "orderCreate",
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $this->db->table('order')->insert($orderData);
        }
    }
}
