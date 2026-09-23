<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PartnerTypeSeeder::class,
            PartnerSeeder::class,
            AccessAreaSeeder::class,
            QuestionCategorySeeder::class,
            QuestionLvSeeder::class,
            QuestionRambuSeeder::class,
            QuestionDtSeeder::class,
            QuestionExcaSeeder::class,
            QuestionBulldozerSeeder::class,
            QuestionMotorGraderSeeder::class,
            SimperCategorySeeder::class,
        ]);
    }
}
