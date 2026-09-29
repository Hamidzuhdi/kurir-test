<?php

namespace Tests\Feature\Api\V1;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidQueryProvider(): array
    {
        return [
            'level above maximum' => [['level' => '6'], 'level.0'],
            'level below minimum' => [['level' => '0'], 'level.0'],
            'level not numeric' => [['level' => 'abc'], 'level.0'],
            'one invalid level in list' => [['level' => '2,x,3'], 'level.1'],
            'unknown sort column' => [['sort' => 'phone'], 'sort'],
            'unknown sort direction' => [['direction' => 'sideways'], 'direction'],
            'per_page below minimum' => [['per_page' => '0'], 'per_page'],
            'per_page above maximum' => [['per_page' => '101'], 'per_page'],
            'per_page not numeric' => [['per_page' => 'many'], 'per_page'],
            'search too long' => [['search' => str_repeat('a', 101)], 'search'],
        ];
    }

    public function test_it_returns_a_paginated_list_of_couriers(): void
    {
        Courier::factory()->count(20)->create();

        $this->getJson(route('v1.couriers.index'))
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'phone', 'email', 'level', 'vehicle_type', 'vehicle_plate_number', 'status', 'address', 'created_at', 'updated_at']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'per_page', 'last_page', 'total'],
            ]);
    }

    public function test_it_returns_the_requested_page(): void
    {
        Courier::factory()->count(20)->create();

        $this->getJson(route('v1.couriers.index', ['page' => 2]))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_it_uses_the_requested_page_size(): void
    {
        Courier::factory()->count(12)->create();

        $this->getJson(route('v1.couriers.index', ['per_page' => 5]))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_it_returns_an_empty_list_when_there_are_no_couriers(): void
    {
        $this->getJson(route('v1.couriers.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_it_sorts_by_name_by_default(): void
    {
        Courier::factory()->create(['name' => 'Charlie']);
        Courier::factory()->create(['name' => 'Alpha']);
        Courier::factory()->create(['name' => 'Bravo']);

        $this->getJson(route('v1.couriers.index'))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Alpha', 'Bravo', 'Charlie']);
    }

    public function test_it_can_sort_by_name_descending(): void
    {
        Courier::factory()->create(['name' => 'Charlie']);
        Courier::factory()->create(['name' => 'Alpha']);
        Courier::factory()->create(['name' => 'Bravo']);

        $this->getJson(route('v1.couriers.index', ['sort' => 'name', 'direction' => 'desc']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Charlie', 'Bravo', 'Alpha']);
    }

    public function test_it_can_sort_by_registration_date_instead_of_name(): void
    {
        Courier::factory()->create(['name' => 'Alpha', 'created_at' => now()->subDays(1)]);
        Courier::factory()->create(['name' => 'Bravo', 'created_at' => now()->subDays(3)]);
        Courier::factory()->create(['name' => 'Charlie', 'created_at' => now()->subDays(2)]);

        $this->getJson(route('v1.couriers.index', ['sort' => 'created_at']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Bravo', 'Charlie', 'Alpha']);
    }

    public function test_it_can_sort_by_registration_date_newest_first(): void
    {
        Courier::factory()->create(['name' => 'Alpha', 'created_at' => now()->subDays(1)]);
        Courier::factory()->create(['name' => 'Bravo', 'created_at' => now()->subDays(3)]);
        Courier::factory()->create(['name' => 'Charlie', 'created_at' => now()->subDays(2)]);

        $this->getJson(route('v1.couriers.index', ['sort' => 'created_at', 'direction' => 'desc']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Alpha', 'Charlie', 'Bravo']);
    }

    public function test_it_finds_couriers_whose_name_contains_all_search_terms(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Budi Santoso']);
        Courier::factory()->create(['name' => 'Agung Prasetyo']);

        $this->getJson('/api/v1/couriers?search=budi+agung')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Budiono Hadi Agung')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_search_ignores_case_and_term_order(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Siti Nurhaliza']);

        $this->getJson(route('v1.couriers.index', ['search' => 'AGUNG budi']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Budiono Hadi Agung']);
    }

    public function test_search_returns_an_empty_list_when_nothing_matches(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);

        $this->getJson(route('v1.couriers.index', ['search' => 'nobody']))
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_search_treats_wildcard_characters_literally(): void
    {
        Courier::factory()->create(['name' => 'Budi Santoso']);
        Courier::factory()->create(['name' => 'Budi_Santoso']);
        Courier::factory()->create(['name' => 'Diskon 100%']);

        $this->getJson(route('v1.couriers.index', ['search' => 'budi_s']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Budi_Santoso']);

        $this->getJson(route('v1.couriers.index', ['search' => '%']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Diskon 100%']);
    }

    public function test_it_filters_by_a_list_of_levels(): void
    {
        foreach (range(1, 5) as $level) {
            Courier::factory()->atLevel($level)->create(['name' => "Courier {$level}"]);
        }

        $this->getJson('/api/v1/couriers?level=2,3')
            ->assertOk()
            ->assertJsonPath('data.*.level', [2, 3])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_it_filters_by_a_single_level(): void
    {
        Courier::factory()->atLevel(1)->count(2)->create();
        Courier::factory()->atLevel(4)->create(['name' => 'Level Four']);

        $this->getJson(route('v1.couriers.index', ['level' => '4']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Level Four']);
    }

    public function test_it_ignores_empty_entries_in_the_level_filter(): void
    {
        Courier::factory()->atLevel(1)->create();
        Courier::factory()->atLevel(2)->create();

        $this->getJson(route('v1.couriers.index', ['level' => '2,']))
            ->assertOk()
            ->assertJsonPath('data.*.level', [2]);
    }

    public function test_an_empty_level_filter_does_not_restrict_the_result(): void
    {
        Courier::factory()->atLevel(1)->create();
        Courier::factory()->atLevel(5)->create();

        $this->getJson('/api/v1/couriers?level=')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_blank_query_parameters_fall_back_to_the_defaults(): void
    {
        Courier::factory()->create(['name' => 'Charlie']);
        Courier::factory()->create(['name' => 'Alpha']);

        $this->getJson('/api/v1/couriers?search=&level=&sort=&direction=&per_page=')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Alpha', 'Charlie'])
            ->assertJsonPath('meta.per_page', 15);
    }

    public function test_search_level_and_sort_can_be_combined(): void
    {
        Courier::factory()->atLevel(2)->create(['name' => 'Budi Kecil', 'created_at' => now()->subDays(2)]);
        Courier::factory()->atLevel(3)->create(['name' => 'Budi Besar', 'created_at' => now()->subDay()]);
        Courier::factory()->atLevel(5)->create(['name' => 'Budi Sakti']);
        Courier::factory()->atLevel(2)->create(['name' => 'Agung Kecil']);

        $this->getJson('/api/v1/couriers?search=budi&level=2,3&sort=created_at&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Budi Besar', 'Budi Kecil']);
    }

    public function test_pagination_links_keep_the_active_query_parameters(): void
    {
        Courier::factory()->atLevel(2)->count(3)->create();

        $nextPageUrl = $this->getJson('/api/v1/couriers?level=2,3&sort=created_at&per_page=2')
            ->assertOk()
            ->json('links.next');

        $this->assertStringContainsString('level=2%2C3', $nextPageUrl);
        $this->assertStringContainsString('sort=created_at', $nextPageUrl);
        $this->assertStringContainsString('per_page=2', $nextPageUrl);
    }

    /**
     * @param  array<string, string>  $query
     */
    #[DataProvider('invalidQueryProvider')]
    public function test_it_rejects_invalid_query_parameters(array $query, string $invalidKey): void
    {
        $this->getJson(route('v1.couriers.index', $query))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($invalidKey);
    }
}
