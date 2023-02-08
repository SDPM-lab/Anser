<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;
use Bezhanov\Faker\Provider\Commerce;

class Product extends Seeder
{
    public function run()
    {
        // $faker = Factory::create();
        $now   = date("Y-m-d H:i:s");

        $faker = \Faker\Factory::create();
        $faker->addProvider(new Commerce($faker));

        for ($i = 0; $i < 20; $i++) {
            $data = [
                'name'       => $faker->productName(),
                'price'      => $faker->numberBetween(500, 1000),
                'amount'     => $faker->numberBetween(10, 500),
                'created_at' => $faker->dateTimeBetween('-2 month', '-1 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now,
            ];
            $this->db->table('product')->insert($data);
        }
    }
}
