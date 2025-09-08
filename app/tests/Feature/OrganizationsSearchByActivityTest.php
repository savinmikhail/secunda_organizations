<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationsSearchByActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_by_parent_activity_includes_children(): void
    {
        $food = Activity::factory()->create(attributes: ['name' => 'Еда', 'level' => 1]);
        $meat = Activity::factory()->create(attributes: ['name' => 'Мясная продукция', 'level' => 2, 'parent_id' => $food->id]);
        $dairy = Activity::factory()->create(attributes: ['name' => 'Молочная продукция', 'level' => 2, 'parent_id' => $food->id]);

        $org1 = Organization::factory()->create(attributes: ['name' => 'Мясная лавка']);
        $org2 = Organization::factory()->create(attributes: ['name' => 'Молочный дом']);
        Organization::factory()->create(attributes: ['name' => 'Без еды']);

        $org1->activities()->attach($meat->id);
        $org2->activities()->attach($dairy->id);

        $res = $this->getJson(route(name: 'organizations.search.activity', parameters: ['q' => 'Еда']));

        $res->assertOk()
            ->assertJsonFragment(data: ['name' => 'Мясная лавка'])
            ->assertJsonFragment(data: ['name' => 'Молочный дом'])
            ->assertJsonMissing(data: ['name' => 'Без еды']);
    }

    public function test_returns_empty_when_no_activity_matches(): void
    {
        $res = $this->getJson(route(name: 'organizations.search.activity', parameters: ['q' => 'Nonexistent']));
        $res->assertOk()->assertJsonCount(count: 0, key: 'data');
    }
}
