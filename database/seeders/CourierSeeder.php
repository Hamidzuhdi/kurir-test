<?php

namespace Database\Seeders;

use App\Models\Courier;
use Illuminate\Database\Seeder;

class CourierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Courier::factory()->atLevel(2)->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->atLevel(3)->create(['name' => 'Siti Nurhaliza']);
        Courier::factory()->atLevel(5)->create(['name' => 'Agung Prasetyo']);

        Courier::factory()->count(47)->create();
    }
}
