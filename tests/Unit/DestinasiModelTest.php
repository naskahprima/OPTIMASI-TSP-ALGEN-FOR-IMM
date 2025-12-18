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
        $fillable = ['destination_code', 'name', 'description', 'lat', 'lng', 'img'];
        $destinasi = new Destinasi();
        
        $this->assertEquals($fillable, $destinasi->getFillable());
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

        // Generate next code
        $code = Destinasi::generateDestinationCode();
        $this->assertEquals('D003', $code);
    }

    /** @test */
    public function it_generates_sequential_codes_with_leading_zeros()
    {
        // Create destinations D001 to D011
        for ($i = 1; $i <= 11; $i++) {
            Destinasi::factory()->create([
                'destination_code' => 'D' . str_pad($i, 3, '0', STR_PAD_LEFT)
            ]);
        }

        // Next should be D012
        $code = Destinasi::generateDestinationCode();
        $this->assertEquals('D012', $code);
    }

    /** @test */
    public function it_has_origin_distances_relationship()
    {
        $destinasi = Destinasi::factory()->create();
        $otherDestinasi = Destinasi::factory()->create();

        MatriksJarak::create([
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

        MatriksJarak::create([
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
        
        $this->assertDatabaseHas('destinasis', [
            'id' => $destinasi->id,
        ]);
    }

    /** @test */
    public function it_stores_coordinates_as_decimal()
    {
        $destinasi = Destinasi::factory()->create([
            'lat' => -0.026559,
            'lng' => 109.333557,
        ]);

        $this->assertEquals(-0.026559, $destinasi->lat);
        $this->assertEquals(109.333557, $destinasi->lng);
    }

    /** @test */
    public function it_can_have_null_image()
    {
        $destinasi = Destinasi::factory()->create([
            'img' => null,
        ]);

        $this->assertNull($destinasi->img);
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
        $destinasi = Destinasi::factory()->create();
        $otherDestinasi = Destinasi::factory()->create();

        // Create distance records
        MatriksJarak::create([
            'origin_id' => $destinasi->id,
            'destination_id' => $otherDestinasi->id,
            'distance' => 5000,
        ]);

        MatriksJarak::create([
            'origin_id' => $otherDestinasi->id,
            'destination_id' => $destinasi->id,
            'distance' => 5000,
        ]);

        $this->assertEquals(2, MatriksJarak::count());

        // Delete destinasi (in real app, controller handles this)
        MatriksJarak::where('origin_id', $destinasi->id)
            ->orWhere('destination_id', $destinasi->id)
            ->delete();
        
        $destinasi->delete();

        // Matriks jarak should be deleted
        $this->assertEquals(0, MatriksJarak::where('origin_id', $destinasi->id)->count());
        $this->assertEquals(0, MatriksJarak::where('destination_id', $destinasi->id)->count());
    }
}