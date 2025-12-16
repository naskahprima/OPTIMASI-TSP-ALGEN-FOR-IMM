<?php

namespace App\Http\Controllers;

use App\Models\Destinasi;
use App\Models\MatriksJarak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\OSRMService;

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

        // Inisialisasi OSRM Service
        $osrmService = new OSRMService();

        foreach ($destinasiLain as $destinasiLama) {
            try {
                // Hitung jarak menggunakan OSRM API
                $result = $osrmService->getDistance(
                    $destinasi->lat,
                    $destinasi->lng,
                    $destinasiLama->lat,
                    $destinasiLama->lng
                );
                
                $distance = $result['distance']; // sudah dalam meter
                
                // Log untuk debugging (optional)
                Log::info('OSRM Distance Calculated', [
                    'from' => $destinasi->name,
                    'to' => $destinasiLama->name,
                    'distance_m' => $distance,
                ]);
                
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
                
            } catch (\Exception $e) {
                // Log error untuk debugging
                Log::error('Error calculating distance with OSRM', [
                    'from' => $destinasi->name,
                    'to' => $destinasiLama->name,
                    'error' => $e->getMessage()
                ]);
                
                // Lanjutkan ke destinasi berikutnya
                continue;
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
