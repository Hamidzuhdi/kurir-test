<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Courier
 */
class CourierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'level' => $this->level,
            'vehicle_type' => $this->vehicle_type,
            'vehicle_plate_number' => $this->vehicle_plate_number,
            'status' => $this->status,
            'address' => $this->address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
