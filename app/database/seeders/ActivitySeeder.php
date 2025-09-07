<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $food = Activity::firstOrCreate(
            ['name' => 'Еда'],
            ['level' => 1, 'parent_id' => null]
        );
        $cars = Activity::firstOrCreate(
            ['name' => 'Автомобили'],
            ['level' => 1, 'parent_id' => null]
        );

        $meat = Activity::firstOrCreate(
            ['name' => 'Мясная продукция'],
            ['level' => 2, 'parent_id' => $food->id]
        );
        $dairy = Activity::firstOrCreate(
            ['name' => 'Молочная продукция'],
            ['level' => 2, 'parent_id' => $food->id]
        );

        $trucks = Activity::firstOrCreate(
            ['name' => 'Грузовые'],
            ['level' => 2, 'parent_id' => $cars->id]
        );
        $passenger = Activity::firstOrCreate(
            ['name' => 'Легковые'],
            ['level' => 2, 'parent_id' => $cars->id]
        );

        Activity::firstOrCreate(
            ['name' => 'Запчасти'],
            ['level' => 3, 'parent_id' => $passenger->id]
        );
        Activity::firstOrCreate(
            ['name' => 'Аксессуары'],
            ['level' => 3, 'parent_id' => $passenger->id]
        );
    }
}
