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

    // ✅ ENABLE TIMESTAMPS (karena migration punya timestamps!)
    public $timestamps = true;

    // ✅ TAMBAH RELATIONSHIPS!
    public function originDistances()
    {
        return $this->hasMany(MatriksJarak::class, 'origin_id');
    }

    public function destinationDistances()
    {
        return $this->hasMany(MatriksJarak::class, 'destination_id');
    }

    // ✅ Generate Destination Code
    public static function generateDestinationCode()
    {
        $lastDestinasi = self::orderBy('destination_code', 'desc')->first();
        
        if (!$lastDestinasi) {
            return 'D001';
        }
        
        $lastCode = intval(substr($lastDestinasi->destination_code, 1));
        $newCode = $lastCode + 1;
        
        return 'D' . str_pad($newCode, 3, '0', STR_PAD_LEFT);
    }
}