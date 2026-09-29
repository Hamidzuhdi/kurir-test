<?php

namespace Tests\Feature\Api\V1;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_the_courier_from_the_database(): void
    {
        $courier = Courier::factory()->create();

        $this->deleteJson(route('v1.couriers.destroy', $courier))
            ->assertNoContent();

        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
        $this->assertDatabaseCount('couriers', 0);
    }

    public function test_it_keeps_the_other_couriers(): void
    {
        $couriers = Courier::factory()->count(2)->create();

        $this->deleteJson(route('v1.couriers.destroy', $couriers->first()))
            ->assertNoContent();

        $this->assertDatabaseMissing('couriers', ['id' => $couriers->first()->id]);
        $this->assertDatabaseHas('couriers', ['id' => $couriers->last()->id]);
    }

    public function test_a_deleted_courier_can_no_longer_be_found(): void
    {
        $courier = Courier::factory()->create();

        $this->deleteJson(route('v1.couriers.destroy', $courier))->assertNoContent();

        $this->getJson(route('v1.couriers.show', $courier))->assertNotFound();
        $this->deleteJson(route('v1.couriers.destroy', $courier))->assertNotFound();
    }

    public function test_it_returns_not_found_for_an_unknown_courier(): void
    {
        $this->deleteJson(route('v1.couriers.destroy', 999))
            ->assertNotFound();
    }
}
