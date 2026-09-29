<?php

namespace Tests\Feature\Api\V1;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function invalidFieldProvider(): array
    {
        return [
            'name too short' => ['name', 'A'],
            'name too long' => ['name', str_repeat('a', 256)],
            'phone with letters' => ['phone', '0812abc45678'],
            'phone too short' => ['phone', '08123'],
            'email malformed' => ['email', 'not-an-email'],
            'level below minimum' => ['level', 0],
            'level above maximum' => ['level', 6],
            'level not an integer' => ['level', 2.5],
            'vehicle type unknown' => ['vehicle_type', 'helicopter'],
            'vehicle plate too long' => ['vehicle_plate_number', str_repeat('B', 16)],
            'status unknown' => ['status', 'retired'],
            'address too long' => ['address', str_repeat('a', 1001)],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'name' => ['name'],
            'phone' => ['phone'],
            'level' => ['level'],
            'vehicle_type' => ['vehicle_type'],
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
            'level' => 4,
            'vehicle_type' => 'car',
            'vehicle_plate_number' => 'B 9999 ABC',
            'status' => 'suspended',
            'address' => 'Jl. Sudirman No. 10, Jakarta',
            ...$overrides,
        ];
    }

    public function test_it_replaces_a_courier_with_put(): void
    {
        $courier = Courier::factory()->create(['name' => 'Old Name']);
        $payload = $this->validPayload();

        $this->putJson(route('v1.couriers.update', $courier), $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $courier->id)
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.level', 4)
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, ...$payload]);
        $this->assertDatabaseMissing('couriers', ['name' => 'Old Name']);
    }

    public function test_it_changes_only_the_sent_fields_with_patch(): void
    {
        $courier = Courier::factory()->atLevel(1)->create();

        $this->patchJson(route('v1.couriers.update', $courier), ['level' => 5])
            ->assertOk()
            ->assertJsonPath('data.level', 5);

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'level' => 5,
            'name' => $courier->name,
            'phone' => $courier->phone,
            'email' => $courier->email,
            'vehicle_type' => $courier->vehicle_type->value,
            'status' => $courier->status->value,
        ]);
    }

    public function test_it_can_change_the_status(): void
    {
        $courier = Courier::factory()->create();

        $this->patchJson(route('v1.couriers.update', $courier), ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'status' => 'inactive']);
    }

    public function test_it_can_clear_optional_fields(): void
    {
        $courier = Courier::factory()->create();

        $this->patchJson(route('v1.couriers.update', $courier), ['email' => null, 'address' => null])
            ->assertOk();

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'email' => null, 'address' => null]);
    }

    public function test_a_courier_can_keep_its_own_phone_number_and_email(): void
    {
        $courier = Courier::factory()->create(['phone' => '081234567890', 'email' => 'budiono@example.com']);

        $this->putJson(route('v1.couriers.update', $courier), $this->validPayload())
            ->assertOk();

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'phone' => '081234567890']);
    }

    public function test_it_rejects_a_phone_number_used_by_another_courier(): void
    {
        Courier::factory()->create(['phone' => '081234567890']);
        $courier = Courier::factory()->create(['phone' => '089999999999']);

        $this->patchJson(route('v1.couriers.update', $courier), ['phone' => '081234567890'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'phone' => '089999999999']);
    }

    public function test_it_rejects_an_email_used_by_another_courier(): void
    {
        Courier::factory()->create(['email' => 'taken@example.com']);
        $courier = Courier::factory()->create(['email' => 'mine@example.com']);

        $this->patchJson(route('v1.couriers.update', $courier), ['email' => 'taken@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'email' => 'mine@example.com']);
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_it_rejects_invalid_input_without_changing_the_courier(string $field, mixed $value): void
    {
        $courier = Courier::factory()->create();

        $this->putJson(route('v1.couriers.update', $courier), $this->validPayload([$field => $value]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'name' => $courier->name]);
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_patch_validates_the_sent_fields_too(string $field, mixed $value): void
    {
        $courier = Courier::factory()->create();

        $this->patchJson(route('v1.couriers.update', $courier), [$field => $value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    #[DataProvider('requiredFieldProvider')]
    public function test_put_requires_the_essential_fields(string $field): void
    {
        $courier = Courier::factory()->create();
        $payload = $this->validPayload();
        unset($payload[$field]);

        $this->putJson(route('v1.couriers.update', $courier), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public function test_it_returns_not_found_for_an_unknown_courier(): void
    {
        $this->putJson(route('v1.couriers.update', 999), $this->validPayload())
            ->assertNotFound();

        $this->assertDatabaseCount('couriers', 0);
    }
}
