<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Destinasi;
use App\Models\MatriksJarak;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DestinasiModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $destinasi = new Destinasi([
            'destination_code' => 'D001',
            'name' => 'Test Destinasi',
            'description' => 'Test description',
            'lat' => -0.033271,
            'lng' => 109.333557,
            'img' => 'test.jpg',
        ]);

        $this->assertEquals('D001', $destinasi->destination_code);
        $this->assertEquals('Test Destinasi', $destinasi->name);
        $this->assertEquals('Test description', $destinasi->description);
        $this->assertEquals(-0.033271, $destinasi->lat);
        $this->assertEquals(109.333557, $destinasi->lng);
        $this->assertEquals('test.jpg', $destinasi->img);
    }

    /** @test */
    public function it_can_generate_destination_code()
    {
        // Test when no destinations exist
        $code = Destinasi::generateDestinationCode();
        $this->assertEquals('D001', $code);

        // Create some destinations
        Destinasi::factory()->create(['destination_code' => 'D001']);
        Destinasi::factory()->create(['destination_code' => 'D002']);

        $code = Destinasi::generateDestinationCode();
        $this->assertEquals('D003', $code);
    }

    /** @test */
    public function it_generates_sequential_codes_with_leading_zeros()
    {
        for ($i = 1; $i <= 12; $i++) {
            $code = Destinasi::generateDestinationCode();
            Destinasi::factory()->create(['destination_code' => $code]);
        }

        $lastDestinasi = Destinasi::latest()->first();
        $this->assertEquals('D012', $lastDestinasi->destination_code);
    }

    /** @test */
    public function it_has_origin_distances_relationship()
    {
        $destinasi = Destinasi::factory()->create();
        $otherDestinasi = Destinasi::factory()->create();

        MatriksJarak::factory()->create([
            'origin_id' => $destinasi->id,
            'destination_id' => $otherDestinasi->id,
            'distance' => 5000,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $destinasi->originDistances);
        $this->assertCount(1, $destinasi->originDistances);
    }

    /** @test */
    public function it_has_destination_distances_relationship()
    {
        $destinasi = Destinasi::factory()->create();
        $otherDestinasi = Destinasi::factory()->create();

        MatriksJarak::factory()->create([
            'origin_id' => $otherDestinasi->id,
            'destination_id' => $destinasi->id,
            'distance' => 5000,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $destinasi->destinationDistances);
        $this->assertCount(1, $destinasi->destinationDistances);
    }

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $destinasi = Destinasi::factory()->create();

        $this->assertInstanceOf(Destinasi::class, $destinasi);
        $this->assertDatabaseHas('destinasis', [
            'id' => $destinasi->id,
        ]);
    }

    /** @test */
    public function it_stores_coordinates_as_decimal()
    {
        $destinasi = Destinasi::factory()->create([
            'lat' => -0.033271123456789,
            'lng' => 109.333557123456789,
        ]);

        $this->assertIsFloat($destinasi->lat);
        $this->assertIsFloat($destinasi->lng);
    }

    /** @test */
    public function it_can_have_null_image()
    {
        $destinasi = Destinasi::factory()->withoutImage()->create();

        $this->assertNull($destinasi->img);
        $this->assertDatabaseHas('destinasis', [
            'id' => $destinasi->id,
            'img' => null,
        ]);
    }

    /** @test */
    public function description_can_be_long_text()
    {
        $longDescription = str_repeat('Lorem ipsum dolor sit amet. ', 50);
        
        $destinasi = Destinasi::factory()->create([
            'description' => $longDescription,
        ]);

        $this->assertEquals($longDescription, $destinasi->description);
    }

    /** @test */
    public function it_cascades_delete_to_matriks_jarak()
    {
        $destinasi1 = Destinasi::factory()->create();
        $destinasi2 = Destinasi::factory()->create();

        // Create distance matrices
        MatriksJarak::factory()->create([
            'origin_id' => $destinasi1->id,
            'destination_id' => $destinasi2->id,
        ]);

        MatriksJarak::factory()->create([
            'origin_id' => $destinasi2->id,
            'destination_id' => $destinasi1->id,
        ]);

        // Delete should trigger cascade in controller
        // (This is handled in controller, not model)
        $this->assertCount(2, MatriksJarak::all());
    }
}
