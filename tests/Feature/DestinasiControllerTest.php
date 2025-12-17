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
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Masjid Mujahidin',
                'description' => 'Masjid bersejarah di Pontianak',
                'lat' => -0.033271,
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertRedirect(route('destinasi.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('destinasis', [
            'destination_code' => 'D001',
            'name' => 'Masjid Mujahidin',
            'lat' => -0.033271,
            'lng' => 109.333557,
        ]);

        // Verify image was stored
        $destinasi = Destinasi::where('destination_code', 'D001')->first();
        $this->assertNotNull($destinasi->img);
        
        Storage::disk('public')->assertExists('images/destinations/' . $destinasi->img);
    }

    /** @test */
    public function it_creates_matriks_jarak_when_creating_second_destinasi()
    {
        // Create first destinasi
        $first = Destinasi::factory()->create();

        $file = UploadedFile::fake()->image('test.jpg');

        // Create second destinasi
        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D002',
                'name' => 'Test Destinasi 2',
                'description' => 'Test description',
                'lat' => -0.05,
                'lng' => 109.35,
                'img' => $file,
            ]);

        $response->assertRedirect(route('destinasi.index'));

        $second = Destinasi::where('destination_code', 'D002')->first();

        // Should create 2 distance records (bidirectional)
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
            'img'
        ]);
    }

    /** @test */
    public function it_validates_unique_destination_code()
    {
        Destinasi::factory()->create(['destination_code' => 'D001']);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001', // Duplicate!
                'name' => 'Test',
                'description' => 'Test',
                'lat' => -0.033271,
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertSessionHasErrors('destination_code');
    }

    /** @test */
    public function it_validates_image_format()
    {
        $file = UploadedFile::fake()->create('test.pdf', 1000); // Wrong format

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Test',
                'description' => 'Test',
                'lat' => -0.033271,
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertSessionHasErrors('img');
    }

    /** @test */
    public function it_validates_image_size_max_2mb()
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Test',
                'description' => 'Test',
                'lat' => -0.033271,
                'lng' => 109.333557,
                'img' => $file,
            ]);

        $response->assertSessionHasErrors('img');
    }

    /** @test */
    public function it_validates_latitude_range()
    {
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
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D001',
                'name' => 'Test',
                'description' => 'Test',
                'lat' => -0.033271,
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
        $destinasi = Destinasi::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old Description',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('destinasi.update', $destinasi->id), [
                'destination_code' => $destinasi->destination_code,
                'name' => 'New Name',
                'description' => 'New Description',
                'lat' => $destinasi->lat,
                'lng' => $destinasi->lng,
            ]);

        $response->assertRedirect(route('destinasi.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('destinasis', [
            'id' => $destinasi->id,
            'name' => 'New Name',
            'description' => 'New Description',
        ]);
    }

    /** @test */
    public function it_can_update_destinasi_with_new_image()
    {
        $oldImage = 'old_image.jpg';
        Storage::disk('public')->put('images/destinations/' . $oldImage, 'old content');

        $destinasi = Destinasi::factory()->create(['img' => $oldImage]);

        $newFile = UploadedFile::fake()->image('new.jpg');

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
        Storage::disk('public')->assertMissing('images/destinations/' . $oldImage);

        // New image should exist
        $destinasi->refresh();
        $this->assertNotEquals($oldImage, $destinasi->img);
        Storage::disk('public')->assertExists('images/destinations/' . $destinasi->img);
    }

    /** @test */
    public function it_can_delete_destinasi()
    {
        $image = 'test_image.jpg';
        Storage::disk('public')->put('images/destinations/' . $image, 'test content');

        $destinasi = Destinasi::factory()->create(['img' => $image]);

        // Create matriks jarak related to this destinasi
        MatriksJarak::factory()->create([
            'origin_id' => $destinasi->id,
            'destination_id' => Destinasi::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('destinasi.destroy', $destinasi->id));

        $response->assertRedirect(route('destinasi.index'));
        $response->assertSessionHas('success');

        // Destinasi should be deleted
        $this->assertDatabaseMissing('destinasis', ['id' => $destinasi->id]);

        // Image should be deleted
        Storage::disk('public')->assertMissing('images/destinations/' . $image);

        // Matriks jarak should be deleted
        $this->assertDatabaseMissing('matriks_jaraks', [
            'origin_id' => $destinasi->id,
        ]);
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
        // Create test data
        Destinasi::factory()->count(3)->create();
        MatriksJarak::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.reset-all'), [
                'confirmation' => 'RESET',
            ]);

        $response->assertRedirect(route('destinasi.index'));
        $response->assertSessionHas('success');

        // All data should be deleted
        $this->assertEquals(0, Destinasi::count());
        $this->assertEquals(0, MatriksJarak::count());
    }

    /** @test */
    public function it_requires_reset_confirmation()
    {
        Destinasi::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.reset-all'), [
                'confirmation' => 'WRONG',
            ]);

        $response->assertSessionHasErrors('confirmation');

        // Data should still exist
        $this->assertEquals(3, Destinasi::count());
    }

    /** @test */
    public function it_handles_osrm_api_failure_gracefully()
    {
        // Mock OSRM failure
        Http::fake([
            'router.project-osrm.org/*' => Http::response(['error' => 'Service unavailable'], 500),
        ]);

        $first = Destinasi::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('destinasi.create.store'), [
                'destination_code' => 'D002',
                'name' => 'Test Destinasi 2',
                'description' => 'Test description',
                'lat' => -0.05,
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

        $response = $this->get(route('destinasi.create'));
        $response->assertRedirect(route('login'));
    }
}
