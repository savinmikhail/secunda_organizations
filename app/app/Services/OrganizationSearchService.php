<?php

namespace App\Services;

use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class OrganizationSearchService
{
    public function withinRectangle(Request $request, float $lat1, float $lng1, float $lat2, float $lng2, int $perPage = 15): LengthAwarePaginator
    {
        $minLat = min($lat1, $lat2); $maxLat = max($lat1, $lat2);
        $minLng = min($lng1, $lng2); $maxLng = max($lng1, $lng2);

        $query = $this->baseQuery()
            ->whereBetween('buildings.latitude', [$minLat, $maxLat])
            ->whereBetween('buildings.longitude', [$minLng, $maxLng])
            ->orderBy('organizations.name');

        return $query->paginate($perPage)->appends($request->query());
    }

    public function withinRadius(Request $request, float $lat, float $lng, float $radiusKm, int $perPage = 15): LengthAwarePaginator
    {
        $expr = $this->haversineSql(':lat', ':lng');

        $query = $this->baseQuery()
            ->selectRaw("{$expr} AS distance_km", [
                'lat' => $lat,
                'lng' => $lng,
            ])
            ->whereRaw("{$expr} <= :radius", [
                'lat' => $lat,
                'lng' => $lng,
                'radius' => $radiusKm,
            ])
            ->orderBy('distance_km')
            ->orderBy('organizations.name');

        return $query->paginate($perPage)->appends($request->query());
    }

    private function baseQuery(): Builder
    {
        return Organization::query()
            ->with(['phones', 'activities', 'building'])
            ->join('buildings', 'buildings.id', '=', 'organizations.building_id')
            ->select('organizations.*');
    }

    /**
     * Haversine distance in kilometers as a PostgreSQL SQL expression.
     * Uses ::double precision casts to ensure correct types.
     */
    private function haversineSql(string $latParam, string $lngParam): string
    {
        // Earth radius in km
        $R = 6371.0;

        return sprintf(
            '%f * acos(LEAST(1.0, '
            . 'cos(radians(%1$s)) * cos(radians(buildings.latitude::double precision)) '
            . '* cos(radians(buildings.longitude::double precision) - radians(%2$s)) '
            . '+ sin(radians(%1$s)) * sin(radians(buildings.latitude::double precision))
            ))',
            $R,
            $latParam,
            $lngParam
        );
    }
}

