<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destinasi extends Model
{
    use HasFactory;
    protected $table = "destinasis";
    protected $fillable = [
        "destination_code", "name", "description", "lat", "lng", "img"
    ];

    public $timestamps = false;

    // Fungsi untuk generate kode otomatis
    // public static function generateDestinationCode()
    // {
    //     $lastDestination = self::orderBy('destination_code', 'desc')->first();
    //     if (!$lastDestination) {
    //         return '001';
    //     }

    //     $lastCode = (int)$lastDestination->destination_code;
    //     $newCode = str_pad($lastCode + 1, 3, '0', STR_PAD_LEFT);

    //     return $newCode;
    // }

    public static function generateDestinationCode()
    {
        $lastDestinasi = self::orderBy('destination_code', 'desc')->first();
        
        if (!$lastDestinasi) {
            return 'D001'; // ← PASTIKAN ADA 'D'
        }
        
        $lastCode = intval(substr($lastDestinasi->destination_code, 1));
        $newCode = $lastCode + 1;
        
        return 'D' . str_pad($newCode, 3, '0', STR_PAD_LEFT); // ← PASTIKAN ADA 'D'
    }
}
