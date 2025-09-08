<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationsSearchByNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_by_name_returns_matches(): void
    {
        Organization::factory()->create(['name' => 'Shop Alpha']);
        Organization::factory()->create(['name' => 'Beta Shop']);
        Organization::factory()->create(['name' => 'Gamma Cafe']);

        $res = $this->getJson(route('organizations.search.name', ['q' => 'shop']));

        $res->assertOk()
            ->assertJsonFragment(['name' => 'Shop Alpha'])
            ->assertJsonFragment(['name' => 'Beta Shop'])
            ->assertJsonMissing(['name' => 'Gamma Cafe']);
    }

    public function test_paginates_results(): void
    {
        Organization::factory()->count(25)->create(['name' => 'Acme']);

        $res = $this->getJson(route('organizations.search.name', ['q' => 'ac', 'per_page' => 10]));

        $res->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_requires_q(): void
    {
        $this->getJson(route('organizations.search.name'))
            ->assertStatus(422);
    }
}
