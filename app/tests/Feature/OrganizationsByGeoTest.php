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
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL-only geo test');
        }
        $b1 = Building::factory()->create(['latitude' => 55.0000, 'longitude' => 37.0000]);
        $b2 = Building::factory()->create(['latitude' => 55.0100, 'longitude' => 37.0100]);
        $b3 = Building::factory()->create(['latitude' => 55.1000, 'longitude' => 37.1000]);

        $o1 = Organization::factory()->create(['name' => 'Center', 'building_id' => $b1->id]);
        $o2 = Organization::factory()->create(['name' => 'Near', 'building_id' => $b2->id]);
        $o3 = Organization::factory()->create(['name' => 'Far', 'building_id' => $b3->id]);

        $res = $this->getJson(route('organizations.geo', [
            'lat1' => 54.99, 'lng1' => 36.99,
            'lat2' => 55.02, 'lng2' => 37.02,
        ]));

        $res->assertOk()
            ->assertJsonFragment(['name' => 'Center'])
            ->assertJsonFragment(['name' => 'Near'])
            ->assertJsonMissing(['name' => 'Far']);
    }

    public function test_radius_filters_organizations(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL-only geo test');
        }
        $centerLat = 55.0000; $centerLng = 37.0000;
        $b1 = Building::factory()->create(['latitude' => $centerLat, 'longitude' => $centerLng]);
        // ~1.11 km north
        $b2 = Building::factory()->create(['latitude' => 55.0100, 'longitude' => 37.0000]);
        // ~5.55 km north
        $b3 = Building::factory()->create(['latitude' => 55.0500, 'longitude' => 37.0000]);

        $o1 = Organization::factory()->create(['name' => 'Center', 'building_id' => $b1->id]);
        $o2 = Organization::factory()->create(['name' => 'Near', 'building_id' => $b2->id]);
        $o3 = Organization::factory()->create(['name' => 'Far', 'building_id' => $b3->id]);

        $res = $this->getJson(route('organizations.geo', [
            'lat' => $centerLat,
            'lng' => $centerLng,
            'radius_km' => 2,
        ]));

        $res->assertOk()
            ->assertJsonFragment(['name' => 'Center'])
            ->assertJsonFragment(['name' => 'Near'])
            ->assertJsonMissing(['name' => 'Far']);
    }
}
