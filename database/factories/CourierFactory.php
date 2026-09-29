<?php

namespace Database\Factories;

use App\Enums\CourierStatus;
use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vehicleType = fake()->randomElement(VehicleType::cases());

        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'level' => fake()->numberBetween(Courier::MIN_LEVEL, Courier::MAX_LEVEL),
            'vehicle_type' => $vehicleType,
            'vehicle_plate_number' => $vehicleType === VehicleType::Bicycle
                ? null
                : strtoupper(fake()->bothify('B #### ???')),
            'status' => CourierStatus::Active,
            'address' => fake()->address(),
        ];
    }

    /**
     * Indicate the courier's level.
     */
    public function atLevel(int $level): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => $level,
        ]);
    }
}
