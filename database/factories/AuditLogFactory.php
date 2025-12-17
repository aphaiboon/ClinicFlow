<?php

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(AuditAction::cases()),
            'resource_type' => fake()->randomElement(['Patient', 'Appointment', 'ExamRoom']),
            'resource_id' => fake()->numberBetween(1, 100),
            'changes' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => null,
            'created_at' => now(),
        ];
    }

    public function withChanges(array $before, array $after): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => AuditAction::Update,
            'changes' => [
                'before' => $before,
                'after' => $after,
            ],
        ]);
    }
}
