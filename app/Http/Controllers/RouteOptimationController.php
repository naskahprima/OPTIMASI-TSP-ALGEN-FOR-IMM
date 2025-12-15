<?php

namespace App\Http\Controllers;

use App\Models\Destinasi;
use App\Models\RuteOptimal;
use Illuminate\Http\Request;

class RouteOptimationController extends Controller
{
    public function index() {


        $id = optional(RuteOptimal::orderBy('total_distance', 'asc')
            ->select('id')
            ->first())->id ?? null;

        $dataMentah = RuteOptimal::find($id);
        $route = json_decode(optional($dataMentah)->route);

        $data = [
            "chromosome" => $route ?? [],
            "distance_km" => optional($dataMentah)->total_distance ?? null, // Berikan nilai default 0
        ];

        $destinasi = Destinasi::all();

        

        return view("optimasi", compact("data", "destinasi"));
    }
}
