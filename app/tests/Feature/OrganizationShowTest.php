<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Building;
use App\Models\Organization;
use App\Models\OrganizationPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_organization_details(): void
    {
        $building = Building::factory()->create();
        $activity = Activity::factory()->create(['name' => 'Еда', 'level' => 1]);
        $org = Organization::factory()->create([
            'name' => 'Test Org',
            'building_id' => $building->id,
        ]);

        $org->activities()->attach($activity->id);
        OrganizationPhone::create(['organization_id' => $org->id, 'phone' => '8-900-000-00-01']);
        OrganizationPhone::create(['organization_id' => $org->id, 'phone' => '8-900-000-00-02']);

        $res = $this->getJson(route('organizations.show', ['organization' => $org->id]));

        $res->assertOk()
            ->assertJsonPath('data.id', $org->id)
            ->assertJsonPath('data.name', 'Test Org')
            ->assertJsonPath('data.building_id', $building->id)
            ->assertJsonPath('data.phones', ['8-900-000-00-01', '8-900-000-00-02'])
            ->assertJsonFragment(['name' => 'Еда']);
    }

    public function test_returns_404_for_nonexistent(): void
    {
        $this->getJson(route('organizations.show', ['organization' => 999999]))
            ->assertStatus(404);
    }
}
