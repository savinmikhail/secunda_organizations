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
        $food = Activity::factory()->create(['name' => 'Еда', 'level' => 1, 'parent_id' => null]);
        $meat = Activity::factory()->create(['name' => 'Мясная продукция', 'level' => 2, 'parent_id' => $food->id]);
        $dairy = Activity::factory()->create(['name' => 'Молочная продукция', 'level' => 2, 'parent_id' => $food->id]);

        $org1 = Organization::factory()->create(['name' => 'Мясная лавка']);
        $org2 = Organization::factory()->create(['name' => 'Молочный дом']);

        $org1->activities()->attach($meat->id);
        $org2->activities()->attach($dairy->id);

        $response = $this->getJson(route('activities.organizations.index', ['activity' => $food->id]));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Мясная лавка'])
            ->assertJsonFragment(['name' => 'Молочный дом']);
    }

    public function test_paginates_results(): void
    {
        $activity = Activity::factory()->create(['name' => 'Еда', 'level' => 1, 'parent_id' => null]);

        Organization::factory()->count(30)->create()->each(function ($org) use ($activity) {
            $org->activities()->attach($activity->id);
        });

        $response = $this->getJson(route('activities.organizations.index', ['activity' => $activity->id, 'per_page' => 10]));

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 30)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_returns_404_for_nonexistent_activity(): void
    {
        $response = $this->getJson(route('activities.organizations.index', ['activity' => 999999]));
        $response->assertStatus(404);
    }
}
