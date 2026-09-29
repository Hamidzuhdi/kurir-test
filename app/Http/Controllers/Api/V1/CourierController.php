<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexCourierRequest;
use App\Http\Requests\Api\V1\StoreCourierRequest;
use App\Http\Requests\Api\V1\UpdateCourierRequest;
use App\Http\Resources\Api\V1\CourierResource;
use App\Models\Courier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CourierController extends Controller
{
    /**
     * Display a paginated list of couriers, sorted by name unless told otherwise.
     */
    public function index(IndexCourierRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $couriers = Courier::query()
            ->search($filters['search'] ?? null)
            ->ofLevels($filters['level'] ?? [])
            ->sortedBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate($filters['per_page'] ?? IndexCourierRequest::DEFAULT_PER_PAGE)
            ->withQueryString();

        return CourierResource::collection($couriers);
    }

    /**
     * Store a newly created courier.
     */
    public function store(StoreCourierRequest $request): JsonResponse
    {
        $courier = Courier::create($request->validated());

        return CourierResource::make($courier)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified courier.
     */
    public function show(Courier $courier): CourierResource
    {
        return CourierResource::make($courier);
    }

    /**
     * Update the specified courier.
     */
    public function update(UpdateCourierRequest $request, Courier $courier): CourierResource
    {
        $courier->update($request->validated());

        return CourierResource::make($courier);
    }

    /**
     * Remove the specified courier.
     */
    public function destroy(Courier $courier): Response
    {
        $courier->delete();

        return response()->noContent();
    }
}
