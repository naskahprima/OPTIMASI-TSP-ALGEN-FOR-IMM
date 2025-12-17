<?php

namespace Database\Factories;

use App\Models\RuteOptimal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RuteOptimal>
 */
class RuteOptimalFactory extends Factory
{
    protected $model = RuteOptimal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate random route (array of destination IDs)
        $routeSize = fake()->numberBetween(3, 8);
        $route = range(1, $routeSize);
        shuffle($route);
        
        return [
            'route' => json_encode($route),
            'total_distance' => fake()->randomFloat(2, 5, 100), // in km
            'date_time' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Route with specific distance
     */
    public function withDistance(float $distance): static
    {
        return $this->state(fn (array $attributes) => [
            'total_distance' => $distance,
        ]);
    }

    /**
     * Route with specific IDs
     */
    public function withRoute(array $destinationIds): static
    {
        return $this->state(fn (array $attributes) => [
            'route' => json_encode($destinationIds),
        ]);
    }

    /**
     * Recent route (created today)
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_time' => now(),
        ]);
    }

    /**
     * Old route (created > 30 days ago)
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_time' => now()->subDays(fake()->numberBetween(31, 90)),
        ]);
    }
}
