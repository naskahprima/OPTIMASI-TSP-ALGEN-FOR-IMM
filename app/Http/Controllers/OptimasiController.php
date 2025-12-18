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
    // Validation...
    $request->validate([
        'kromosom' => 'required|integer|min:1|max:1000',
        'max_gen' => 'required|integer|min:1|max:10000',
        'titik_awal' => 'required|integer|exists:destinasis,id',
        'crossover_rate' => 'required|numeric|min:0|max:1',
        'mutation_rate' => 'required|numeric|min:0|max:1',
    ]);

    // Check minimum destinations
    $totalDestinasi = Destinasi::count();
    if ($totalDestinasi < 2) {
        return redirect()->back()
            ->withInput()
            ->with('error', '❌ Minimal 2 destinasi diperlukan untuk TSP!');
    }

    try {
        $kromosom = $request->input('kromosom');
        $maxGen = $request->input('max_gen');
        $titikAwal = $request->input('titik_awal');
        $crossoverRate = $request->input('crossover_rate');
        $mutationRate = $request->input('mutation_rate');

        $gaService = new GeneticAlgorithmService(
            $kromosom, 
            $maxGen, 
            $titikAwal, 
            $crossoverRate, 
            $mutationRate
        );
    
        $result = $gaService->run();

        $destinasi = Destinasi::all();
        $totalDestinasi = $destinasi->count();
    
        // ✅ FIXED: Return view TANPA ->with('success')
        // Session success di test akan kosong karena memang tidak pakai redirect
        return view('optimasi.generate', [
            'destinasi' => $destinasi,
            'totalDestinasi' => $totalDestinasi,
            'result' => $result,
        ]);

    } catch (\Exception $e) {
        Log::error('Error generating TSP route', [
            'error' => $e->getMessage(),
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