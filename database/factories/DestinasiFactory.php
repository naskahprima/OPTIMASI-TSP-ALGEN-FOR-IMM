<?php

namespace Database\Factories;

use App\Models\Destinasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Destinasi>
 */
class DestinasiFactory extends Factory
{
    protected $model = Destinasi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;
        
        return [
            'destination_code' => 'D' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'name' => fake()->words(3, true) . ' ' . fake()->randomElement(['Mosque', 'Mall', 'Park', 'Museum', 'Beach']),
            'description' => fake()->paragraph(),
            'lat' => fake()->latitude(-1, 1), // Sekitar Pontianak
            'lng' => fake()->longitude(108, 110), // Sekitar Pontianak
            'img' => 'test_image_' . fake()->uuid() . '.jpg',
        ];
    }

    /**
     * Destinasi di Pontianak (koordinat real)
     */
    public function pontianak(): static
    {
        return $this->state(fn (array $attributes) => [
            'lat' => fake()->latitude(-0.1, 0.1),
            'lng' => fake()->longitude(109.2, 109.4),
        ]);
    }

    /**
     * Destinasi tanpa gambar
     */
    public function withoutImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'img' => null,
        ]);
    }

    /**
     * Destinasi dengan kode spesifik
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'destination_code' => $code,
        ]);
    }

    /**
     * Masjid Mujahidin (example real data)
     */
    public function masjidMujahidin(): static
    {
        return $this->state(fn (array $attributes) => [
            'destination_code' => 'D001',
            'name' => 'Masjid Mujahidin',
            'description' => 'Masjid bersejarah di Pontianak',
            'lat' => -0.033271,
            'lng' => 109.333557,
        ]);
    }
}
