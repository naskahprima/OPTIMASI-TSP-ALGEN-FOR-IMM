<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Destinasi;
use App\Models\MatriksJarak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class DestinasiControllerTest extends TestCase
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
    public function it_displays_destinasi_index_page()
    {
        Destinasi::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('destinasi.index'));

        $response->assertStatus(200);
        $response->assertViewIs('destinasi.index');
        $response->assertViewHas('destinasi');
        $response->assertViewHas('totalDestinasi', 5);
    }

    /** @test */
    public function it_displays_empty_index_when_no_destinations()
    {
        $response = $this->actingAs($this->user)
            ->get(route('destinasi.index'));

        $response->assertStatus(200);
        $response->assertViewHas('totalDestinasi', 0);
    }

    /** @test */
    public function it_displays_create_destinasi_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('destinasi.create'));

        $response->assertStatus(200);
        $response->assertViewIs('destinasi.create');
        $response->assertViewHas('destinationCode');
    }

    /** @test */
    public function it_can_create_destinasi_with_valid_data()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Test Destination',
                'description' => 'Test Description',
                'lat' => -0.026559,
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertRedirect(route('destinasi.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('destinasis', [
            'destination_code' => 'D001',
            'name' => 'Test Destination',
        ]);
    }

    /** @test */
    public function it_creates_matriks_jarak_when_creating_second_destinasi()
    {
        Storage::fake('public');
        
        // Create first destination
        $first = Destinasi::factory()->create(['destination_code' => 'D001']);
        
        $file = UploadedFile::fake()->image('test2.jpg');
        
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D002',
                'name' => 'Second Destination',
                'description' => 'Test Description',
                'lat' => -0.03,
                'lng' => 109.35,
                'img' => $file,
            ]);

        $response->assertRedirect(route('destinasi.index'));

        $second = Destinasi::where('destination_code', 'D002')->first();

        // Should create 2 distance records (bidirectional)
        $this->assertEquals(2, MatriksJarak::count());
        
        $this->assertDatabaseHas('matriks_jaraks', [
            'origin_id' => $first->id,
            'destination_id' => $second->id,
        ]);
        
        $this->assertDatabaseHas('matriks_jaraks', [
            'origin_id' => $second->id,
            'destination_id' => $first->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), []);

        $response->assertSessionHasErrors([
            'destination_code',
            'name',
            'description',
            'lat',
            'lng',
            'img',
        ]);
    }

    /** @test */
    public function it_validates_unique_destination_code()
    {
        Storage::fake('public');
        Destinasi::factory()->create(['destination_code' => 'D001']);

        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001', // Duplicate!
                'name' => 'Test',
                'description' => 'Test',
                'lat' => -0.026559,
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertSessionHasErrors('destination_code');
    }

    /** @test */
    public function it_validates_image_format()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Test',
                'description' => 'Test',
                'lat' => -0.026559,
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertSessionHasErrors('img');
    }

    /** @test */
    public function it_validates_image_size_max_2mb()
    {
        $file = UploadedFile::fake()->image('huge.jpg')->size(3000); // 3MB
        
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Test',
                'description' => 'Test',
                'lat' => -0.026559,
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertSessionHasErrors('img');
    }

    /** @test */
    public function it_validates_latitude_range()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Test',
                'description' => 'Test',
                'lat' => 100, // Invalid! Should be -90 to 90
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertSessionHasErrors('lat');
    }

    /** @test */
    public function it_validates_longitude_range()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Test',
                'description' => 'Test',
                'lat' => -0.026559,
                'lng' => 200, // Invalid! Should be -180 to 180
                'img' => $file,
            ]);

        $response->assertSessionHasErrors('lng');
    }

    /** @test */
    public function it_displays_edit_destinasi_form()
    {
        $destinasi = Destinasi::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('destinasi.edit', $destinasi->id));

        $response->assertStatus(200);
        $response->assertViewIs('destinasi.edit');
        $response->assertViewHas('destinasi', $destinasi);
    }

    /** @test */
    public function it_can_update_destinasi_without_changing_image()
    {
        $destinasi = Destinasi::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)
            ->put(route('destinasi.update', $destinasi->id), [
                'destination_code' => $destinasi->destination_code,
                'name' => 'Updated Name',
                'description' => $destinasi->description,
                'lat' => $destinasi->lat,
                'lng' => $destinasi->lng,
            ]);

        $response->assertRedirect(route('destinasi.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('destinasis', [
            'id' => $destinasi->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_can_update_destinasi_with_new_image()
    {
        Storage::fake('public');
        
        $oldImage = 'old_image.jpg';
        Storage::disk('public')->put('images/destinations/' . $oldImage, 'fake content');
        
        $destinasi = Destinasi::factory()->create(['img' => $oldImage]);
        
        $newFile = UploadedFile::fake()->image('new_image.jpg');

        $response = $this->actingAs($this->user)
            ->put(route('destinasi.update', $destinasi->id), [
                'destination_code' => $destinasi->destination_code,
                'name' => $destinasi->name,
                'description' => $destinasi->description,
                'lat' => $destinasi->lat,
                'lng' => $destinasi->lng,
                'img' => $newFile,
            ]);

        $response->assertRedirect(route('destinasi.index'));

        // Old image should be deleted
        $this->assertFalse(
            Storage::disk('public')->exists('images/destinations/' . $oldImage)
        );
        
        // New image should exist
        $destinasi->refresh();
        $this->assertNotEquals($oldImage, $destinasi->img);
    }

    /** @test */
    public function it_can_delete_destinasi()
    {
        Storage::fake('public');
        
        $imageName = 'test_image.jpg';
        Storage::disk('public')->put('images/destinations/' . $imageName, 'fake content');
        
        $destinasi = Destinasi::factory()->create(['img' => $imageName]);

        $response = $this->actingAs($this->user)
            ->delete(route('destinasi.destroy', $destinasi->id));

        $response->assertRedirect(route('destinasi.index'));
        $response->assertSessionHas('success');

        // Destinasi should be deleted
        $this->assertDatabaseMissing('destinasis', ['id' => $destinasi->id]);
        
        // Image should be deleted
        $this->assertFalse(
            Storage::disk('public')->exists('images/destinations/' . $imageName)
        );
    }

    /** @test */
    public function it_returns_404_when_deleting_non_existent_destinasi()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('destinasi.destroy', 9999));

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_reset_all_data_with_confirmation()
    {
        Destinasi::factory()->count(3)->create();
        MatriksJarak::factory()->count(5)->create();

        // ✅ FIXED: Changed to 'destinasi.reset' (match routes)
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.reset'), [
                'confirmation' => 'RESET',
            ]);

        $response->assertRedirect(route('destinasi.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(0, Destinasi::count());
        $this->assertEquals(0, MatriksJarak::count());
    }

    /** @test */
    public function it_requires_reset_confirmation()
    {
        Destinasi::factory()->count(3)->create();

        // ✅ FIXED: Changed to 'destinasi.reset'
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.reset'), [
                'confirmation' => 'WRONG',
            ]);

        $response->assertSessionHasErrors('confirmation');
        $this->assertGreaterThan(0, Destinasi::count());
    }

    /** @test */
    public function it_handles_osrm_api_failure_gracefully()
    {
        Storage::fake('public');
        
        // First destinasi
        Destinasi::factory()->create();
        
        // Mock OSRM failure
        Http::fake([
            'router.project-osrm.org/*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D002',
                'name' => 'Second Destination',
                'description' => 'Test',
                'lat' => -0.03,
                'lng' => 109.35,
                'img' => $file,
            ]);

        // Should still create destinasi even if OSRM fails
        $response->assertRedirect(route('destinasi.index'));
        $this->assertDatabaseHas('destinasis', ['destination_code' => 'D002']);
    }

    /** @test */
    public function guest_cannot_access_destinasi_pages()
    {
        $response = $this->get(route('destinasi.index'));
        $response->assertRedirect(route('login'));
    }
}