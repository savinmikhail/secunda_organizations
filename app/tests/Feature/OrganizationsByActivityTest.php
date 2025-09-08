<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationsByActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_organizations_for_activity_and_descendants(): void
    {
        $food = Activity::factory()->create(attributes: ['name' => 'Еда', 'level' => 1, 'parent_id' => null]);
        $meat = Activity::factory()->create(attributes: ['name' => 'Мясная продукция', 'level' => 2, 'parent_id' => $food->id]);
        $dairy = Activity::factory()->create(attributes: ['name' => 'Молочная продукция', 'level' => 2, 'parent_id' => $food->id]);

        $org1 = Organization::factory()->create(attributes: ['name' => 'Мясная лавка']);
        $org2 = Organization::factory()->create(attributes: ['name' => 'Молочный дом']);

        $org1->activities()->attach($meat->id);
        $org2->activities()->attach($dairy->id);

        $response = $this->getJson(route(name: 'activities.organizations.index', parameters: ['activity' => $food->id]));

        $response->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonFragment(data: ['name' => 'Мясная лавка'])
            ->assertJsonFragment(data: ['name' => 'Молочный дом']);
    }

    public function test_paginates_results(): void
    {
        $activity = Activity::factory()->create(attributes: ['name' => 'Еда', 'level' => 1, 'parent_id' => null]);

        Organization::factory()->count(count: 30)->create()->each(function ($org) use ($activity) {
            $org->activities()->attach($activity->id);
        });

        $response = $this->getJson(route(name: 'activities.organizations.index', parameters: ['activity' => $activity->id, 'per_page' => 10]));

        $response->assertOk()
            ->assertJsonCount(count: 10, key: 'data')
            ->assertJsonPath(path: 'meta.total', expect: 30)
            ->assertJsonPath(path: 'meta.per_page', expect: 10);
    }

    public function test_returns_404_for_nonexistent_activity(): void
    {
        $response = $this->getJson(route(name: 'activities.organizations.index', parameters: ['activity' => 999999]));
        $response->assertStatus(status: 404);
    }
}
