<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CourierStatus;
use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function invalidFieldProvider(): array
    {
        return [
            'name missing' => ['name', null],
            'name too short' => ['name', 'A'],
            'name too long' => ['name', str_repeat('a', 256)],
            'name not a string' => ['name', ['Budi']],
            'phone missing' => ['phone', null],
            'phone with letters' => ['phone', '0812abc45678'],
            'phone too short' => ['phone', '08123'],
            'phone too long' => ['phone', '0812345678901234567'],
            'email malformed' => ['email', 'not-an-email'],
            'email too long' => ['email', str_repeat('a', 251).'@x.id'],
            'level missing' => ['level', null],
            'level below minimum' => ['level', 0],
            'level above maximum' => ['level', 6],
            'level not an integer' => ['level', 2.5],
            'level not numeric' => ['level', 'high'],
            'vehicle type missing' => ['vehicle_type', null],
            'vehicle type unknown' => ['vehicle_type', 'helicopter'],
            'vehicle plate too long' => ['vehicle_plate_number', str_repeat('B', 16)],
            'status unknown' => ['status', 'retired'],
            'address too long' => ['address', str_repeat('a', 1001)],
        ];
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function validLevelProvider(): array
    {
        return [
            'lowest level' => [1],
            'middle level' => [3],
            'highest level' => [5],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budiono@example.com',
            'level' => 2,
            'vehicle_type' => 'motorcycle',
            'vehicle_plate_number' => 'B 1234 XYZ',
            'status' => 'inactive',
            'address' => 'Jl. Merdeka No. 1, Jakarta',
            ...$overrides,
        ];
    }

    public function test_it_stores_a_courier_in_the_database(): void
    {
        $payload = $this->validPayload();

        $this->postJson(route('v1.couriers.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.level', 2)
            ->assertJsonPath('data.vehicle_type', 'motorcycle')
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonStructure(['data' => ['id', 'created_at', 'updated_at']]);

        $this->assertDatabaseCount('couriers', 1);
        $this->assertDatabaseHas('couriers', $payload);
    }

    public function test_the_stored_courier_can_be_read_back(): void
    {
        $id = $this->postJson(route('v1.couriers.store'), $this->validPayload())->json('data.id');

        $courier = Courier::findOrFail($id);

        $this->assertSame('Budiono Hadi Agung', $courier->name);
        $this->assertSame(2, $courier->level);
        $this->assertSame(VehicleType::Motorcycle, $courier->vehicle_type);
        $this->assertSame(CourierStatus::Inactive, $courier->status);
    }

    public function test_it_only_requires_the_essential_fields(): void
    {
        $payload = [
            'name' => 'Siti Nurhaliza',
            'phone' => '+6281234567890',
            'level' => 1,
            'vehicle_type' => 'bicycle',
        ];

        $this->postJson(route('v1.couriers.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.vehicle_plate_number', null)
            ->assertJsonPath('data.address', null);

        $this->assertDatabaseHas('couriers', [...$payload, 'email' => null, 'address' => null]);
    }

    public function test_a_new_courier_is_active_by_default(): void
    {
        $payload = $this->validPayload();
        unset($payload['status']);

        $this->postJson(route('v1.couriers.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('couriers', ['phone' => $payload['phone'], 'status' => 'active']);
    }

    #[DataProvider('validLevelProvider')]
    public function test_it_accepts_every_level_from_one_to_five(int $level): void
    {
        $this->postJson(route('v1.couriers.store'), $this->validPayload(['level' => $level]))
            ->assertCreated();

        $this->assertDatabaseHas('couriers', ['level' => $level]);
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_it_rejects_invalid_input_without_storing_anything(string $field, mixed $value): void
    {
        $this->postJson(route('v1.couriers.store'), $this->validPayload([$field => $value]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('couriers', 0);
    }

    public function test_it_rejects_an_empty_request(): void
    {
        $this->postJson(route('v1.couriers.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'level', 'vehicle_type']);

        $this->assertDatabaseCount('couriers', 0);
    }

    public function test_it_rejects_a_phone_number_that_is_already_registered(): void
    {
        Courier::factory()->create(['phone' => '081234567890']);

        $this->postJson(route('v1.couriers.store'), $this->validPayload(['phone' => '081234567890']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->assertDatabaseCount('couriers', 1);
    }

    public function test_it_rejects_an_email_that_is_already_registered(): void
    {
        Courier::factory()->create(['email' => 'budiono@example.com']);

        $this->postJson(route('v1.couriers.store'), $this->validPayload(['email' => 'budiono@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('couriers', 1);
    }
}
