<?php

namespace App\Services;

use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use App\Models\Activity;
use Illuminate\Http\Request;

class OrganizationSearchService
{
    public function withinRectangle(Request $request, float $lat1, float $lng1, float $lat2, float $lng2, int $perPage = 15): LengthAwarePaginator
    {
        $minLat = min($lat1, $lat2);
        $maxLat = max($lat1, $lat2);
        $minLng = min($lng1, $lng2);
        $maxLng = max($lng1, $lng2);

        $query = $this->baseQuery()
            ->whereBetween('buildings.latitude', [$minLat, $maxLat])
            ->whereBetween(column: 'buildings.longitude', values: [$minLng, $maxLng])
            ->orderBy(column: 'organizations.name');

        return $query->paginate(perPage: $perPage)->appends(key: $request->query());
    }

    public function withinRadius(Request $request, float $lat, float $lng, float $radiusKm, int $perPage = 15): LengthAwarePaginator
    {
        $expr = $this->haversineSqlNumeric(lat: $lat, lng: $lng);

        $query = $this->baseQuery()
            ->selectRaw("{$expr} AS distance_km")
            ->whereRaw(sql: "{$expr} <= ?", bindings: [$radiusKm])
            ->orderByRaw(sql: "{$expr} ASC")
            ->orderBy(column: 'organizations.name');

        return $query->paginate(perPage: $perPage)->appends(key: $request->query());
    }

    public function perPage(Request $request): int
    {
        $perPage = (int) $request->query(key: 'per_page', default: 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }
        return $perPage;
    }

    public function byBuilding(Request $request, int $buildingId, int $perPage = 15): LengthAwarePaginator
    {
        return Organization::with(relations: ['phones', 'activities'])
            ->where(column: 'building_id', operator: $buildingId)
            ->orderBy('name')
            ->paginate(perPage: $perPage)
            ->appends(key: $request->query());
    }

    public function byActivityIds(Request $request, array $ids, int $perPage = 15): LengthAwarePaginator
    {
        return Organization::with(relations: ['phones', 'activities'])
            ->whereHas(relation: 'activities', callback: function ($q) use ($ids) {
                $q->whereIn('activities.id', $ids);
            })
            ->orderBy('name')
            ->paginate(perPage: $perPage)
            ->appends(key: $request->query());
    }

    public function searchByActivityName(Request $request, string $q, \App\Services\ActivityTreeService $tree, int $perPage = 15): LengthAwarePaginator
    {
        $matched = Activity::query()
            ->when(DB::getDriverName() === 'pgsql', function ($query) use ($q) {
                $query->where(column: 'name', operator: 'ILIKE', value: '%'.$q.'%');
            }, function ($query) use ($q) {
                $query->where(column: 'name', operator: 'like', value: '%'.$q.'%');
            })
            ->pluck('id')
            ->all();

        if (empty($matched)) {
            return Organization::query()->whereRaw('1 = 0')->paginate(perPage: $perPage)->appends(key: $request->query());
        }

        $ids = [];
        foreach ($matched as $id) {
            $ids = array_merge($ids, $tree->descendantIds(activityId: (int) $id));
        }
        $ids = array_values(array: array_unique(array: $ids));

        return $this->byActivityIds(request: $request, ids: $ids, perPage: $perPage);
    }

    public function searchByName(Request $request, string $q, int $perPage = 15): LengthAwarePaginator
    {
        $query = Organization::with(relations: ['phones', 'activities']);

        if ($this->hasPgTrgm()) {
            $query->where(column: function ($sub) use ($q) {
                $sub->whereRaw('name % ?', [$q])
                    ->orWhereRaw(sql: 'name ILIKE ?', bindings: ['%'.$q.'%']);
            })
            ->orderByRaw('similarity(name, ?) DESC, name ASC', [$q]);
        } else {
            $needle = mb_strtolower(string: $q, encoding: 'UTF-8');
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.$needle.'%'])
                  ->orderBy(column: 'name');
        }

        return $query->paginate(perPage: $perPage)->appends(key: $request->query());
    }

    private function baseQuery(): EloquentBuilder
    {
        return Organization::query()
            ->with(relations: ['phones', 'activities', 'building'])
            ->join('buildings', 'buildings.id', '=', 'organizations.building_id')
            ->select(columns: 'organizations.*');
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
        $latS = number_format(num: $lat, decimals: 8, thousands_separator: '');
        $lngS = number_format(num: $lng, decimals: 8, thousands_separator: '');
        return $this->haversineSql(latParam: $latS, lngParam: $lngS);
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
