<?php

namespace App\Http\Controllers;

use App\Http\Resources\BuildingResource;
use App\Models\Building;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $buildings = Building::query()
            ->orderBy('address')
            ->paginate($perPage)
            ->appends($request->query());

        return BuildingResource::collection($buildings);
    }
}
