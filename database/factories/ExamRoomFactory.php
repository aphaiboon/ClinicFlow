<?php

namespace Database\Factories;

use App\Models\ExamRoom;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamRoomFactory extends Factory
{
    protected $model = ExamRoom::class;

    public function definition(): array
    {
        $equipmentOptions = [
            ['stethoscope', 'blood_pressure_monitor', 'thermometer'],
            ['stethoscope', 'otoscope', 'ophthalmoscope'],
            ['stethoscope', 'scale', 'height_measure'],
            ['stethoscope', 'glucometer', 'pulse_oximeter'],
        ];

        return [
            'organization_id' => Organization::factory(),
            'room_number' => 'R'.fake()->unique()->numerify('###'),
            'name' => 'Exam Room '.fake()->numberBetween(1, 50),
            'floor' => fake()->numberBetween(1, 3),
            'equipment' => fake()->randomElement($equipmentOptions),
            'capacity' => fake()->numberBetween(1, 3),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
