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

        $orgA = Organization::factory()->create(['name' => 'Org A', 'building_id' => $building->id]);
        $orgB = Organization::factory()->create(['name' => 'Org B', 'building_id' => $building->id]);
        Organization::factory()->create(['name' => 'Other Org', 'building_id' => $other->id]);

        $res = $this->getJson(route('buildings.organizations.index', ['building' => $building->id]));

        $res->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Org A'])
            ->assertJsonFragment(['name' => 'Org B'])
            ->assertJsonMissing(['name' => 'Other Org']);
    }

    public function test_paginates_results(): void
    {
        $building = Building::factory()->create();
        Organization::factory()->count(25)->create(['building_id' => $building->id]);

        $res = $this->getJson(route('buildings.organizations.index', ['building' => $building->id, 'per_page' => 10]));

        $res->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_nonexistent_building_returns_404(): void
    {
        $res = $this->getJson(route('buildings.organizations.index', ['building' => 999999]));
        $res->assertStatus(404);
    }

    public function test_existing_building_with_no_orgs_returns_empty_list(): void
    {
        $building = Building::factory()->create();
        $res = $this->getJson(route('buildings.organizations.index', ['building' => $building->id]));
        $res->assertOk()->assertJsonCount(0, 'data');
    }
}
