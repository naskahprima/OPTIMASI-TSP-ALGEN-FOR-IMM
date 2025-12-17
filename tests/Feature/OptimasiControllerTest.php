<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Destinasi;
use App\Models\RuteOptimal;
use App\Models\MatriksJarak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OptimasiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user with full permissions
        $this->user = User::factory()->create([
            'is_admin' => true, // Kalau pakai kolom is_admin
            // ATAU
            'role' => 'admin', // Kalau pakai kolom role
        ]);
        
        // Fake storage
        Storage::fake('public');
        
        // Mock OSRM API
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => [
                    ['distance' => 5000, 'duration' => 300]
                ]
            ], 200),
        ]);
    }

    /** @test */
    public function it_displays_optimasi_index_page()
    {
        RuteOptimal::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('optimasi'));

        $response->assertStatus(200);
        $response->assertViewIs('optimasi.index');
        $response->assertViewHas('ruteOptimal');
    }

    /** @test */
    public function it_displays_generate_form()
    {
        Destinasi::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('optimasi.generate'));

        $response->assertStatus(200);
        $response->assertViewIs('optimasi.generate');
        $response->assertViewHas('destinasi');
        $response->assertViewHas('totalDestinasi', 5);
    }

    /** @test */
    public function it_can_generate_optimal_route_with_valid_parameters()
    {
        // Create 5 destinasi
        $destinasiList = Destinasi::factory()->count(5)->create();
        
        // Create distance matrix
        foreach ($destinasiList as $origin) {
            foreach ($destinasiList as $destination) {
                if ($origin->id !== $destination->id) {
                    MatriksJarak::factory()->create([
                        'origin_id' => $origin->id,
                        'destination_id' => $destination->id,
                        'distance' => rand(1000, 10000),
                    ]);
                }
            }
        }

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 10,
                'max_gen' => 10,
                'titik_awal' => $destinasiList->first()->id,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.1,
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('optimasi.generate');
        $response->assertViewHas('result');
        $response->assertSessionHas('success');

        // Verify result structure
        $result = $response->viewData('result');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('chromosome', $result);
        $this->assertArrayHasKey('distance_km', $result);
        $this->assertArrayHasKey('fitness', $result);
        $this->assertArrayHasKey('execution_time', $result);
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), []);

        $response->assertSessionHasErrors([
            'kromosom',
            'max_gen',
            'titik_awal',
            'crossover_rate',
            'mutation_rate',
        ]);
    }

    /** @test */
    public function it_validates_kromosom_range()
    {
        Destinasi::factory()->count(3)->create();

        // Too small
        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 0,
                'max_gen' => 10,
                'titik_awal' => 1,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.1,
            ]);

        $response->assertSessionHasErrors('kromosom');

        // Too large
        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 1001,
                'max_gen' => 10,
                'titik_awal' => 1,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.1,
            ]);

        $response->assertSessionHasErrors('kromosom');
    }

    /** @test */
    public function it_validates_max_generation_range()
    {
        Destinasi::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 10,
                'max_gen' => 0, // Invalid
                'titik_awal' => 1,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.1,
            ]);

        $response->assertSessionHasErrors('max_gen');
    }

    /** @test */
    public function it_validates_crossover_rate_range()
    {
        Destinasi::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 10,
                'max_gen' => 10,
                'titik_awal' => 1,
                'crossover_rate' => 1.5, // Invalid (> 1)
                'mutation_rate' => 0.1,
            ]);

        $response->assertSessionHasErrors('crossover_rate');
    }

    /** @test */
    public function it_validates_mutation_rate_range()
    {
        Destinasi::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 10,
                'max_gen' => 10,
                'titik_awal' => 1,
                'crossover_rate' => 0.8,
                'mutation_rate' => -0.1, // Invalid (< 0)
            ]);

        $response->assertSessionHasErrors('mutation_rate');
    }

    /** @test */
    public function it_requires_minimum_two_destinations()
    {
        // Create only 1 destinasi
        Destinasi::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 10,
                'max_gen' => 10,
                'titik_awal' => 1,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function it_can_save_optimal_route()
    {
        $route = [1, 2, 3, 4, 5];
        $distance = 25.5;

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.store'), [
                'solusi' => json_encode($route),
                'jarak' => $distance,
            ]);

        $response->assertRedirect(route('optimasi'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rute_optimals', [
            'route' => json_encode($route),
            'total_distance' => $distance,
        ]);
    }

    /** @test */
    public function it_can_display_saved_route()
    {
        $destinasi = Destinasi::factory()->count(5)->create();
        $route = $destinasi->pluck('id')->toArray();
        
        $ruteOptimal = RuteOptimal::factory()->create([
            'route' => json_encode($route),
            'total_distance' => 25.5,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.show'), [
                'id' => $ruteOptimal->id,
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('optimasi.show');
        $response->assertViewHas('data');
        $response->assertViewHas('destinasi');
    }

    /** @test */
    public function it_returns_404_when_viewing_non_existent_route()
    {
        $response = $this->actingAs($this->user)
            ->post(route('optimasi.show'), [
                'id' => 9999,
            ]);

        $response->assertRedirect(route('optimasi'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function it_can_delete_saved_route()
    {
        $ruteOptimal = RuteOptimal::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.destroy'), [
                'id' => $ruteOptimal->id,
            ]);

        $response->assertRedirect(route('optimasi'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('rute_optimals', [
            'id' => $ruteOptimal->id,
        ]);
    }

    /** @test */
    public function it_returns_error_when_deleting_non_existent_route()
    {
        $response = $this->actingAs($this->user)
            ->post(route('optimasi.destroy'), [
                'id' => 9999,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function generated_route_starts_and_ends_with_starting_point()
    {
        $destinasiList = Destinasi::factory()->count(5)->create();
        
        foreach ($destinasiList as $origin) {
            foreach ($destinasiList as $destination) {
                if ($origin->id !== $destination->id) {
                    MatriksJarak::factory()->create([
                        'origin_id' => $origin->id,
                        'destination_id' => $destination->id,
                    ]);
                }
            }
        }

        $startingPoint = $destinasiList->first()->id;

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 10,
                'max_gen' => 10,
                'titik_awal' => $startingPoint,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.1,
            ]);

        $result = $response->viewData('result');
        $chromosome = $result['chromosome'];

        // First and last should be starting point
        $this->assertEquals($startingPoint, $chromosome[0]);
        $this->assertEquals($startingPoint, end($chromosome));
    }

    /** @test */
    public function guest_cannot_access_optimasi_pages()
    {
        $response = $this->get(route('optimasi'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('optimasi.generate'));
        $response->assertRedirect(route('login'));
    }
}
