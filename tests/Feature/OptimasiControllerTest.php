<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Destinasi;
use App\Models\MatriksJarak;
use App\Models\RuteOptimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class OptimasiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // ✅ FIXED: Create admin user (required for role:admin middleware!)
        $this->user = User::factory()->create([
            'role' => 'admin'  // Set role = admin
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
        $ruteOptimal = RuteOptimal::factory()->count(3)->create();

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
    /** @test */
/** @test */
public function it_can_generate_optimal_route_with_valid_parameters()
{
    // ✅ FIXED: Buat destinasi dulu
    $destinations = Destinasi::factory()->count(5)->create();
    
    // ✅ FIXED: Buat matriks jarak yang LENGKAP (semua kombinasi)
    foreach ($destinations as $origin) {
        foreach ($destinations as $destination) {
            if ($origin->id !== $destination->id) {
                MatriksJarak::create([
                    'origin_id' => $origin->id,
                    'destination_id' => $destination->id,
                    'distance' => rand(1000, 50000), // Random 1-50 km
                ]);
            }
        }
    }

    $startingPoint = $destinations->first();

    $response = $this->actingAs($this->user)
        ->post(route('optimasi.generate.store'), [
            'kromosom' => 50,
            'max_gen' => 100,
            'titik_awal' => $startingPoint->id,
            'crossover_rate' => 0.8,
            'mutation_rate' => 0.1,
        ]);

    $response->assertStatus(200);
    $response->assertViewIs('optimasi.generate');
    $response->assertViewHas('result');
}

/** @test */
public function it_requires_minimum_two_destinations()
{
    // Buat 1 destinasi yang valid
    $destination = Destinasi::factory()->create();

    $response = $this->actingAs($this->user)
        ->post(route('optimasi.generate.store'), [
            'kromosom' => 50,
            'max_gen' => 100,
            'titik_awal' => $destination->id, // ✅ ID valid
            'crossover_rate' => 0.8,
            'mutation_rate' => 0.1,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    
    // ✅ Assert pesan error yang tepat
    $this->assertStringContainsString(
        'Minimal 2 destinasi',
        session('error')
    );
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
                'kromosom' => 0, // Invalid (< 1)
                'max_gen' => 100,
                'titik_awal' => 1,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.1,
            ]);

        $response->assertSessionHasErrors('kromosom');

        // Too large
        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 1001, // Invalid (> 1000)
                'max_gen' => 100,
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
                'kromosom' => 50,
                'max_gen' => 10001, // Invalid (> 10000)
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
                'kromosom' => 50,
                'max_gen' => 100,
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
                'kromosom' => 50,
                'max_gen' => 100,
                'titik_awal' => 1,
                'crossover_rate' => 0.8,
                'mutation_rate' => -0.1, // Invalid (< 0)
            ]);

        $response->assertSessionHasErrors('mutation_rate');
    }


    /** @test */
    public function it_can_save_optimal_route()
    {
        $route = [1, 2, 3, 4, 1];
        $distance = 15.5;

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
        $destinations = Destinasi::factory()->count(5)->create();
        $route = $destinations->pluck('id')->toArray();
        
        $ruteOptimal = RuteOptimal::factory()->create([
            'route' => json_encode($route),
            'total_distance' => 20.5,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('optimasi.show') . '?id=' . $ruteOptimal->id);

        $response->assertStatus(200);
        $response->assertViewIs('optimasi.show');
        $response->assertViewHas('data');
        $response->assertViewHas('destinasi');
    }

    /** @test */
    public function it_returns_404_when_viewing_non_existent_route()
    {
        $response = $this->actingAs($this->user)
            ->get(route('optimasi.show') . '?id=9999');

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
        $destinations = Destinasi::factory()->count(5)->create();
        $startingPoint = $destinations->first()->id;
        
        // Create complete distance matrix
        foreach ($destinations as $origin) {
            foreach ($destinations as $destination) {
                if ($origin->id !== $destination->id) {
                    MatriksJarak::factory()->create([
                        'origin_id' => $origin->id,
                        'destination_id' => $destination->id,
                        'distance' => rand(1000, 5000),
                    ]);
                }
            }
        }

        $response = $this->actingAs($this->user)
            ->post(route('optimasi.generate.store'), [
                'kromosom' => 50,
                'max_gen' => 100,
                'titik_awal' => $startingPoint,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.1,
            ]);

        $response->assertStatus(200);
        $result = $response->viewData('result');
        $chromosome = $result['chromosome'];

        // First and last should be starting point
        $this->assertEquals($startingPoint, $chromosome[0]);
        $this->assertEquals($startingPoint, $chromosome[count($chromosome) - 1]);
    }

    /** @test */
    public function guest_cannot_access_optimasi_pages()
    {
        $response = $this->get(route('optimasi'));
        $response->assertRedirect(route('login'));
    }
}