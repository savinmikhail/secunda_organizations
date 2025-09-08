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
    public function indexByBuilding(Request $request, int $building, \App\Services\OrganizationSearchService $search)
    {
        $perPage = $search->perPage(request: $request);
        $organizations = $search->byBuilding(request: $request, buildingId: $building, perPage: $perPage);

        if ($organizations->isEmpty() && ! Building::whereKey($building)->exists()) {
            abort(code: 404);
        }

        return OrganizationResource::collection(resource: $organizations);
    }

    /**
     * List organizations related to a given activity (including descendants).
     */
    public function indexByActivity(Request $request, int $activity, \App\Services\OrganizationSearchService $search, \App\Services\ActivityTreeService $tree)
    {
        $perPage = $search->perPage(request: $request);
        $activityIds = $tree->descendantIds(activityId: $activity);
        $organizations = $search->byActivityIds(request: $request, ids: $activityIds, perPage: $perPage);

        if ($organizations->isEmpty() && ! Activity::whereKey($activity)->exists()) {
            abort(code: 404);
        }

        return OrganizationResource::collection(resource: $organizations);
    }

    // Descendant collecting moved to ActivityTreeService

    /**
     * List organizations within a radius from a point or within a rectangular bounding box.
     * Query params:
     *  - per_page: int (default 15, max 100)
     *  - lat, lng, radius_km: for circle search
     *  - or: lat1, lng1, lat2, lng2: for rectangle search
     */
    public function indexByGeo(Request $request, OrganizationSearchService $search)
    {
        $perPage = $search->perPage(request: $request);

        $lat = $request->query(key: 'lat');
        $lng = $request->query(key: 'lng');
        $radiusKm = $request->query(key: 'radius_km');

        $lat1 = $request->query(key: 'lat1');
        $lng1 = $request->query(key: 'lng1');
        $lat2 = $request->query(key: 'lat2');
        $lng2 = $request->query(key: 'lng2');

        if ($lat !== null && $lng !== null && $radiusKm !== null) {
            $lat = (float) $lat;
            $lng = (float) $lng;
            $radiusKm = (float) $radiusKm;
            if (! $this->validLat(lat: $lat) || ! $this->validLng(lng: $lng) || $radiusKm <= 0) {
                return response()->json(['message' => 'Invalid geo parameters'], 422);
            }
            $paginator = $search->withinRadius(request: $request, lat: $lat, lng: $lng, radiusKm: $radiusKm, perPage: $perPage);
            return OrganizationResource::collection(resource: $paginator);
        }

        if ($lat1 !== null && $lng1 !== null && $lat2 !== null && $lng2 !== null) {
            $lat1 = (float) $lat1;
            $lng1 = (float) $lng1;
            $lat2 = (float) $lat2;
            $lng2 = (float) $lng2;
            if (! $this->validLat(lat: $lat1) || ! $this->validLat(lat: $lat2) || ! $this->validLng(lng: $lng1) || ! $this->validLng(lng: $lng2)) {
                return response()->json(['message' => 'Invalid rectangle parameters'], 422);
            }
            $minLat = min($lat1, $lat2);
            $maxLat = max($lat1, $lat2);
            $minLng = min($lng1, $lng2);
            $maxLng = max($lng1, $lng2);

            $paginator = $search->withinRectangle(request: $request, lat1: $lat1, lng1: $lng1, lat2: $lat2, lng2: $lng2, perPage: $perPage);
            return OrganizationResource::collection(resource: $paginator);
        }

        return response()->json(['message' => 'Provide either lat,lng,radius_km or lat1,lng1,lat2,lng2'], 422);
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
        $org = Organization::with(relations: ['phones', 'activities', 'building'])
            ->find(id: $organization);

        if (! $org) {
            abort(code: 404);
        }

        return new OrganizationResource(resource: $org);
    }

    /**
     * Search organizations by activity name. Matches activities by name
     * and includes organizations tagged to their descendant activities.
     * Query params: q (required), per_page (default 15, max 100)
     */
    public function searchByActivity(Request $request, \App\Services\OrganizationSearchService $search)
    {
        $q = trim(string: (string) $request->query(key: 'q', default: ''));
        if ($q === '') {
            return response()->json(['message' => 'Query parameter q is required'], 422);
        }

        $perPage = $search->perPage(request: $request);
        $organizations = $search->searchByActivityName(request: $request, q: $q, tree: app(abstract: \App\Services\ActivityTreeService::class), perPage: $perPage);

        return OrganizationResource::collection(resource: $organizations);
    }

    /**
     * Search organizations by name (case-insensitive substring).
     * Query params: q (required), per_page (default 15, max 100)
     */
    public function searchByName(Request $request, \App\Services\OrganizationSearchService $search)
    {
        $q = trim(string: (string) $request->query(key: 'q', default: ''));
        if ($q === '') {
            return response()->json(['message' => 'Query parameter q is required'], 422);
        }

        $perPage = $search->perPage(request: $request);
        $organizations = $search->searchByName(request: $request, q: $q, perPage: $perPage);

        return OrganizationResource::collection(resource: $organizations);
    }

    // hasPgTrgm moved to OrganizationSearchService
}
