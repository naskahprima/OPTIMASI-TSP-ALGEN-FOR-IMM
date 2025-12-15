<?php

namespace App\Http\Controllers;

use App\Models\Destinasi;
use App\Models\RuteOptimal;
use App\Services\GeneticAlgorithmService;
use Illuminate\Http\Request;

class OptimasiController extends Controller
{
    public function index() {
        $ruteOptimal = RuteOptimal::all();
        return view("optimasi.index", compact("ruteOptimal"));
    }



    public function optimasiStore(Request $request){
        RuteOptimal::create([
            "route" => $request->input('solusi'),
            "total_distance" => $request->input('jarak'),
            "date_time" => date('Y-m-d H:i:s')
        ]);
        // return redirect('/optimasi')->with("success", "Data Berhasil Ditambahkan");

        return redirect()->route('optimasi')
                     ->with('success', 'Data Berhasil Ditambahkan');
    }

    public function optimasiShow(Request $request)
    {
       $dataMentah = RuteOptimal::find($request->input("id"));
       $route = json_decode($dataMentah->route);
       $data = [
        "chromosome" => $route,
        "distance_km" => $dataMentah->total_distance,
       ];

       $destinasi = Destinasi::all();

       return view("optimasi.show", compact("data", "destinasi"));
    }

    public function generate() {
        $destinasi = Destinasi::all();
        return view('optimasi.generate', compact("destinasi"));
    }

    public function store(Request $request) {
        $request->validate([
            'kromosom' => 'required|integer|min:1',
            'max_gen' => 'required|integer|min:1',
            'titik_awal' => 'required|integer|exists:destinasis,id',
            'crossover_rate' => 'required|numeric|min:0|max:1',
            'mutation_rate' => 'required|numeric|min:0|max:1',
        ]);
        
        // Ambil data dari request
        $kromosom = $request->input('kromosom');
        $maxGen = $request->input('max_gen');
        $titikAwal = $request->input('titik_awal');
        $crossoverRate = $request->input('crossover_rate');
        $mutationRate = $request->input('mutation_rate');
    
        // Inisialisasi GeneticAlgorithmService
        $gaService = new GeneticAlgorithmService($kromosom, $maxGen, $titikAwal, $crossoverRate, $mutationRate);
    
        // Jalankan algoritma genetika
        $result = $gaService->run();

        // Ambil data destinasi
        $destinasi = Destinasi::all();
    
        return view('optimasi.generate', [
            'destinasi' => $destinasi,
            'result' => $result ?? null,
        ]);
    }

    public function optimasiDestroy(Request $request)  {
        $optimasi = RuteOptimal::find($request->input("id"));
        $optimasi->delete();
        return redirect()->route('optimasi')->with('success', 'Rute Berhasil Di hapus');
    }
}

