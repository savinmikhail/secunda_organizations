<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrganizationResource;
use App\Models\Activity;
use App\Models\Building;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\OrganizationSearchService;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    /**
     * List organizations located in a specific building.
     */
    public function indexByBuilding(Request $request, int $building)
    {
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

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
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

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

    /**
     * List organizations within a radius from a point or within a rectangular bounding box.
     * Query params:
     *  - per_page: int (default 15, max 100)
     *  - lat, lng, radius_km: for circle search
     *  - or: lat1, lng1, lat2, lng2: for rectangle search
     */
    public function indexByGeo(Request $request, OrganizationSearchService $search)
    {
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $radiusKm = $request->query('radius_km');

        $lat1 = $request->query('lat1');
        $lng1 = $request->query('lng1');
        $lat2 = $request->query('lat2');
        $lng2 = $request->query('lng2');

        if ($lat !== null && $lng !== null && $radiusKm !== null) {
            $lat = (float) $lat;
            $lng = (float) $lng;
            $radiusKm = (float) $radiusKm;
            if (! $this->validLat($lat) || ! $this->validLng($lng) || $radiusKm <= 0) {
                return response()->json(['message' => 'Invalid geo parameters'], 422);
            }
            $paginator = $search->withinRadius($request, $lat, $lng, $radiusKm, $perPage);
            return OrganizationResource::collection($paginator);
        }

        if ($lat1 !== null && $lng1 !== null && $lat2 !== null && $lng2 !== null) {
            $lat1 = (float) $lat1;
            $lng1 = (float) $lng1;
            $lat2 = (float) $lat2;
            $lng2 = (float) $lng2;
            if (! $this->validLat($lat1) || ! $this->validLat($lat2) || ! $this->validLng($lng1) || ! $this->validLng($lng2)) {
                return response()->json(['message' => 'Invalid rectangle parameters'], 422);
            }
            $minLat = min($lat1, $lat2);
            $maxLat = max($lat1, $lat2);
            $minLng = min($lng1, $lng2);
            $maxLng = max($lng1, $lng2);

            $paginator = $search->withinRectangle($request, $lat1, $lng1, $lat2, $lng2, $perPage);
            return OrganizationResource::collection($paginator);
        }

        return response()->json(['message' => 'Provide either lat,lng,radius_km or lat1,lng1,lat2,lng2'], 422);
    }

    private function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $earthRadiusKm = 6371.0;
        $latDelta = rad2deg($radiusKm / $earthRadiusKm);
        $lngDelta = rad2deg($radiusKm / $earthRadiusKm / cos(deg2rad($lat)));
        return [
            $lat - $latDelta, // minLat
            $lat + $latDelta, // maxLat
            $lng - $lngDelta, // minLng
            $lng + $lngDelta, // maxLng
        ];
    }

    private function validLat(float $lat): bool
    {
        return $lat >= -90 && $lat <= 90;
    }
    private function validLng(float $lng): bool
    {
        return $lng >= -180 && $lng <= 180;
    }

    /**
     * Show organization details by ID.
     */
    public function show(int $organization)
    {
        $org = Organization::with(['phones', 'activities', 'building'])
            ->find($organization);

        if (! $org) {
            abort(404);
        }

        return new OrganizationResource($org);
    }

    /**
     * Search organizations by activity name. Matches activities by name
     * and includes organizations tagged to their descendant activities.
     * Query params: q (required), per_page (default 15, max 100)
     */
    public function searchByActivity(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['message' => 'Query parameter q is required'], 422);
        }

        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $matched = Activity::query()
            ->where('name', 'like', "%$q%")
            ->pluck('id')
            ->all();

        if (empty($matched)) {
            // Return empty paginator shape for consistency
            $empty = Organization::query()->whereRaw('1 = 0')->paginate($perPage);
            return OrganizationResource::collection($empty);
        }

        $ids = [];
        foreach ($matched as $id) {
            $ids = array_merge($ids, $this->collectActivityIdsWithDescendants((int) $id));
        }
        $ids = array_values(array_unique($ids));

        $organizations = Organization::with(['phones', 'activities'])
            ->whereHas('activities', function ($q2) use ($ids) {
                $q2->whereIn('activities.id', $ids);
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return OrganizationResource::collection($organizations);
    }

    /**
     * Search organizations by name (case-insensitive substring).
     * Query params: q (required), per_page (default 15, max 100)
     */
    public function searchByName(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['message' => 'Query parameter q is required'], 422);
        }

        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = Organization::with(['phones', 'activities']);

        if ($this->hasPgTrgm()) {
            // PostgreSQL trigram fuzzy search when extension is available
            $query->where(function ($sub) use ($q) {
                $sub->whereRaw('name % ?', [$q])
                    ->orWhereRaw('name ILIKE ?', ['%'.$q.'%']);
            })
            ->orderByRaw('similarity(name, ?) DESC, name ASC', [$q]);
        } else {
            // Portable case-insensitive substring search
            $needle = mb_strtolower($q, 'UTF-8');
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.$needle.'%'])
                  ->orderBy('name');
        }

        $organizations = $query->paginate($perPage)->appends($request->query());

        return OrganizationResource::collection($organizations);
    }

    private function hasPgTrgm(): bool
    {
        try {
            if (DB::getDriverName() !== 'pgsql') {
                return false;
            }
            $row = DB::selectOne("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'pg_trgm') AS installed");
            return (bool) ($row->installed ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
