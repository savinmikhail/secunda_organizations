<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationsByBuildingTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_organizations_in_building(): void
    {
        $building = Building::factory()->create();
        $other = Building::factory()->create();

        Organization::factory()->create(attributes: ['name' => 'Org A', 'building_id' => $building->id]);
        Organization::factory()->create(attributes: ['name' => 'Org B', 'building_id' => $building->id]);
        Organization::factory()->create(attributes: ['name' => 'Other Org', 'building_id' => $other->id]);

        $res = $this->getJson(route(name: 'buildings.organizations.index', parameters: ['building' => $building->id]));

        $res->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonFragment(data: ['name' => 'Org A'])
            ->assertJsonFragment(data: ['name' => 'Org B'])
            ->assertJsonMissing(data: ['name' => 'Other Org']);
    }

    public function test_paginates_results(): void
    {
        $building = Building::factory()->create();
        Organization::factory()->count(count: 25)->create(attributes: ['building_id' => $building->id]);

        $res = $this->getJson(route(name: 'buildings.organizations.index', parameters: ['building' => $building->id, 'per_page' => 10]));

        $res->assertOk()
            ->assertJsonCount(count: 10, key: 'data')
            ->assertJsonPath(path: 'meta.total', expect: 25)
            ->assertJsonPath(path: 'meta.per_page', expect: 10);
    }

    public function test_nonexistent_building_returns_404(): void
    {
        $res = $this->getJson(route(name: 'buildings.organizations.index', parameters: ['building' => 999999]));
        $res->assertStatus(status: 404);
    }

    public function test_existing_building_with_no_orgs_returns_empty_list(): void
    {
        $building = Building::factory()->create();
        $res = $this->getJson(route(name: 'buildings.organizations.index', parameters: ['building' => $building->id]));
        $res->assertOk()->assertJsonCount(count: 0, key: 'data');
    }
}
