<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'building_id' => $this->building_id,
            'distance_km' => $this->when(condition: isset($this->distance_km), value: fn () => round(num: (float) $this->distance_km, precision: 3)),
            'phones' => $this->whenLoaded(relationship: 'phones', value: fn () => $this->phones->pluck('phone')->all()),
            'activities' => $this->whenLoaded(relationship: 'activities', value: fn () => $this->activities->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'level' => $a->level,
            ])->all()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
