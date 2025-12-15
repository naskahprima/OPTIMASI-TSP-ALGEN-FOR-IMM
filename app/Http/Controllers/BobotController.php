<?php

namespace App\Http\Controllers;

use App\Models\Destinasi;
use App\Models\MatriksJarak;
use Illuminate\Http\Request;

class BobotController extends Controller
{
    public function index(){
        
        $destinations = Destinasi::all();

        // Ambil semua data matriks jarak
        $distanceMatrix = MatriksJarak::all();

        // Kirim data ke view
        return view('bobot.index', compact('destinations', 'distanceMatrix'));
    }

    
}
