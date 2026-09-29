<?php

namespace Tests\Feature\Api\V1;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_all_data_of_a_courier(): void
    {
        $courier = Courier::factory()->create();

        $this->getJson(route('v1.couriers.show', $courier))
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $courier->id,
                    'name' => $courier->name,
                    'phone' => $courier->phone,
                    'email' => $courier->email,
                    'level' => $courier->level,
                    'vehicle_type' => $courier->vehicle_type->value,
                    'vehicle_plate_number' => $courier->vehicle_plate_number,
                    'status' => $courier->status->value,
                    'address' => $courier->address,
                    'created_at' => $courier->created_at->toJSON(),
                    'updated_at' => $courier->updated_at->toJSON(),
                ],
            ]);
    }

    public function test_it_only_returns_the_requested_courier(): void
    {
        Courier::factory()->create();
        $courier = Courier::factory()->create();

        $this->getJson(route('v1.couriers.show', $courier))
            ->assertOk()
            ->assertJsonPath('data.id', $courier->id);
    }

    public function test_it_returns_not_found_for_an_unknown_courier(): void
    {
        $this->getJson(route('v1.couriers.show', 999))
            ->assertNotFound();
    }
}
