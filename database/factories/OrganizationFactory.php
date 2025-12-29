<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company().' Clinic';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address_line_1' => fake()->optional()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->stateAbbr(),
            'postal_code' => fake()->optional()->postcode(),
            'country' => 'US',
            'tax_id' => fake()->optional()->regexify('[0-9]{2}-[0-9]{7}'),
            'npi_number' => fake()->optional()->numerify('##########'),
            'practice_type' => fake()->optional()->randomElement(['primary_care', 'specialty', 'urgent_care', 'surgical']),
            'license_number' => fake()->optional()->regexify('[A-Z]{2}-[0-9]{6}'),
            'is_active' => true,
            'operating_hours_start' => '08:00:00',
            'operating_hours_end' => '18:00:00',
            'default_time_slot_interval' => 15,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
