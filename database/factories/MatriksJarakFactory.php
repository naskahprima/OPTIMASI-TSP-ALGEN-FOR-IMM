<?php

namespace Database\Factories;

use App\Models\MatriksJarak;
use App\Models\Destinasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MatriksJarak>
 */
class MatriksJarakFactory extends Factory
{
    protected $model = MatriksJarak::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'origin_id' => Destinasi::factory(),
            'destination_id' => Destinasi::factory(),
            'distance' => fake()->randomFloat(2, 100, 50000), // 100m - 50km in meters
        ];
    }

    /**
     * Jarak pendek (< 5km)
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'distance' => fake()->randomFloat(2, 100, 5000),
        ]);
    }

    /**
     * Jarak sedang (5-20km)
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'distance' => fake()->randomFloat(2, 5000, 20000),
        ]);
    }

    /**
     * Jarak jauh (> 20km)
     */
    public function long(): static
    {
        return $this->state(fn (array $attributes) => [
            'distance' => fake()->randomFloat(2, 20000, 100000),
        ]);
    }

    /**
     * Between specific destinations
     */
    public function between(int $originId, int $destinationId): static
    {
        return $this->state(fn (array $attributes) => [
            'origin_id' => $originId,
            'destination_id' => $destinationId,
        ]);
    }
}
