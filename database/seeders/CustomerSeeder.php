<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Measurement;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::factory(10)
            ->create()
            ->each(function (Customer $customer) {
                Measurement::factory()->create([
                    'customer_id' => $customer->id,
                    'type' => 'shirt',
                    'version' => 1,
                ]);

                Measurement::factory()->create([
                    'customer_id' => $customer->id,
                    'type' => 'pant',
                    'version' => 1,
                ]);
            });

        Customer::factory()->create([
            'name' => 'Rahul Sharma',
            'phone' => '9876543210',
            'address' => '42, MG Road, Mumbai',
            'gender' => 'male',
        ]);
    }
}
