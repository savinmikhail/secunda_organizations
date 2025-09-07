<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Building;
use App\Models\Organization;
use App\Models\OrganizationPhone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Random\RandomException;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws RandomException
     */
    public function run(): void
    {
        // Create buildings and organizations with phone numbers
        $buildings = Building::factory()->count(10)->create();

        $organizations = Organization::factory()->count(30)->create();

        $faker = \Faker\Factory::create();

        $activities = Activity::all();

        foreach ($organizations as $org) {
            $phonesCount = random_int(1, 3);
            for ($i = 0; $i < $phonesCount; $i++) {
                OrganizationPhone::create([
                    'organization_id' => $org->id,
                    'phone' => $faker->numerify('8-###-###-##-##'),
                ]);
            }

            if ($activities->isNotEmpty()) {
                $attach = $activities->random(random_int(1, min(3, $activities->count())));
                $org->activities()->syncWithoutDetaching(collect($attach)->pluck('id')->all());
            }
        }
    }
}
