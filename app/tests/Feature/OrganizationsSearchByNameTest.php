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
        Organization::factory()->create(attributes: ['name' => 'Shop Alpha']);
        Organization::factory()->create(attributes: ['name' => 'Beta Shop']);
        Organization::factory()->create(attributes: ['name' => 'Gamma Cafe']);

        $res = $this->getJson(route(name: 'organizations.search.name', parameters: ['q' => 'shop']));

        $res->assertOk()
            ->assertJsonFragment(data: ['name' => 'Shop Alpha'])
            ->assertJsonFragment(data: ['name' => 'Beta Shop'])
            ->assertJsonMissing(data: ['name' => 'Gamma Cafe']);
    }

    public function test_paginates_results(): void
    {
        Organization::factory()->count(count: 25)->create(attributes: ['name' => 'Acme']);

        $res = $this->getJson(route(name: 'organizations.search.name', parameters: ['q' => 'ac', 'per_page' => 10]));

        $res->assertOk()
            ->assertJsonCount(count: 10, key: 'data')
            ->assertJsonPath(path: 'meta.total', expect: 25)
            ->assertJsonPath(path: 'meta.per_page', expect: 10);
    }

    public function test_requires_q(): void
    {
        $this->getJson(route(name: 'organizations.search.name'))
            ->assertStatus(status: 422);
    }
}
