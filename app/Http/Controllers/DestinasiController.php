<?php

namespace App\Http\Controllers;

use App\Models\Destinasi;
use App\Models\MatriksJarak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Atur Image Kembali setelah teasting dengan cara membuak komentarnya dan menggantiya dengan yangsesuai 

class DestinasiController extends Controller
{
    public function index() {
        $destinasi = Destinasi::all();
        return view('destinasi.index', compact('destinasi'));
    }

    public function create() {
        $destinationCode = Destinasi::generateDestinationCode();
        return view('destinasi.create', compact('destinationCode'));
    }

    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'destination_code' => 'required|string|max:10|unique:destinasis,destination_code',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        // 2. Unggah Gambar (Jika Ada)
        $imageName = null;
        if ($request->hasFile('img')) {
            $image = $request->file('img');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/images/destinations', $imageName);
        }

        // 3. Simpan Data ke Database
        $destinasi = new Destinasi();
        $destinasi->destination_code = $request->destination_code;
        $destinasi->name = $request->name;
        $destinasi->description = $request->description;
        $destinasi->lat = $request->lat;
        $destinasi->lng = $request->lng;
        $destinasi->img = $imageName;
        $destinasi->save();

        // 4. Perhitungan dan Penyimpanan Matriks Jarak
        $destinasiLain = Destinasi::where('id', '!=', $destinasi->id)->get();

        foreach ($destinasiLain as $destinasiLama) {
            try {
                // Hitung jarak menggunakan Google Maps API
                $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                    'origins' => $destinasi->lat . ',' . $destinasi->lng,
                    'destinations' => $destinasiLama->lat . ',' . $destinasiLama->lng,
                    'key' => env('GOOGLE_MAPS_API_KEY'),
                    'units' => 'metric', // Tambahkan unit metric
                ]);

                // PERBAIKAN: Decode JSON response
                $responseData = $response->json();
                
                // Debug: Log response untuk troubleshooting
                Log::info('Google Maps API Response:', $responseData);

                // Periksa apakah response valid dan terdapat data jarak
                if (isset($responseData['status']) && $responseData['status'] === 'OK' &&
                    isset($responseData['rows'][0]['elements'][0]['status']) &&
                    $responseData['rows'][0]['elements'][0]['status'] === 'OK' &&
                    isset($responseData['rows'][0]['elements'][0]['distance']['value'])) {
                    
                    $distance = $responseData['rows'][0]['elements'][0]['distance']['value']; // dalam meter
                    
                    // Simpan jarak dari destinasi baru ke destinasi lain
                    MatriksJarak::create([
                        'origin_id' => $destinasi->id,
                        'destination_id' => $destinasiLama->id,
                        'distance' => $distance,
                    ]);

                    // Simpan juga jarak dari destinasi lain ke destinasi baru
                    MatriksJarak::create([
                        'origin_id' => $destinasiLama->id,
                        'destination_id' => $destinasi->id,
                        'distance' => $distance,
                    ]);
                    
                } else {
                    // Log error untuk debugging
                    $errorMessage = $responseData['error_message'] ?? 'Unknown error';
                    $elementStatus = $responseData['rows'][0]['elements'][0]['status'] ?? 'Unknown status';
                    
                    Log::error("Google Maps API Error: $errorMessage, Element Status: $elementStatus");
                    
                    // Lanjutkan ke destinasi berikutnya alih-alih return error
                    continue;
                }
                
            } catch (\Exception $e) {
                Log::error('Error calculating distance: ' . $e->getMessage());
                continue; // Lanjutkan ke destinasi berikutnya
            }
        }

        // 5. Redirect dengan Pesan Sukses
        return redirect()->route('destinasi.index')->with('success', 'Destinasi berhasil ditambahkan!');
    }




    // tambahkan edit, dan delete dibawah
    public function edit($id)
    {
        $destinasi = Destinasi::findOrFail($id);
        return view('destinasi.edit', compact('destinasi'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'destination_code' => 'required|string|max:10|unique:destinasis,destination_code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $destinasi = Destinasi::findOrFail($id);

        if ($request->hasFile('img')) {
            $image = $request->file('img');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/images/destinations', $imageName);
            $destinasi->img = $imageName;
        }

        $destinasi->destination_code = $request->destination_code;
        $destinasi->name = $request->name;
        $destinasi->description = $request->description;
        $destinasi->lat = $request->lat;
        $destinasi->lng = $request->lng;
        $destinasi->save();

        return redirect()->route('destinasi.index')->with('success', 'Destinasi berhasil diupdate!');
    }


    public function destroy($id)
    {
        $destinasi = Destinasi::findOrFail($id);
        $destinasi->delete();

        return redirect()->route('destinasi.index')->with('success', 'Destinasi berhasil dihapus!');
    }

}
