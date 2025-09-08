<?php

namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLevelLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_up_to_three_levels(): void
    {
        $l1 = Activity::create(['name' => 'Level1']);
        $l2 = Activity::create(['name' => 'Level2', 'parent_id' => $l1->id]);
        $l3 = Activity::create(['name' => 'Level3', 'parent_id' => $l2->id]);

        $this->assertSame(1, $l1->level);
        $this->assertSame(2, $l2->level);
        $this->assertSame(3, $l3->level);
    }

    public function test_prevents_fourth_level(): void
    {
        $this->expectException(\DomainException::class);
        $l1 = Activity::create(['name' => 'Level1']);
        $l2 = Activity::create(['name' => 'Level2', 'parent_id' => $l1->id]);
        $l3 = Activity::create(['name' => 'Level3', 'parent_id' => $l2->id]);
        Activity::create(['name' => 'Level4', 'parent_id' => $l3->id]);
    }
}

