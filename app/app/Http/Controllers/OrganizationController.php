<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrganizationResource;
use App\Models\Activity;
use App\Models\Building;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * List organizations located in a specific building.
     */
    public function indexByBuilding(Request $request, int $building)
    {
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) { $perPage = 15; }
        if ($perPage > 100) { $perPage = 100; }

        $query = Organization::with(['phones', 'activities'])
            ->where('building_id', $building)
            ->orderBy('name');

        $organizations = $query->paginate($perPage)->appends($request->query());

        if ($organizations->isEmpty() && ! Building::whereKey($building)->exists()) {
            abort(404);
        }

        return OrganizationResource::collection($organizations);
    }

    /**
     * List organizations related to a given activity (including descendants).
     */
    public function indexByActivity(Request $request, int $activity)
    {
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) { $perPage = 15; }
        if ($perPage > 100) { $perPage = 100; }

        $activityIds = $this->collectActivityIdsWithDescendants($activity);

        $query = Organization::with(['phones', 'activities'])
            ->whereHas('activities', function ($q) use ($activityIds) {
                $q->whereIn('activities.id', $activityIds);
            })
            ->orderBy('name');

        $organizations = $query->paginate($perPage)->appends($request->query());

        if ($organizations->isEmpty() && ! Activity::whereKey($activity)->exists()) {
            abort(404);
        }

        return OrganizationResource::collection($organizations);
    }

    private function collectActivityIdsWithDescendants(int $activityId): array
    {
        $all = [$activityId];
        $frontier = [$activityId];

        while (!empty($frontier)) {
            $children = Activity::whereIn('parent_id', $frontier)->pluck('id')->all();
            $children = array_values(array_diff($children, $all));
            if (empty($children)) {
                break;
            }
            $all = array_merge($all, $children);
            $frontier = $children;
        }

        return $all;
    }
}

