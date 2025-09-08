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
        Building::factory()->create(['address' => 'A street']);
        Building::factory()->create(['address' => 'B street']);

        $res = $this->getJson(route('buildings.index'));

        $res->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['address' => 'A street'])
            ->assertJsonFragment(['address' => 'B street']);
    }

    public function test_paginates_buildings(): void
    {
        Building::factory()->count(25)->create();

        $res = $this->getJson(route('buildings.index', ['per_page' => 10]));

        $res->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 10);
    }
}

