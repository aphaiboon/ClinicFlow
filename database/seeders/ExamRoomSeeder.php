<?php

namespace Database\Seeders;

use App\Models\ExamRoom;
use Illuminate\Database\Seeder;

class ExamRoomSeeder extends Seeder
{
    public function run(): void
    {
        ExamRoom::factory()->count(10)->create();
        
        ExamRoom::factory()->count(2)->inactive()->create();
    }
}
