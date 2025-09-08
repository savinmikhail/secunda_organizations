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
        $food = Activity::factory()->create(['name' => 'Еда', 'level' => 1]);
        $meat = Activity::factory()->create(['name' => 'Мясная продукция', 'level' => 2, 'parent_id' => $food->id]);
        $dairy = Activity::factory()->create(['name' => 'Молочная продукция', 'level' => 2, 'parent_id' => $food->id]);

        $org1 = Organization::factory()->create(['name' => 'Мясная лавка']);
        $org2 = Organization::factory()->create(['name' => 'Молочный дом']);
        $org3 = Organization::factory()->create(['name' => 'Без еды']);

        $org1->activities()->attach($meat->id);
        $org2->activities()->attach($dairy->id);

        $res = $this->getJson(route('organizations.search.activity', ['q' => 'Еда']));

        $res->assertOk()
            ->assertJsonFragment(['name' => 'Мясная лавка'])
            ->assertJsonFragment(['name' => 'Молочный дом'])
            ->assertJsonMissing(['name' => 'Без еды']);
    }

    public function test_returns_empty_when_no_activity_matches(): void
    {
        $res = $this->getJson(route('organizations.search.activity', ['q' => 'Nonexistent']));
        $res->assertOk()->assertJsonCount(0, 'data');
    }
}
