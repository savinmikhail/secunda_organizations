<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationsByGeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_rectangle_filters_organizations(): void
    {
        $b1 = Building::factory()->create(attributes: ['latitude' => 55.0000, 'longitude' => 37.0000]);
        $b2 = Building::factory()->create(attributes: ['latitude' => 55.0100, 'longitude' => 37.0100]);
        $b3 = Building::factory()->create(attributes: ['latitude' => 55.1000, 'longitude' => 37.1000]);

        Organization::factory()->create(attributes: ['name' => 'Center', 'building_id' => $b1->id]);
        Organization::factory()->create(attributes: ['name' => 'Near', 'building_id' => $b2->id]);
        Organization::factory()->create(attributes: ['name' => 'Far', 'building_id' => $b3->id]);

        $res = $this->getJson(route(name: 'organizations.geo', parameters: [
            'lat1' => 54.99, 'lng1' => 36.99,
            'lat2' => 55.02, 'lng2' => 37.02,
        ]));

        $res->assertOk()
            ->assertJsonFragment(data: ['name' => 'Center'])
            ->assertJsonFragment(data: ['name' => 'Near'])
            ->assertJsonMissing(data: ['name' => 'Far']);
    }

    public function test_radius_filters_organizations(): void
    {
        $centerLat = 55.0000;
        $centerLng = 37.0000;
        $b1 = Building::factory()->create(attributes: ['latitude' => $centerLat, 'longitude' => $centerLng]);
        // ~1.11 km north
        $b2 = Building::factory()->create(attributes: ['latitude' => 55.0100, 'longitude' => 37.0000]);
        // ~5.55 km north
        $b3 = Building::factory()->create(attributes: ['latitude' => 55.0500, 'longitude' => 37.0000]);

        Organization::factory()->create(attributes: ['name' => 'Center', 'building_id' => $b1->id]);
        Organization::factory()->create(attributes: ['name' => 'Near', 'building_id' => $b2->id]);
        Organization::factory()->create(attributes: ['name' => 'Far', 'building_id' => $b3->id]);

        $res = $this->getJson(route(name: 'organizations.geo', parameters: [
            'lat' => $centerLat,
            'lng' => $centerLng,
            'radius_km' => 2,
        ]));

        $res->assertOk()
            ->assertJsonFragment(data: ['name' => 'Center'])
            ->assertJsonFragment(data: ['name' => 'Near'])
            ->assertJsonMissing(data: ['name' => 'Far']);
    }
}
