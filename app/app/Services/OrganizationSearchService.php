<?php

namespace App\Services;

use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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
        $expr = $this->haversineSqlNumeric($lat, $lng);

        $query = $this->baseQuery()
            ->selectRaw("{$expr} AS distance_km")
            ->whereRaw("{$expr} <= ?", [$radiusKm])
            ->orderByRaw("{$expr} ASC")
            ->orderBy('organizations.name');

        return $query->paginate($perPage)->appends($request->query());
    }

    private function baseQuery(): EloquentBuilder
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
            '%1$f * acos(GREATEST(-1.0, LEAST(1.0, '
            . 'cos(radians(%2$s)) * cos(radians(buildings.latitude::double precision)) '
            . '* cos(radians(buildings.longitude::double precision) - radians(%3$s)) '
            . '+ sin(radians(%2$s)) * sin(radians(buildings.latitude::double precision))
            )))',
            $R,
            $latParam,
            $lngParam
        );
    }

    private function haversineSqlNumeric(float $lat, float $lng): string
    {
        // ensure decimals with dot
        $latS = number_format($lat, 8, '.', '');
        $lngS = number_format($lng, 8, '.', '');
        return $this->haversineSql($latS, $lngS);
    }
}
