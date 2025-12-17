<?php

namespace App\Http\Controllers;

use App\Models\Destinasi;
use App\Models\RuteOptimal;
use App\Services\GeneticAlgorithmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OptimasiController extends Controller
{
    public function index() {
        $ruteOptimal = RuteOptimal::orderBy('date_time', 'desc')->get();
        return view("optimasi.index", compact("ruteOptimal"));
    }

    public function optimasiStore(Request $request){
        try {
            RuteOptimal::create([
                "route" => $request->input('solusi'),
                "total_distance" => $request->input('jarak'),
                "date_time" => now()
            ]);

            return redirect()->route('optimasi')
                         ->with('success', '✅ Rute optimal berhasil disimpan!');
        } catch (\Exception $e) {
            Log::error('Error saving optimal route', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', '❌ Gagal menyimpan rute: ' . $e->getMessage());
        }
    }

    public function optimasiShow(Request $request)
    {
        try {
            $dataMentah = RuteOptimal::findOrFail($request->input("id"));
            $route = json_decode($dataMentah->route);
            
            $data = [
                "chromosome" => $route,
                "distance_km" => $dataMentah->total_distance,
            ];

            $destinasi = Destinasi::all();

            return view("optimasi.show", compact("data", "destinasi"));
        } catch (\Exception $e) {
            Log::error('Error showing optimal route', [
                'id' => $request->input("id"),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('optimasi')
                ->with('error', '❌ Rute tidak ditemukan!');
        }
    }

    public function generate() {
        $destinasi = Destinasi::all();
        $totalDestinasi = $destinasi->count();
        
        return view('optimasi.generate', compact("destinasi", "totalDestinasi"));
    }

    public function store(Request $request) {
    // 1. Enhanced Validation
    $request->validate([
        'kromosom' => 'required|integer|min:1|max:1000',
        'max_gen' => 'required|integer|min:1|max:10000',
        'titik_awal' => 'required|integer|exists:destinasis,id',
        'crossover_rate' => 'required|numeric|min:0|max:1',
        'mutation_rate' => 'required|numeric|min:0|max:1',
    ], [
        'kromosom.required' => '❌ Jumlah kromosom harus diisi',
        'kromosom.min' => '❌ Jumlah kromosom minimal 1',
        'kromosom.max' => '❌ Jumlah kromosom maksimal 1000',
        'max_gen.required' => '❌ Maksimal generasi harus diisi',
        'max_gen.min' => '❌ Maksimal generasi minimal 1',
        'max_gen.max' => '❌ Maksimal generasi maksimal 10000',
        'titik_awal.required' => '❌ Titik awal harus dipilih',
        'titik_awal.exists' => '❌ Titik awal tidak valid',
        'crossover_rate.required' => '❌ Crossover rate harus diisi',
        'crossover_rate.min' => '❌ Crossover rate minimal 0',
        'crossover_rate.max' => '❌ Crossover rate maksimal 1',
        'mutation_rate.required' => '❌ Mutation rate harus diisi',
        'mutation_rate.min' => '❌ Mutation rate minimal 0',
        'mutation_rate.max' => '❌ Mutation rate maksimal 1',
    ]);

    // 2. Check minimum destinations
    $totalDestinasi = Destinasi::count();
    if ($totalDestinasi < 2) {
        return redirect()->back()
            ->withInput()
            ->with('error', '❌ Minimal 2 destinasi diperlukan untuk TSP! Saat ini hanya ada ' . $totalDestinasi . ' destinasi.');
    }

    try {
        // 3. Ambil data dari request
        $kromosom = $request->input('kromosom');
        $maxGen = $request->input('max_gen');
        $titikAwal = $request->input('titik_awal');
        $crossoverRate = $request->input('crossover_rate');
        $mutationRate = $request->input('mutation_rate');
    
        // 4. Log start
        Log::info('TSP Generation started', [
            'kromosom' => $kromosom,
            'max_gen' => $maxGen,
            'titik_awal' => $titikAwal,
            'user' => auth()->user()->email
        ]);

        // 5. Inisialisasi GeneticAlgorithmService
        $gaService = new GeneticAlgorithmService(
            $kromosom, 
            $maxGen, 
            $titikAwal, 
            $crossoverRate, 
            $mutationRate
        );
    
        // 6. Jalankan algoritma genetika
        $result = $gaService->run();

        // 7. Log success
        Log::info('TSP Generation completed', [
            'distance_km' => $result['distance_km'],
            'execution_time' => $result['execution_time'],
            'fitness' => $result['fitness']
        ]);

        // 8. Ambil data destinasi
        $destinasi = Destinasi::all();
        $totalDestinasi = $destinasi->count();
    
        // ✅ FIXED: Hapus ?? null
        return view('optimasi.generate', [
            'destinasi' => $destinasi,
            'totalDestinasi' => $totalDestinasi,
            'result' => $result,  // ← INI YANG BENER!
        ])->with('success', '✅ Rute optimal berhasil di-generate!');

    } catch (\Exception $e) {
        Log::error('Error generating TSP route', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()
            ->withInput()
            ->with('error', '❌ Gagal generate rute: ' . $e->getMessage());
    }
}

    public function optimasiDestroy(Request $request)  {
        try {
            $optimasi = RuteOptimal::findOrFail($request->input("id"));
            $optimasi->delete();
            
            return redirect()->route('optimasi')
                ->with('success', '✅ Rute berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting optimal route', [
                'id' => $request->input("id"),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', '❌ Gagal menghapus rute!');
        }
    }
}