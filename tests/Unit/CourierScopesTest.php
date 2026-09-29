<?php

namespace Tests\Unit;

use App\Enums\CourierStatus;
use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierScopesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function names(?string $keyword): array
    {
        return Courier::query()->search($keyword)->orderBy('id')->pluck('name')->all();
    }

    public function test_search_requires_every_term_to_match_in_any_order(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Budi Santoso']);

        $this->assertSame(['Budiono Hadi Agung'], $this->names('budi agung'));
        $this->assertSame(['Budiono Hadi Agung'], $this->names('agung budi'));
        $this->assertSame(['Budiono Hadi Agung', 'Budi Santoso'], $this->names('budi'));
    }

    public function test_search_is_case_insensitive_and_ignores_extra_whitespace(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);

        $this->assertSame(['Budiono Hadi Agung'], $this->names('  BUDI   aGuNg '));
    }

    public function test_a_blank_search_applies_no_restriction(): void
    {
        Courier::factory()->count(2)->create();

        $this->assertCount(2, $this->names(null));
        $this->assertCount(2, $this->names(''));
        $this->assertCount(2, $this->names('   '));
    }

    public function test_search_treats_like_wildcards_and_the_escape_character_literally(): void
    {
        Courier::factory()->create(['name' => 'Budi Santoso']);
        Courier::factory()->create(['name' => 'Budi_Santoso']);
        Courier::factory()->create(['name' => 'Diskon 100%']);
        Courier::factory()->create(['name' => 'Wow!']);

        $this->assertSame(['Budi_Santoso'], $this->names('budi_s'));
        $this->assertSame(['Diskon 100%'], $this->names('%'));
        $this->assertSame(['Wow!'], $this->names('!'));
    }

    public function test_search_only_looks_at_the_name(): void
    {
        Courier::factory()->create(['name' => 'Budi Santoso', 'email' => 'agung@example.com']);

        $this->assertSame([], $this->names('agung'));
    }

    public function test_of_levels_keeps_only_the_given_levels(): void
    {
        foreach (range(1, 5) as $level) {
            Courier::factory()->atLevel($level)->create();
        }

        $this->assertSame([2, 3], Courier::query()->ofLevels([2, 3])->orderBy('level')->pluck('level')->all());
        $this->assertSame([5], Courier::query()->ofLevels(['5'])->pluck('level')->all());
    }

    public function test_of_levels_applies_no_restriction_for_an_empty_list(): void
    {
        Courier::factory()->count(3)->create();

        $this->assertCount(3, Courier::query()->ofLevels([])->get());
    }

    public function test_sorted_by_orders_by_the_given_column_and_direction(): void
    {
        Courier::factory()->create(['name' => 'Bravo', 'created_at' => now()->subDays(1)]);
        Courier::factory()->create(['name' => 'Alpha', 'created_at' => now()->subDays(2)]);
        Courier::factory()->create(['name' => 'Charlie', 'created_at' => now()->subDays(3)]);

        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], Courier::query()->sortedBy('name')->pluck('name')->all());
        $this->assertSame(['Charlie', 'Bravo', 'Alpha'], Courier::query()->sortedBy('name', 'desc')->pluck('name')->all());
        $this->assertSame(['Charlie', 'Alpha', 'Bravo'], Courier::query()->sortedBy('created_at')->pluck('name')->all());
    }

    public function test_sorted_by_falls_back_to_the_id_for_equal_values(): void
    {
        $first = Courier::factory()->create(['name' => 'Same Name']);
        $second = Courier::factory()->create(['name' => 'Same Name']);

        $this->assertSame([$first->id, $second->id], Courier::query()->sortedBy('name')->pluck('id')->all());
        $this->assertSame([$first->id, $second->id], Courier::query()->sortedBy('name', 'desc')->pluck('id')->all());
    }

    public function test_attributes_are_cast_and_new_couriers_are_active_by_default(): void
    {
        $courier = Courier::factory()->create(['level' => 3, 'vehicle_type' => 'van'])->fresh();

        $this->assertSame(3, $courier->level);
        $this->assertSame(VehicleType::Van, $courier->vehicle_type);
        $this->assertSame(CourierStatus::Active, $courier->status);
        $this->assertSame(CourierStatus::Active, (new Courier)->status);
    }
}
