<?php

namespace App\Models;

use App\Enums\CourierStatus;
use App\Enums\VehicleType;
use Database\Factories\CourierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'phone',
    'email',
    'level',
    'vehicle_type',
    'vehicle_plate_number',
    'status',
    'address',
])]
class Courier extends Model
{
    /** @use HasFactory<CourierFactory> */
    use HasFactory;

    public const int MIN_LEVEL = 1;

    public const int MAX_LEVEL = 5;

    /**
     * Columns the courier list may be sorted by.
     *
     * @var list<string>
     */
    public const array SORTABLE_COLUMNS = ['name', 'created_at'];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => CourierStatus::Active->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'vehicle_type' => VehicleType::class,
            'status' => CourierStatus::class,
        ];
    }

    /**
     * Match couriers whose name contains every whitespace-separated term of the
     * keyword, in any order and regardless of case ("budi agung" finds
     * "Budiono Hadi Agung").
     *
     * @param  Builder<Courier>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $keyword): void
    {
        $terms = preg_split('/\s+/', trim((string) $keyword), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($terms as $term) {
            $escapedTerm = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term));

            $query->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", ["%{$escapedTerm}%"]);
        }
    }

    /**
     * Restrict couriers to the given levels. An empty list applies no restriction.
     *
     * @param  Builder<Courier>  $query
     * @param  array<int, int|string>  $levels
     */
    #[Scope]
    protected function ofLevels(Builder $query, array $levels): void
    {
        if ($levels === []) {
            return;
        }

        $query->whereIn('level', array_map('intval', $levels));
    }

    /**
     * Order by one of the sortable columns, falling back to the id so that
     * pagination stays stable when several couriers share the same value.
     *
     * @param  Builder<Courier>  $query
     * @param  'name'|'created_at'  $column
     * @param  'asc'|'desc'  $direction
     */
    #[Scope]
    protected function sortedBy(Builder $query, string $column, string $direction = 'asc'): void
    {
        $query->orderBy($column, $direction)->orderBy('id');
    }
}
