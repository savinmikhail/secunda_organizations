<?php

namespace Tests\Feature;

use App\Models\Building;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_buildings(): void
    {
        Building::factory()->create(attributes: ['address' => 'A street']);
        Building::factory()->create(attributes: ['address' => 'B street']);

        $res = $this->getJson(route(name: 'buildings.index'));

        $res->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonFragment(data: ['address' => 'A street'])
            ->assertJsonFragment(data: ['address' => 'B street']);
    }

    public function test_paginates_buildings(): void
    {
        Building::factory()->count(count: 25)->create();

        $res = $this->getJson(route(name: 'buildings.index', parameters: ['per_page' => 10]));

        $res->assertOk()
            ->assertJsonCount(count: 10, key: 'data')
            ->assertJsonPath(path: 'meta.total', expect: 25)
            ->assertJsonPath(path: 'meta.per_page', expect: 10);
    }
}
