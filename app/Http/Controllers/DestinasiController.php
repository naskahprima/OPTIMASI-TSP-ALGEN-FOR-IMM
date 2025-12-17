<?php

namespace App\Http\Controllers;

use App\Models\Destinasi;
use App\Models\MatriksJarak;
use App\Models\RuteOptimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\OSRMService;
use Intervention\Image\Facades\Image;
use Nette\Utils\Image as UtilsImage;

class DestinasiController extends Controller
{
    public function index() {
        $destinasi = Destinasi::all();
        $totalDestinasi = $destinasi->count();
        return view('destinasi.index', compact('destinasi', 'totalDestinasi'));
    }

    public function create() {
        $destinationCode = Destinasi::generateDestinationCode();
        return view('destinasi.create', compact('destinationCode'));
    }

    public function store(Request $request)
    {
        // 1. Validasi Input (Enhanced)
        $request->validate([
            'destination_code' => 'required|string|max:10|unique:destinasis,destination_code',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'img' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ], [
            // Custom error messages
            'destination_code.required' => '❌ Kode destinasi harus diisi',
            'destination_code.unique' => '❌ Kode destinasi sudah digunakan',
            'name.required' => '❌ Nama destinasi harus diisi',
            'name.max' => '❌ Nama destinasi maksimal 255 karakter',
            'description.required' => '❌ Deskripsi harus diisi',
            'description.max' => '❌ Deskripsi maksimal 1000 karakter',
            'img.required' => '❌ Gambar destinasi harus diupload',
            'img.image' => '❌ File harus berupa gambar',
            'img.mimes' => '❌ Format gambar harus jpeg, png, atau jpg',
            'img.max' => '❌ Ukuran gambar maksimal 2MB',
            'lat.required' => '❌ Latitude harus diisi',
            'lat.between' => '❌ Latitude harus antara -90 sampai 90',
            'lng.required' => '❌ Longitude harus diisi',
            'lng.between' => '❌ Longitude harus antara -180 sampai 180',
        ]);

        try {
            // 2. Upload & Compress Image
            $imageName = null;
            if ($request->hasFile('img')) {
                $image = $request->file('img');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Compress & resize image (max 800px width)
                $img = UtilsImage::make($image->getRealPath());
                $img->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                // Save compressed image
                $path = storage_path('app/public/images/destinations/' . $imageName);
                $img->save($path, 85); // 85% quality
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

            // Check if there are other destinations
            if ($destinasiLain->count() > 0) {
                $osrmService = new OSRMService();
                $successCount = 0;
                $failCount = 0;

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
                        
                        $successCount++;
                        
                    } catch (\Exception $e) {
                        $failCount++;
                        Log::error('Error calculating distance with OSRM', [
                            'from' => $destinasi->name,
                            'to' => $destinasiLama->name,
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }
                }

                // Log hasil perhitungan
                Log::info('Distance calculation completed', [
                    'destination' => $destinasi->name,
                    'success' => $successCount,
                    'failed' => $failCount
                ]);
            }

            // 5. Redirect dengan Pesan Sukses
            return redirect()->route('destinasi.index')
                ->with('success', '✅ Destinasi "' . $destinasi->name . '" berhasil ditambahkan!');

        } catch (\Exception $e) {
            // Rollback: hapus gambar jika ada error
            if (isset($imageName) && Storage::exists('public/images/destinations/' . $imageName)) {
                Storage::delete('public/images/destinations/' . $imageName);
            }

            Log::error('Error storing destination', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Terjadi kesalahan: ' . $e->getMessage());
        }
    }

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
            'description' => 'required|string|max:1000',
            'img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ], [
            'destination_code.required' => '❌ Kode destinasi harus diisi',
            'destination_code.unique' => '❌ Kode destinasi sudah digunakan',
            'name.required' => '❌ Nama destinasi harus diisi',
            'description.required' => '❌ Deskripsi harus diisi',
            'img.image' => '❌ File harus berupa gambar',
            'img.max' => '❌ Ukuran gambar maksimal 2MB',
            'lat.required' => '❌ Latitude harus diisi',
            'lng.required' => '❌ Longitude harus diisi',
        ]);

        try {
            $destinasi = Destinasi::findOrFail($id);
            $oldImage = $destinasi->img;

            // Update image jika ada
            if ($request->hasFile('img')) {
                // Hapus gambar lama
                if ($oldImage && Storage::exists('public/images/destinations/' . $oldImage)) {
                    Storage::delete('public/images/destinations/' . $oldImage);
                }

                // Upload gambar baru
                $image = $request->file('img');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                $img = UtilsImage::make($image->getRealPath());
                $img->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                $path = storage_path('app/public/images/destinations/' . $imageName);
                $img->save($path, 85);
                
                $destinasi->img = $imageName;
            }

            $destinasi->destination_code = $request->destination_code;
            $destinasi->name = $request->name;
            $destinasi->description = $request->description;
            $destinasi->lat = $request->lat;
            $destinasi->lng = $request->lng;
            $destinasi->save();

            return redirect()->route('destinasi.index')
                ->with('success', '✅ Destinasi "' . $destinasi->name . '" berhasil diupdate!');

        } catch (\Exception $e) {
            Log::error('Error updating destination', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $destinasi = Destinasi::findOrFail($id);
            $name = $destinasi->name;
            $image = $destinasi->img;

            // Hapus relasi di matriks_jaraks
            MatriksJarak::where('origin_id', $id)
                ->orWhere('destination_id', $id)
                ->delete();

            // Hapus gambar
            if ($image && Storage::exists('public/images/destinations/' . $image)) {
                Storage::delete('public/images/destinations/' . $image);
            }

            // Hapus destinasi
            $destinasi->delete();

            Log::info('Destination deleted', [
                'id' => $id,
                'name' => $name
            ]);

            return redirect()->route('destinasi.index')
                ->with('success', '✅ Destinasi "' . $name . '" berhasil dihapus!');

        } catch (\Exception $e) {
            Log::error('Error deleting destination', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', '❌ Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Reset ALL data (destinations, matrix, routes, images)
     * ⚠️ DANGEROUS! Only for admin
     */
    public function resetAll(Request $request)
    {
        // Validation: must type "RESET" to confirm
        $request->validate([
            'confirmation' => 'required|in:RESET',
        ], [
            'confirmation.required' => '❌ Konfirmasi harus diisi',
            'confirmation.in' => '❌ Ketik "RESET" untuk konfirmasi',
        ]);

        try {
            DB::beginTransaction();

            // 1. Hapus semua gambar destinasi
            $images = Destinasi::pluck('img')->filter();
            foreach ($images as $image) {
                if (Storage::exists('public/images/destinations/' . $image)) {
                    Storage::delete('public/images/destinations/' . $image);
                }
            }

            // 2. Hapus semua data dari database
            MatriksJarak::truncate();
            RuteOptimal::truncate();
            Destinasi::truncate();

            // 3. Log aktivitas
            Log::warning('ALL DATA RESET', [
                'user' => auth()->user()->name,
                'email' => auth()->user()->email,
                'timestamp' => now()
            ]);

            DB::commit();

            return redirect()->route('destinasi.index')
                ->with('success', '✅ Semua data berhasil direset! Database kosong.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error resetting all data', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->email
            ]);

            return redirect()->back()
                ->with('error', '❌ Gagal reset data: ' . $e->getMessage());
        }
    }
}